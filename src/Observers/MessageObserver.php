<?php

namespace Garissman\LaraChain\Observers;


use Illuminate\Support\Facades\Broadcast;
use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Enums\RoleEnum;

class MessageObserver
{
    /**
     * Handle the Message "created" event.
     */
    public function created(Message $message): void
    {
        if ($message->role == RoleEnum::User || $message->role == RoleEnum::Assistant) {
            Broadcast::on('chat.'.$message->chat->id)
                ->as('update')
                ->with([
                    'message' => $message,
                ])
                ->send();
        }
    }

    /**
     * Handle the Message "updated" event.
     */
    public function updated(Message $message): void
    {
        if ($message->role == RoleEnum::User || $message->role == RoleEnum::Assistant) {
            Broadcast::on('chat.'.$message->chat->id)
                ->as('update')
                ->with([
                    'message' => $message,
                ])
                ->send();
        }
    }

    /**
     * Handle the Message "deleted" event.
     */
    public function deleted(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "restored" event.
     */
    public function restored(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "force deleted" event.
     */
    public function forceDeleted(Message $message): void
    {
        //
    }
}
