<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Simpler signed-in pages that only display information in the front-end part.
 * Each one moves to its own controller when its backend feature is built.
 */
class AccountPageController extends Controller
{
    public function saved(): View
    {
        return view('account.saved', [
            'listings' => SampleAccount::saved(),
            'usingSampleData' => true,
        ]);
    }

    public function notifications(Request $request): View
    {
        $all = SampleAccount::notifications();
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        return view('account.notifications', [
            'notifications' => $filter === 'unread' ? $all->where('read', false)->values() : $all,
            'filter' => $filter,
            'unreadCount' => $all->where('read', false)->count(),
            'usingSampleData' => true,
        ]);
    }

    public function history(Request $request): View
    {
        $all = SampleAccount::history();
        $roles = ['donated', 'received', 'requested'];
        $role = in_array($request->query('role'), $roles, true) ? $request->query('role') : '';

        return view('account.history', [
            'entries' => $role ? $all->where('role', $role)->values() : $all,
            'role' => $role,
            'roles' => $roles,
            'usingSampleData' => true,
        ]);
    }

    public function verification(): View
    {
        return view('account.verification', [
            'submissions' => SampleAccount::verifications(),
            'requests' => SampleAccount::requests()->where('requires_prescription', true)
                ->whereIn('status', ['active', 'matching', 'match_found', 'verification_pending'])->values(),
            'usingSampleData' => true,
        ]);
    }

    public function profile(): View
    {
        return view('account.profile.show', [
            'user' => SampleAccount::user(),
            'stats' => SampleAccount::stats(),
            'usingSampleData' => true,
        ]);
    }

    public function editProfile(): View
    {
        return view('account.profile.edit', [
            'user' => SampleAccount::user(),
            'usingSampleData' => true,
        ]);
    }

    public function privacy(): View
    {
        return view('account.settings.privacy', [
            'user' => SampleAccount::user(),
            'usingSampleData' => true,
        ]);
    }

    public function security(): View
    {
        return view('account.settings.security', [
            'user' => SampleAccount::user(),
            'usingSampleData' => true,
        ]);
    }
}
