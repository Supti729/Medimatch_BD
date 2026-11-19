<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MedicineListing;
use App\Models\Report;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ListingModerationController extends Controller
{
    public const TABS = [
        'all' => null,
        'pending' => ['pending_review'],
        'public' => ['available', 'requested', 'reserved'],
        'blocked' => ['blocked', 'rejected'],
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'all';
        $counts = MedicineListing::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.listings.index', [
            'listings' => MedicineListing::with(['medicine', 'owner', 'district'])
                ->when(self::TABS[$tab], fn ($q, $statuses) => $q->whereIn('status', $statuses))
                ->when($tab === 'pending', fn ($q) => $q->oldest('submitted_at'), fn ($q) => $q->latest())
                ->paginate(20)
                ->withQueryString(),
            'tab' => $tab,
            'counts' => collect(self::TABS)->map(fn ($statuses) => $statuses ? $counts->only($statuses)->sum() : $counts->sum()),
            'reportCounts' => Report::where('target_type', 'listing')->selectRaw('target_id, count(*) as total')->groupBy('target_id')->pluck('total', 'target_id'),
        ]);
    }

    public function show(MedicineListing $listing): View
    {
        return view('admin.listings.show', [
            'listing' => $listing->load(['medicine.aliases', 'owner', 'images', 'upazila', 'district', 'division', 'alias']),
            'reports' => Report::where('target_type', 'listing')->where('target_id', $listing->id)->latest()->get(),
            'audit' => AuditLog::with('actor')->where('subject_type', 'listing')->where('subject_id', $listing->id)->latest('id')->get(),
        ]);
    }

    public function decide(Request $request, MedicineListing $listing, ModerationService $moderation): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'reject', 'block', 'unblock'])],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $moderation->decideListing($listing, $data['decision'], $data['note']);

        return back()->with('success', 'Decision recorded. The owner was notified.');
    }
}
