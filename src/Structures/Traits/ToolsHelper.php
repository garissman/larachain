<?php

namespace Garissman\LaraChain\Structures\Traits;


use Garissman\LaraChain\Models\Message;

trait ToolsHelper
{
    protected function addToolsToMessage(Message $message, FunctionCallDto $functionDto): Message
    {
        $tools = $message->tools;
        if (! $tools) {
            $tools = ToolsDto::from(['tools' => []]);
        }
        $tools->tools[] = $functionDto;
        $message->updateQuietly(['tools' => $tools]);

        return $message->refresh();
    }

    protected function savePromptHistory(Message $message, string $prompt): void
    {
        PromptHistory::create([
            'prompt' => $prompt,
            'chat_id' => $message->getChat()->id,
            'message_id' => $message?->id,
            /** @phpstan-ignore-next-line */
            'collection_id' => $message->getChatable()?->id,
        ]);
    }
}
