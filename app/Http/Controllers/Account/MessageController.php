<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Support\SampleAccount;
use Illuminate\View\View;

/**
 * Private conversations.
 * Front-end part: sample data only. Sending is added in backend push B4.
 */
class MessageController extends Controller
{
    public function index(): View
    {
        return view('account.messages.index', [
            'conversations' => SampleAccount::conversations(),
            'current' => null,
            'usingSampleData' => true,
        ]);
    }

    public function show(int $conversation): View
    {
        $found = SampleAccount::findConversation($conversation);

        // Backend part: only the two participants may open a conversation.
        abort_if($found === null, 404);

        return view('account.messages.index', [
            'conversations' => SampleAccount::conversations(),
            'current' => $found,
            'usingSampleData' => true,
        ]);
    }
}
