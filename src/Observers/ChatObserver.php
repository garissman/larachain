<?php

namespace Garissman\LaraChain\Observers;


use Garissman\LaraChain\Clients\AnythingLlmClient;
use Garissman\LaraChain\Models\Chat;
use Garissman\LaraChain\Structures\Enums\DriversEnum;
use Illuminate\Http\Client\ConnectionException;

class ChatObserver
{
    /**
     * Handle the Message "created" event.
     * @throws ConnectionException
     */
    public function created(Chat $chat): void
    {
        if ($chat->chat_driver->value == DriversEnum::AnythingLln->value) {
            $thread = (new AnythingLlmClient())->createChat();
            $metadata = $chat->metadata;
            $metadata['anything_llm'] = ['thread' => $thread['thread']];
            $chat->metadata = $metadata;
            $chat->saveQuietly();
        }

    }

    /**
     * Handle the Message "updated" event.
     */
    public function updated(Chat $chat): void
    {
        if ($chat->chat_driver == DriversEnum::AnythingLln->value) {
            if (!isset($chat->metadata['anything_llm']['thread'])) {
                $thread = (new AnythingLlmClient())->createChat();
                $metadata = $chat->metadata;
                $metadata['anything_llm'] = ['thread' => $thread];
                $chat->metadata = $metadata;
                $chat->saveQuietly();
            }
        }
    }

    /**
     * Handle the Message "deleted" event.
     * @throws ConnectionException
     */
    public function deleted(Chat $chat): void
    {
        if ($chat->chat_driver == DriversEnum::AnythingLln) {
            if (isset($chat->metadata['anything_llm']['thread']['slug'])) {
                (new AnythingLlmClient())->deleteChat($chat->metadata['anything_llm']['thread']['slug']);
                $metadata = $chat->metadata;
                $metadata['anything_llm'] = ['thread' => null];
                $chat->metadata = $metadata;
                $chat->saveQuietly();
            }
        }
    }

    /**
     * Handle the Message "restored" event.
     */
    public function restored(Chat $chat): void
    {
        //
    }

    /**
     * Handle the Message "force deleted" event.
     */
    public function forceDeleted(Chat $chat): void
    {
        //
    }
}
