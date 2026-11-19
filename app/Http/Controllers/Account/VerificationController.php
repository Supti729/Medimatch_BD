<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\MedicineRequest;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

/**
 * The user's Verification Center: upload a prescription for a request and follow its review.
 */
class VerificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('account.verification', [
            'submissions' => $user->verificationSubmissions()->with(['request.medicine', 'events'])->latest()->get(),
            // Open prescription-medicine requests that do not already have a submission in progress or accepted
            'requests' => $user->medicineRequests()->open()->with('medicine')
                ->whereHas('medicine', fn ($q) => $q->where('requires_prescription', true))
                ->whereDoesntHave('verifications', fn ($q) => $q->whereIn('status', ['submitted', 'under_review', 'verified']))
                ->latest()->get(),
        ]);
    }

    public function store(Request $request, VerificationService $verification): RedirectResponse
    {
        $data = $request->validate([
            'medicine_request_id' => ['required', 'integer'],
            'document' => ['required', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max(5 * 1024)],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Please agree that an authorized reviewer may view the document.',
        ], ['document' => 'prescription']);

        // findOrFail on the user's own requests: someone else's request id gives 404, not a hint that it exists.
        $medicineRequest = $request->user()->medicineRequests()->findOrFail($data['medicine_request_id']);

        $verification->submit($medicineRequest, $request->user(), $request->file('document'));

        return back()->with('success', 'Your prescription was uploaded. An authorized reviewer will check it.');
    }
}
