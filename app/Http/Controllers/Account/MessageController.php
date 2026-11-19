<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Private conversations. Only the two participants of a conversation can open it (403 for anyone else).
 */
class MessageController extends Controller
{
    public function __construct(private readonly MessagingService $messaging) {}

    public function index(Request $request): View
    {
        return view('account.messages.index', [
            'conversations' => $this->conversationsFor($request),
            'current' => null,
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);

        $this->messaging->markRead($conversation, $request->user());
        $conversation->load(['messages', 'reservation.listing.medicine', 'donor', 'requester']);

        return view('account.messages.index', [
            'conversations' => $this->conversationsFor($request),
            'current' => $conversation,
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $request->validate(['body' => ['required', 'string', 'max:'.MessagingService::MAX_LENGTH]], [], ['body' => 'message']);

        $message = $this->messaging->send($conversation, $request->user(), $request->string('body'));

        return $request->expectsJson()
            ? response()->json(['message' => $this->present($message, $request)], 201)
            : redirect()->route('messages.show', $conversation)->withFragment('latest');
    }

    /**
     * New messages since a given id, for the page's light auto-refresh (every 15 seconds).
     * Real-time push (WebSockets) is not set up; this keeps the conversation current without it.
     */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->hasParticipant($request->user()), 403);

        $after = max(0, (int) $request->query('after'));
        $messages = $conversation->messages()->where('id', '>', $after)->get();

        $this->messaging->markRead($conversation, $request->user());

        return response()->json([
            'messages' => $messages->map(fn (Message $m) => $this->present($m, $request))->values(),
            'open' => $conversation->load('reservation')->isOpen(),
        ]);
    }

    private function conversationsFor(Request $request)
    {
        $user = $request->user();

        return Conversation::for($user)
            ->with(['latestMessage', 'donor', 'requester', 'reservation.listing.medicine'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_id', '!=', $user->id)->whereNull('read_at')])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();
    }

    /** Only what the page needs. The body is escaped by the browser (textContent), never inserted as HTML. */
    private function present(Message $message, Request $request): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'mine' => $message->sender_id === $request->user()->id,
            'time' => $message->created_at->format('j M, g:i a'),
            'iso' => $message->created_at->toIso8601String(),
        ];
    }
}
