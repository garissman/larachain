<?php

namespace Garissman\LaraChain;

use Garissman\Larachain\Jobs\ProcessPendingResponse;
use Garissman\LaraChain\Models\Chat;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Bus;


class LaraChain
{

    public EngineManager $engine;

    public function __construct(private readonly Container $container)
    {
        $this->engine = new EngineManager($this->container);
    }

    public function invoke(): EngineManager
    {
        return $this->engine;
    }

    public function handle(Chat $chat, string $prompt, string $systemPrompt = ''): void
    {
        $chat->addInput(
            message: $prompt,
            systemPrompt: $systemPrompt,
        );
        Bus::batch([new ProcessPendingResponse($chat, $this)])
            ->name("LaraChain Orchestrate Chain Chat - {$chat->id}")
            ->allowFailures()
            ->dispatch();
    }
}
