<?php

namespace Garissman\LaraChain;

use Garissman\LaraChain\Engines\AnythingLlmEngine;
use Garissman\LaraChain\Engines\NullEngine;
use Garissman\LaraChain\Engines\OllamaEngine;
use Garissman\LaraChain\Engines\OpenAiEngine;
use Garissman\LaraChain\Jobs\ProcessPendingResponse;
use Garissman\LaraChain\Models\Chat;
use Garissman\LaraChain\Structures\Classes\MetaDataDto;
use Garissman\LaraChain\Structures\Enums\ChatStatuesEnum;
use Garissman\LaraChain\Structures\Enums\DriversEnum;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Bus;
use Throwable;


class LaraChain
{

    public OllamaEngine|NullEngine|OpenAiEngine $engine;

    public function __construct(private readonly Container $container)
    {
    }

    public function invoke(Chat $chat): OllamaEngine|NullEngine|OpenAiEngine|AnythingLlmEngine
    {
        return (new EngineManager($this->container))
            ->engine($chat->chat_driver->value)
            ->setChat($chat);
    }

    public function engine(DriversEnum $driver): OllamaEngine|NullEngine|OpenAiEngine|AnythingLlmEngine
    {
        return (new EngineManager($this->container))
            ->engine($driver->value);
    }

    function removeAscii($string): string
    {
        return str_replace("\u2019", ' ', preg_replace('/[^\x00-\x7F]+/', '', $string));
    }

    /**
     * @throws Throwable
     */
    public function handle(Chat $chat, string $prompt, string $systemPrompt = '', ?MetaDataDto $meta_data = null): Batch
    {

        $chat->addInput(
            message: $prompt,
            systemPrompt: $systemPrompt,
            meta_data: $meta_data
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
