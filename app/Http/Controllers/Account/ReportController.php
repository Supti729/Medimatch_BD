<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\MedicineListing;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Users report a public listing, or a conversation they take part in.
 */
class ReportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'target_type' => ['required', Rule::in(['listing', 'conversation'])],
            'target_id' => ['required', 'integer'],
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);
        $user = $request->user();

        // Users can only report what they are allowed to see.
        if ($data['target_type'] === 'listing') {
            $target = MedicineListing::publiclyVisible()->findOrFail($data['target_id']);
            $reported = $target->user_id;
        } else {
            $target = Conversation::findOrFail($data['target_id']);
            abort_unless($target->hasParticipant($user), 404);
            $reported = $target->otherParticipant($user)->id;
        }

        abort_if($reported === $user->id, 422, 'You cannot report yourself.');

        $alreadyOpen = Report::where(['reporter_id' => $user->id, 'target_type' => $data['target_type'], 'target_id' => $target->id])
            ->whereIn('status', ['open', 'escalated'])->exists();
        if ($alreadyOpen) {
            return back()->with('status', 'You have already reported this. A moderator will look at it.');
        }

        $report = new Report;
        $report->forceFill([
            'reporter_id' => $user->id,
            'target_type' => $data['target_type'],
            'target_id' => $target->id,
            'reported_user_id' => $reported,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
        ])->save();

        AuditLog::record('report.created', $report, "New report #{$report->id} about a {$data['target_type']}");

        return back()->with('success', 'Thank you. Your report was sent to the moderators.');
    }
}
