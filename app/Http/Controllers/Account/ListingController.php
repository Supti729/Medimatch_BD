<?php

namespace App\Http\Controllers\Account;

use App\Enums\ListingAction;
use App\Enums\ListingStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ListingRequest;
use App\Models\Medicine;
use App\Models\MedicineListing;
use App\Services\ReservationService;
use App\Support\ImageSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The signed-in user's own medicine listings.
 */
class ListingController extends Controller
{
    /** Tabs on My Listings: tab key => statuses it contains. */
    public const TABS = [
        'all' => null,
        'active' => ['available', 'requested', 'reserved', 'paused', 'unavailable'],
        'review' => ['draft', 'pending_review', 'rejected'],
        'closed' => ['completed', 'cancelled', 'blocked'],
    ];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'all';
        $mine = $request->user()->listings();

        $countsByStatus = (clone $mine)->toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('account.listings.index', [
            'listings' => (clone $mine)
                ->with(['medicine', 'upazila', 'district', 'images'])
                ->when(self::TABS[$tab], fn ($q, $statuses) => $q->whereIn('status', $statuses))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'tab' => $tab,
            'counts' => collect(self::TABS)->map(fn (?array $statuses) => $statuses
                ? $countsByStatus->only($statuses)->sum()
                : $countsByStatus->sum()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('account.listings.create', [
            'listing' => null,
            'catalogue' => $this->catalogue(),
            'location' => $request->user()->only(['division_id', 'district_id', 'upazila_id']),
        ]);
    }

    public function store(ListingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $listing = DB::transaction(function () use ($request, $data) {
            $listing = new MedicineListing($this->attributesFrom($data));
            $listing->user_id = $request->user()->id; // the owner always comes from the session, never the form
            $listing->save();

            $this->storeImages($listing, $request->file('images', []));

            if ($data['action'] === 'submit') {
                $listing->transitionTo(ListingStatus::PendingReview);
            }

            return $listing;
        });

        return redirect()->route('listings.index', ['tab' => 'review'])->with('success', $data['action'] === 'submit'
            ? "{$listing->brand_name} was submitted. A moderator will review it before it becomes visible."
            : "{$listing->brand_name} was saved as a draft.");
    }

    public function edit(MedicineListing $listing): View
    {
        Gate::authorize('view', $listing);

        return view('account.listings.edit', [
            'listing' => $listing->load(['medicine', 'images', 'reservations' => fn ($q) => $q->with(['requester', 'request', 'conversation'])->latest()]),
            'catalogue' => $this->catalogue(),
            'location' => $listing->only(['division_id', 'district_id', 'upazila_id']),
            'locked' => ! request()->user()->can('update', $listing),
            'actions' => request()->user()->can('changeStatus', $listing) ? ListingAction::availableFor($listing->status) : [],
        ]);
    }

    public function update(ListingRequest $request, MedicineListing $listing): RedirectResponse
    {
        Gate::authorize('update', $listing);
        $data = $request->validated();

        DB::transaction(function () use ($request, $listing, $data) {
            $listing->fill($this->attributesFrom($data))->save();

            $this->removeImages($listing, $data['remove_images'] ?? []);
            $this->storeImages($listing, $request->file('images', []));

            // A listing that was already approved goes back to review after any change.
            $wasApproved = in_array($listing->status, [ListingStatus::Available, ListingStatus::Paused, ListingStatus::Unavailable], true);

            if ($wasApproved || $data['action'] === 'submit') {
                $listing->transitionTo(ListingStatus::PendingReview);
            } elseif ($listing->status === ListingStatus::Rejected) {
                $listing->transitionTo(ListingStatus::Draft);
            }
        });

        return redirect()->route('listings.index', ['tab' => 'review'])->with('success', $listing->status === ListingStatus::PendingReview
            ? "Your changes to {$listing->brand_name} were saved and sent for review."
            : "Your changes to {$listing->brand_name} were saved.");
    }

    public function changeStatus(Request $request, MedicineListing $listing): RedirectResponse
    {
        Gate::authorize('changeStatus', $listing);

        $request->validate(['action' => ['required', Rule::enum(ListingAction::class)]]);
        $action = ListingAction::from($request->input('action'));

        if (! in_array($action, ListingAction::availableFor($listing->status), true)) {
            return back()->with('error', "You cannot {$this->lower($action->label())} while it is ".str_replace('_', ' ', $listing->status->value).'.');
        }

        try {
            DB::transaction(function () use ($listing, $action) {
                $listing->transitionTo($action->target());

                // Paused, cancelled or hidden: nobody can keep waiting for it.
                if (in_array($action, [ListingAction::Pause, ListingAction::Cancel, ListingAction::MarkUnavailable], true)) {
                    app(ReservationService::class)->closeAllFor($listing);
                }
            });
        } catch (InvalidStatusTransition) {
            return back()->with('error', 'That change is not allowed for this listing.');
        }

        return back()->with('success', $action->successMessage());
    }

    public function destroy(MedicineListing $listing): RedirectResponse
    {
        Gate::authorize('delete', $listing);

        $paths = $listing->images()->pluck('path')->all();
        $name = $listing->brand_name;
        $listing->delete();
        Storage::disk('public')->delete($paths);

        return redirect()->route('listings.index')->with('success', "{$name} was deleted.");
    }

    // ----- Helpers -----

    /** Listable catalogue medicines with their brand names, for the form. */
    private function catalogue()
    {
        return Medicine::with('aliases')->where('eligibility', 'allowed')->orderBy('generic_name')->orderBy('strength')->get();
    }

    private function attributesFrom(array $data): array
    {
        $aliasId = Medicine::find($data['medicine_id'])
            ->aliases()
            ->whereRaw('lower(brand_name) = ?', [mb_strtolower($data['brand_name'])])
            ->value('id');

        return [
            'medicine_id' => $data['medicine_id'],
            'medicine_alias_id' => $aliasId,
            'brand_name' => $data['brand_name'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'expiry_date' => MedicineListing::expiryFromMonth($data['expiry']),
            'batch_number' => $data['batch_number'],
            'packaging' => $data['packaging'],
            'type' => $data['type'],
            'division_id' => $data['division_id'],
            'district_id' => $data['district_id'],
            'upazila_id' => $data['upazila_id'],
        ];
    }

    private function storeImages(MedicineListing $listing, array $files): void
    {
        $position = (int) $listing->images()->max('position');

        foreach ($files as $file) {
            $listing->images()->create([
                'path' => ImageSanitizer::store($file, "listings/{$listing->id}"), // strips GPS/EXIF metadata
                'position' => ++$position,
            ]);
        }
    }

    private function removeImages(MedicineListing $listing, array $ids): void
    {
        // Only images that belong to this listing can be removed.
        $images = $listing->images()->whereIn('id', $ids)->get();
        Storage::disk('public')->delete($images->pluck('path')->all());
        $listing->images()->whereIn('id', $images->pluck('id'))->delete();
    }

    private function lower(string $label): string
    {
        return mb_strtolower(mb_substr($label, 0, 1)).mb_substr($label, 1);
    }
}
