<?php

namespace App\Http\Controllers\Account;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\MedicineRequestForm;
use App\Models\Medicine;
use App\Models\MedicineAlias;
use App\Models\MedicineRequest;
use App\Services\MatchingService;
use App\Services\MatchingServiceClient;
use App\Services\ReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The signed-in user's own medicine requests.
 */
class MedicineRequestController extends Controller
{
    public const TABS = [
        'all' => null,
        'open' => ['active', 'verification_pending', 'reserved'],
        'closed' => ['completed', 'cancelled', 'expired'],
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'all';
        $mine = $request->user()->medicineRequests();
        $countsByStatus = (clone $mine)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('account.requests.index', [
            'requests' => (clone $mine)
                ->with(['medicine', 'upazila', 'district'])
                ->when(self::TABS[$tab], fn ($q, $statuses) => $q->whereIn('status', $statuses))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'tab' => $tab,
            'counts' => collect(self::TABS)->map(fn (?array $statuses) => $statuses ? $countsByStatus->only($statuses)->sum() : $countsByStatus->sum()),
        ]);
    }

    public function create(Request $request, MatchingServiceClient $matching): View
    {
        // "Request this medicine" from an empty search carries the search text: pre-select a catalogue match.
        $search = is_string($request->query('q')) ? trim(mb_substr($request->query('q'), 0, 80)) : '';
        $preselect = $search === '' ? null : (
            $this->catalogueMatch($matching->normalize($search))
            ?? MedicineAlias::where('brand_name', 'like', $search.'%')->value('medicine_id')
            ?? Medicine::where('generic_name', 'like', $search.'%')->value('id')
        );

        return view('account.requests.create', [
            'medicineRequest' => null,
            'catalogue' => $this->catalogue(),
            'preselect' => $preselect,
            'location' => $request->user()->only(['division_id', 'district_id', 'upazila_id']),
        ]);
    }

    public function store(MedicineRequestForm $form): RedirectResponse
    {
        $medicineRequest = new MedicineRequest($form->safe()->except('confirm'));
        $medicineRequest->user_id = $form->user()->id; // never from the form
        $medicineRequest->expires_at = now()->addDays(config('medimatch.request_expiry_days'));
        $medicineRequest->save();

        return redirect()->route('requests.show', $medicineRequest)->with('success', 'Your request was posted. Matching listings are shown below.');
    }

    public function show(MedicineRequest $medicineRequest, MatchingService $matching): View
    {
        Gate::authorize('view', $medicineRequest);
        $medicineRequest->load(['medicine', 'upazila', 'district', 'reservations.listing.medicine', 'reservations.conversation']);

        $matches = $medicineRequest->status === RequestStatus::Active ? $matching->forRequest($medicineRequest) : collect();

        return view('account.requests.show', [
            'medicineRequest' => $medicineRequest,
            'matches' => $matches,
            // Listings this user has already asked for, so the page can say so
            'requestedListingIds' => $medicineRequest->owner->reservations()->active()->pluck('medicine_listing_id')->all(),
        ]);
    }

    public function edit(MedicineRequest $medicineRequest): View
    {
        Gate::authorize('update', $medicineRequest);

        return view('account.requests.edit', [
            'medicineRequest' => $medicineRequest,
            'catalogue' => $this->catalogue(),
            'preselect' => $medicineRequest->medicine_id,
            'location' => $medicineRequest->only(['division_id', 'district_id', 'upazila_id']),
        ]);
    }

    public function update(MedicineRequestForm $form, MedicineRequest $medicineRequest): RedirectResponse
    {
        Gate::authorize('update', $medicineRequest);

        $medicineRequest->fill($form->safe()->except('confirm'))->save();

        return redirect()->route('requests.show', $medicineRequest)->with('success', 'Your request was updated.');
    }

    public function cancel(MedicineRequest $medicineRequest, ReservationService $reservations): RedirectResponse
    {
        Gate::authorize('cancel', $medicineRequest);

        DB::transaction(function () use ($medicineRequest, $reservations) {
            // Release anything reserved for this request first, so the donors' listings become available again.
            $medicineRequest->reservations()->active()->get()
                ->each(fn ($reservation) => $reservations->cancel($reservation, $medicineRequest->owner));

            $medicineRequest->refresh()->moveTo(RequestStatus::Cancelled);
        });

        return redirect()->route('requests.show', $medicineRequest)->with('success', 'Your request was cancelled.');
    }

    /**
     * The one listable catalogue medicine that fits how the AI service understood a phrase
     * (generic name, and strength or form if stated). Null when it is ambiguous or unknown,
     * so the user chooses themselves.
     */
    private function catalogueMatch(?array $understood): ?int
    {
        if (! $understood || empty($understood['generics'])) {
            return null;
        }

        $fits = Medicine::where('eligibility', 'allowed')
            ->whereIn(\DB::raw('lower(generic_name)'), $understood['generics'])
            ->get()
            ->filter(function (Medicine $m) use ($understood) {
                preg_match_all('/\d+(?:\.\d+)?/', $m->strength, $numbers);
                $hasStrengths = collect($understood['strengths'] ?? [])->every(fn ($n) => in_array((float) $n, array_map('floatval', $numbers[0]), true));
                $sameForm = empty($understood['form']) || mb_strtolower($m->dosage_form) === $understood['form'];

                return $hasStrengths && $sameForm;
            });

        return $fits->count() === 1 ? $fits->first()->id : null;
    }

    private function catalogue()
    {
        return Medicine::with('aliases')->where('eligibility', 'allowed')->orderBy('generic_name')->orderBy('strength')->get();
    }
}
