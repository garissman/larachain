<?php

namespace Garissman\LaraChain;

use Garissman\LaraChain\Engines\NullEngine;
use Garissman\LaraChain\Engines\OllamaEngine;
use Garissman\LaraChain\Engines\OpenAiEngine;
use Garissman\LaraChain\Jobs\ProcessPendingResponse;
use Garissman\LaraChain\Models\Chat;
use Garissman\LaraChain\Structures\Enums\ChatStatuesEnum;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Bus;


class LaraChain
{

    public OllamaEngine|NullEngine|OpenAiEngine $engine;

    public function __construct(private readonly Container $container)
    {
//        $this->engine = (new EngineManager($this->container))->engine();
    }

    public function invoke(Chat $chat): OllamaEngine|NullEngine|OpenAiEngine
    {
        return (new EngineManager($this->container))
            ->engine($chat->chat_driver->value)
            ->setChat($chat);
    }

    /**
     * @throws \Throwable
     */
    public function handle(Chat $chat, string $prompt, string $systemPrompt = ''): Batch
    {

        $chat->addInput(
            message: $prompt,
            systemPrompt: $systemPrompt,
        );
        $chat->update([
            'chat_status' => ChatStatuesEnum::InProgress->value,
        ]);
        return Bus::batch([new ProcessPendingResponse($chat)])
            ->name("LaraChain Orchestrate Chain Chat - {$chat->id}")
            ->finally(function (Batch $batch) use ($chat) {
                $chat->update([
                    'chat_status' => ChatStatuesEnum::Complete->value,
                ]);
            })
            ->allowFailures()
            ->dispatch();
    }
}
