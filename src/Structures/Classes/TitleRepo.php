<?php

namespace Garissman\LaraChain\Structures\Classes;


use Models\Chat;
use Models\Message;
use Structures\Enums\RoleEnum;

class TitleRepo
{
    public function updateAllTitles(): void
    {
        foreach (Chat::whereNull('title')
            ->get() as $chat) {
            $message = $chat->messages()->where('role', RoleEnum::User)->first();
            if ($message) {
                $this->handle($message);
            }
        }
    }

    public function handle(Message $message): void
    {
        if ($message->role != RoleEnum::User) {
            return;
        }

        if (! is_null($message->chat->title)) {
            return;
        }

        $chat = Chat::find($message->chat_id);
        $chat->title = str($message->body)->limit(125)->toString();
        $chat->save();

    }
}
