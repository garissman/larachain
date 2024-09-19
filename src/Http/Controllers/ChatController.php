<?php

namespace Garissman\LaraChain\Http\Controllers;


use App\Http\Controllers\Controller;
use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Models\Agent;
use Garissman\LaraChain\Models\Chat;
use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Enums\ChatStatuesEnum;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Response;
use Inertia\ResponseFactory;
class ChatController extends Controller
{
    //
    public function index(?Chat $chat = null): Response|ResponseFactory
    {
        $messages = [];
        if ($chat) {
            $messages = Message::where('chat_id', $chat->id)
                ->orderBy('created_at', 'ASC')
                ->get();
        }

        return inertia('GuestChat/Chat', [
            'chats' => Chat::orderBy('updated_at', 'desc')
                ->with([
                    'messages' => fn($q) => $q->notSystem()
                        ->notTool()
                        ->latest()
                        ->first(),
                ])
                ->paginate(10),
            'chat' => $chat,
            'messages' => $messages,
        ]);
    }

    public function newChat(): RedirectResponse
    {
        $defaultAgent = Agent::where('is_default', true)->first();
        $chat = Chat::create([
            'agent_id' => $defaultAgent->id,
            'title' => $defaultAgent->description,
            'chat_driver' => config('larachain.driver'),
            'user_id' => auth()->user()?->id,
            'embedding_driver' => config('larachain.embedding_driver'),
        ]);

        return Redirect::route('guest.chats.index', ['chat' => $chat]);
    }

    public function chat(Request $request, Chat $chat): void
    {
        $validated = $request->validate([
            'input' => 'required',
        ]);

        $chat->update([
            'chat_status' => ChatStatuesEnum::InProgress->value,
        ]);
        LaraChain::handle(
            chat: $chat,
            prompt: $validated['input']
        );
        $chat->update([
            'chat_status' => ChatStatuesEnum::Complete->value,
        ]);
    }

    public function updateChat(Request $request, Chat $chat): RedirectResponse
    {
        $data = $request->validate([
            'chat_driver' => ['required', 'string'],
        ]);
        $chat->update($data);
        $chat->save();

        return back();
    }

    public function deleteChat(Chat $chat): RedirectResponse
    {
        $chat->delete();

        return Redirect::route('guest.chats.index');
    }
}
