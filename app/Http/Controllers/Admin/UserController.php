<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly ModerationService $moderation) {}

    public function index(Request $request): View
    {
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 80) : '';
        $status = in_array($request->query('status'), ['active', 'suspended'], true) ? $request->query('status') : '';
        $role = in_array($request->query('role'), array_column(Role::cases(), 'value'), true) ? $request->query('role') : '';

        $users = User::query()
            ->with('district')
            ->withCount(['listings', 'medicineRequests'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', '%'.addcslashes($q, '%_\\').'%')
                ->orWhere('email', 'like', '%'.addcslashes($q, '%_\\').'%')
                ->orWhere('phone', 'like', '%'.addcslashes($q, '%_\\').'%')))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($role, fn ($query) => $query->where('role', $role))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $reportCounts = Report::whereIn('reported_user_id', $users->pluck('id'))->selectRaw('reported_user_id, count(*) as total')->groupBy('reported_user_id')->pluck('total', 'reported_user_id');

        return view('admin.users.index', compact('users', 'q', 'status', 'role', 'reportCounts'));
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user->load(['district', 'upazila'])->loadCount(['listings', 'medicineRequests']),
            'listings' => $user->listings()->with('medicine')->latest()->limit(10)->get(),
            'reports' => Report::where('reported_user_id', $user->id)->latest()->get(),
            'audit' => AuditLog::with('actor')->where('subject_type', 'user')->where('subject_id', $user->id)->latest('id')->get(),
        ]);
    }

    public function status(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['suspend', 'restore'])],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $data['decision'] === 'suspend'
            ? $this->moderation->suspend($user, $request->user(), $data['note'])
            : $this->moderation->restore($user, $request->user(), $data['note']);

        return back()->with('success', $data['decision'] === 'suspend' ? "{$user->name} was suspended." : "{$user->name} was restored.");
    }

    public function role(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(['user', 'partner', 'reviewer'])],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->moderation->changeRole($user, $request->user(), Role::from($data['role']), $data['note']);

        return back()->with('success', "{$user->name} is now a ".Role::from($data['role'])->label().'.');
    }
}
