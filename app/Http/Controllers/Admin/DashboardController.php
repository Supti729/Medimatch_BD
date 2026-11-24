<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ListingStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\District;
use App\Models\MedicineListing;
use App\Models\MedicineRequest;
use App\Models\Report;
use App\Models\Reservation;
use App\Models\User;
use App\Models\VerificationSubmission;
use App\Services\MatchingServiceClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(MatchingServiceClient $matching): View
    {
        $months = collect(range(5, 0))->mapWithKeys(fn (int $back) => [
            now()->startOfMonth()->subMonths($back)->format('Y-m') => now()->startOfMonth()->subMonths($back)->format('M'),
        ]);
        $since = now()->startOfMonth()->subMonths(5);

        // Count dates per month in PHP so the same code works on MySQL and SQLite.
        $perMonth = fn (Collection $dates) => $months->mapWithKeys(fn (string $label, string $key) => [
            $label => $dates->filter(fn ($date) => $date && $date->format('Y-m') === $key)->count(),
        ])->all();

        $byDistrict = function (string $table) {
            $counts = \DB::table($table)->selectRaw('district_id, count(*) as total')->groupBy('district_id')->orderByDesc('total')->limit(6)->pluck('total', 'district_id');
            $names = District::whereIn('id', $counts->keys())->pluck('name', 'id');

            return $counts->mapWithKeys(fn ($total, $id) => [$names[$id] ?? 'Unknown' => (int) $total])->all();
        };

        $types = MedicineListing::selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        return view('admin.dashboard', [
            'stats' => [
                'total_users' => User::count(),
                'active_users' => User::where('status', 'active')->count(),
                'total_listings' => MedicineListing::count(),
                'active_listings' => MedicineListing::publiclyVisible()->count(),
                'total_requests' => MedicineRequest::count(),
                'pending_verification' => VerificationSubmission::whereIn('status', ['submitted', 'under_review'])->count(),
                'open_reports' => Report::whereIn('status', ['open', 'escalated'])->count(),
                'completed' => Reservation::where('status', 'completed')->count(),
                'partners' => User::where('role', 'partner')->count(),
            ],
            'charts' => [
                'registrations' => $perMonth(User::where('created_at', '>=', $since)->pluck('created_at')),
                'completed' => $perMonth(Reservation::where('completed_at', '>=', $since)->pluck('completed_at')),
                'listings_by_district' => $byDistrict('medicine_listings'),
                'requests_by_district' => $byDistrict('medicine_requests'),
                'listing_types' => ['Donation' => (int) ($types['donation'] ?? 0), 'Affordable access' => (int) ($types['affordable_access'] ?? 0)],
            ],
            'pendingListings' => MedicineListing::with(['owner', 'district', 'medicine'])->where('status', ListingStatus::PendingReview->value)->oldest('submitted_at')->limit(5)->get(),
            'pendingVerifications' => VerificationSubmission::with('request.medicine')->whereIn('status', ['submitted', 'under_review'])->oldest()->limit(5)->get(),
            'openReports' => Report::whereIn('status', ['open', 'escalated'])->latest()->limit(5)->get(),
            'recentAudit' => AuditLog::with('actor')->latest('id')->limit(5)->get(),
            'matchingHealth' => Cache::remember('admin:matching-health', 30, fn () => $matching->health()),
        ]);
    }
}
