<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\SimpleNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.notifications', [
            'announcements' => Announcement::with('sender')->latest()->limit(20)->get(),
            'audiences' => collect(Announcement::AUDIENCES)->map(fn ($label, $key) => $label.' ('.$this->recipients($key)->count().')')->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:500'],
            'audience' => ['required', Rule::in(array_keys(Announcement::AUDIENCES))],
        ]);

        $recipients = $this->recipients($data['audience'])->get();
        Notification::send($recipients, new SimpleNotification('announcement', $data['title'], $data['message'], route('notifications.index', [], false)));

        $announcement = new Announcement;
        $announcement->forceFill($data + ['sent_by' => $request->user()->id, 'recipients_count' => $recipients->count()])->save();
        AuditLog::record('announcement.sent', $announcement, "Sent announcement \"{$data['title']}\" to {$recipients->count()} users");

        return back()->with('success', "The announcement was sent to {$recipients->count()} ".str('user')->plural($recipients->count()).'.');
    }

    private function recipients(string $audience)
    {
        $query = User::where('status', 'active');

        return match ($audience) {
            'donors' => $query->whereHas('listings', fn ($l) => $l->whereIn('status', ['available', 'requested', 'reserved'])),
            'requesters' => $query->whereHas('medicineRequests', fn ($r) => $r->whereIn('status', ['active', 'verification_pending', 'reserved'])),
            'partners' => $query->where('role', 'partner'),
            default => $query,
        };
    }
}
