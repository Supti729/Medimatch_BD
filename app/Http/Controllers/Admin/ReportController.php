<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Report;
use App\Services\ModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public const TABS = ['open' => ['open', 'escalated'], 'closed' => ['resolved', 'rejected'], 'all' => null];

    public function index(Request $request): View
    {
        $tab = array_key_exists((string) $request->query('tab'), self::TABS) ? $request->query('tab') : 'open';
        $counts = Report::toBase()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.reports.index', [
            'reports' => Report::with(['reporter', 'reportedUser'])
                ->when(self::TABS[$tab], fn ($q, $statuses) => $q->whereIn('status', $statuses))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'tab' => $tab,
            'counts' => collect(self::TABS)->map(fn ($statuses) => $statuses ? $counts->only($statuses)->sum() : $counts->sum()),
        ]);
    }

    public function show(Report $report): View
    {
        $target = $report->target();

        // Reading a reported conversation is sensitive: it is allowed for moderation, and logged.
        $messages = collect();
        if ($report->target_type === 'conversation' && $target) {
            AuditLog::record('report.evidence_viewed', $report, "Read the conversation reported in report #{$report->id}");
            $messages = $target->messages()->with('sender')->latest('id')->limit(30)->get()->reverse()->values();
        }

        return view('admin.reports.show', [
            'report' => $report->load(['reporter', 'reportedUser', 'handler']),
            'target' => $target,
            'messages' => $messages,
            'related' => Report::where('reported_user_id', $report->reported_user_id)->whereKeyNot($report->id)->latest()->get(),
            'audit' => AuditLog::with('actor')->where('subject_type', 'report')->where('subject_id', $report->id)->latest('id')->get(),
        ]);
    }

    public function decide(Request $request, Report $report, ModerationService $moderation): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['resolved', 'rejected', 'escalated'])],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $moderation->decideReport($report, $request->user(), ReportStatus::from($data['decision']), $data['note']);

        return back()->with('success', 'Decision recorded.');
    }
}
