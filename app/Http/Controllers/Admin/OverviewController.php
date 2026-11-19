<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MedicineRequest;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only admin lists: requests, transactions (reservations), partners and the audit log.
 */
class OverviewController extends Controller
{
    public function requests(Request $request): View
    {
        $statuses = ['active', 'verification_pending', 'reserved', 'completed', 'cancelled', 'expired'];
        $status = in_array($request->query('status'), $statuses, true) ? $request->query('status') : '';

        return view('admin.requests', [
            'requests' => MedicineRequest::with(['owner', 'medicine', 'district'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'statuses' => $statuses,
        ]);
    }

    public function transactions(Request $request): View
    {
        $statuses = ['pending', 'accepted', 'completed', 'declined', 'cancelled'];
        $status = in_array($request->query('status'), $statuses, true) ? $request->query('status') : '';

        return view('admin.transactions', [
            'transactions' => Reservation::with(['listing.medicine', 'listing.owner', 'requester'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(20)->withQueryString(),
            'status' => $status,
            'totals' => Reservation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function partners(): View
    {
        return view('admin.partners', [
            'partners' => User::where('role', 'partner')->with('district')->withCount('listings')->orderBy('name')->get(),
        ]);
    }

    public function auditLogs(Request $request): View
    {
        $areas = ['listing', 'user', 'report', 'verification', 'medicine', 'announcement', 'settings'];
        $area = in_array($request->query('area'), $areas, true) ? $request->query('area') : '';
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 80) : '';

        return view('admin.audit-logs', [
            'logs' => AuditLog::with('actor')
                ->when($area, fn ($query) => $query->where('action', 'like', $area.'.%'))
                ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                    ->where('description', 'like', '%'.addcslashes($q, '%_\\').'%')
                    ->orWhere('action', 'like', '%'.addcslashes($q, '%_\\').'%')
                    ->orWhereHas('actor', fn ($a) => $a->where('name', 'like', '%'.addcslashes($q, '%_\\').'%'))))
                ->latest('id')->paginate(30)->withQueryString(),
            'area' => $area,
            'areas' => $areas,
            'q' => $q,
        ]);
    }
}
