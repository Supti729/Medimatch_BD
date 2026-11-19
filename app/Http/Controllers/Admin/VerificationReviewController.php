<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\VerificationSubmission;
use App\Services\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Prescription review. Open to administrators and authorized reviewers only (route middleware).
 */
class VerificationReviewController extends Controller
{
    public const TABS = ['waiting' => ['submitted', 'under_review'], 'decided' => ['verified', 'rejected', 'reupload_required'], 'all' => null];

    public function __construct(private readonly VerificationService $verification) {}

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'waiting';
        $counts = VerificationSubmission::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.verifications.index', [
            'submissions' => VerificationSubmission::with(['user', 'reviewer', 'request.medicine'])
                ->when(self::TABS[$tab], fn ($q, $statuses) => $q->whereIn('status', $statuses))
                ->oldest()
                ->paginate(20)
                ->withQueryString(),
            'tab' => $tab,
            'counts' => collect(self::TABS)->map(fn ($statuses) => $statuses ? $counts->only($statuses)->sum() : $counts->sum()),
        ]);
    }

    public function show(Request $request, VerificationSubmission $verification): View
    {
        // Opening a waiting submission assigns it to this reviewer.
        $this->verification->startReview($verification, $request->user());

        return view('admin.verifications.show', [
            'submission' => $verification->fresh(['user', 'reviewer', 'request.medicine', 'request.district', 'events.actor']),
            'audit' => AuditLog::with('actor')->where('subject_type', 'verification_submission')->where('subject_id', $verification->id)->latest('id')->get(),
        ]);
    }

    public function document(VerificationSubmission $verification): StreamedResponse
    {
        return $this->verification->document($verification);
    }

    public function decide(Request $request, VerificationSubmission $verification): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(array_map(fn ($s) => $s->value, VerificationStatus::decisions()))],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->verification->decide($verification, $request->user(), VerificationStatus::from($data['decision']), $data['note']);

        return back()->with('success', 'Decision recorded. The user was notified.');
    }
}
