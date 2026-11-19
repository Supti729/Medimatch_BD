<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SampleAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Administration panel pages.
 *
 * Front-end part: every page reads SampleAdmin and nothing can be changed.
 * Backend push B5 splits this into focused controllers, adds the admin
 * middleware and policies, and records each action in the audit log.
 */
class AdminPageController extends Controller
{
    public function login(): View
    {
        return view('admin.login');
    }

    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'stats' => SampleAdmin::stats(),
            'charts' => SampleAdmin::charts(),
            'pendingListings' => SampleAdmin::listings()->where('status', 'pending_review')->values(),
            'pendingVerifications' => SampleAdmin::verifications()->whereIn('status', ['submitted', 'under_review'])->values(),
            'openReports' => SampleAdmin::reports()->whereIn('status', ['open', 'escalated'])->values(),
            'recentAudit' => SampleAdmin::auditLogs()->take(5),
        ]);
    }

    public function users(Request $request): View
    {
        $status = $this->oneOf($request, 'status', ['active', 'suspended']);
        $role = $this->oneOf($request, 'role', ['user', 'partner', 'reviewer', 'admin']);
        $q = $this->text($request, 'q');

        $users = SampleAdmin::users()
            ->when($status, fn (Collection $list) => $list->where('status', $status))
            ->when($role, fn (Collection $list) => $list->where('role', $role))
            ->when($q !== '', fn (Collection $list) => $list->filter(
                fn (array $user) => Str::contains(Str::lower($user['name'].' '.$user['email'].' '.$user['district']), Str::lower($q))
            ))
            ->values();

        return view('admin.users.index', compact('users', 'status', 'role', 'q'));
    }

    public function user(int $user): View
    {
        $found = SampleAdmin::findUser($user);
        abort_if($found === null, 404);

        return view('admin.users.show', [
            'user' => $found,
            'listings' => SampleAdmin::listings()->where('owner.id', $found['id'])->values(),
            'reports' => SampleAdmin::reports()->where('reported_user.id', $found['id'])->values(),
            'audit' => SampleAdmin::auditLogs()->filter(fn (array $log) => Str::contains($log['target'], 'User #'.$found['id'].' '))->values(),
        ]);
    }

    public function medicines(Request $request): View
    {
        $q = $this->text($request, 'q');

        $medicines = SampleAdmin::medicines()
            ->when($q !== '', fn (Collection $list) => $list->filter(
                fn (array $m) => Str::contains(Str::lower($m['generic_name'].' '.implode(' ', $m['brands'])), Str::lower($q))
            ))
            ->values();

        return view('admin.medicines', compact('medicines', 'q'));
    }

    public function listings(Request $request): View
    {
        $tabs = [
            'all' => null,
            'pending' => ['pending_review'],
            'public' => config('medimatch.public_listing_statuses'),
            'blocked' => ['blocked', 'rejected'],
        ];
        $tab = $this->oneOf($request, 'tab', array_keys($tabs)) ?: 'all';
        $all = SampleAdmin::listings();

        return view('admin.listings.index', [
            'listings' => $tabs[$tab] ? $all->whereIn('status', $tabs[$tab])->values() : $all,
            'tab' => $tab,
            'counts' => collect($tabs)->map(fn (?array $statuses) => $statuses ? $all->whereIn('status', $statuses)->count() : $all->count()),
        ]);
    }

    public function listing(int $listing): View
    {
        $found = SampleAdmin::findListing($listing);
        abort_if($found === null, 404);

        return view('admin.listings.show', [
            'listing' => $found,
            'reports' => SampleAdmin::reports()->filter(fn (array $r) => Str::contains($r['target'], '(listing #'.$found['id'].')'))->values(),
            'audit' => SampleAdmin::auditLogs()->filter(fn (array $log) => Str::contains($log['target'], 'Listing #'.$found['id'].' '))->values(),
            'settings' => SampleAdmin::settings(),
        ]);
    }

    public function requests(Request $request): View
    {
        $status = $this->oneOf($request, 'status', ['active', 'matching', 'match_found', 'verification_pending', 'reserved', 'completed', 'cancelled', 'expired']);

        return view('admin.requests', [
            'requests' => SampleAdmin::requests()->when($status, fn (Collection $list) => $list->where('status', $status))->values(),
            'status' => $status,
        ]);
    }

    public function verifications(Request $request): View
    {
        $tabs = ['waiting' => ['submitted', 'under_review'], 'decided' => ['verified', 'rejected', 'reupload_required'], 'all' => null];
        $tab = $this->oneOf($request, 'tab', array_keys($tabs)) ?: 'waiting';
        $all = SampleAdmin::verifications();

        return view('admin.verifications.index', [
            'submissions' => $tabs[$tab] ? $all->whereIn('status', $tabs[$tab])->values() : $all,
            'tab' => $tab,
            'counts' => collect($tabs)->map(fn (?array $statuses) => $statuses ? $all->whereIn('status', $statuses)->count() : $all->count()),
        ]);
    }

    public function verification(int $verification): View
    {
        $found = SampleAdmin::findVerification($verification);
        abort_if($found === null, 404);

        return view('admin.verifications.show', [
            'submission' => $found,
            'audit' => SampleAdmin::auditLogs()->filter(fn (array $log) => $log['target'] === 'Verification #'.$found['id'])->values(),
        ]);
    }

    public function reports(Request $request): View
    {
        $tabs = ['open' => ['open', 'escalated'], 'closed' => ['resolved', 'rejected'], 'all' => null];
        $tab = $this->oneOf($request, 'tab', array_keys($tabs)) ?: 'open';
        $all = SampleAdmin::reports();

        return view('admin.reports.index', [
            'reports' => $tabs[$tab] ? $all->whereIn('status', $tabs[$tab])->values() : $all,
            'tab' => $tab,
            'counts' => collect($tabs)->map(fn (?array $statuses) => $statuses ? $all->whereIn('status', $statuses)->count() : $all->count()),
        ]);
    }

    public function report(int $report): View
    {
        $found = SampleAdmin::findReport($report);
        abort_if($found === null, 404);

        return view('admin.reports.show', [
            'report' => $found,
            'related' => SampleAdmin::reports()->where('reported_user.id', $found['reported_user']['id'])->where('id', '!=', $found['id'])->values(),
            'audit' => SampleAdmin::auditLogs()->filter(fn (array $log) => $log['target'] === 'Report #'.$found['id'])->values(),
        ]);
    }

    public function transactions(Request $request): View
    {
        $status = $this->oneOf($request, 'status', ['requested', 'reserved', 'completed', 'cancelled']);
        $all = SampleAdmin::transactions();

        return view('admin.transactions', [
            'transactions' => $all->when($status, fn (Collection $list) => $list->where('status', $status))->values(),
            'status' => $status,
            'totals' => $all->countBy('status'),
        ]);
    }

    public function partners(): View
    {
        return view('admin.partners', [
            'partners' => SampleAdmin::partners(),
            'plans' => SampleAdmin::plans(),
        ]);
    }

    public function notifications(): View
    {
        return view('admin.notifications', [
            'announcements' => SampleAdmin::announcements(),
            'userCount' => SampleAdmin::users()->where('status', 'active')->count(),
        ]);
    }

    public function auditLogs(Request $request): View
    {
        $q = $this->text($request, 'q');
        $area = $this->oneOf($request, 'area', ['listing', 'user', 'report', 'verification', 'announcement', 'settings']);

        $logs = SampleAdmin::auditLogs()
            ->when($area, fn (Collection $list) => $list->filter(fn (array $log) => Str::startsWith($log['action'], $area.'.')))
            ->when($q !== '', fn (Collection $list) => $list->filter(
                fn (array $log) => Str::contains(Str::lower($log['actor'].' '.$log['action'].' '.$log['target']), Str::lower($q))
            ))
            ->values();

        return view('admin.audit-logs', compact('logs', 'q', 'area'));
    }

    public function settings(): View
    {
        return view('admin.settings', ['settings' => SampleAdmin::settings()]);
    }

    // ----- Helpers: read filters from the URL and ignore anything unexpected -----

    private function text(Request $request, string $key): string
    {
        return is_string($request->query($key)) ? mb_substr(trim($request->query($key)), 0, 80) : '';
    }

    private function oneOf(Request $request, string $key, array $allowed): string
    {
        $value = $this->text($request, $key);

        return in_array($value, $allowed, true) ? $value : '';
    }
}
