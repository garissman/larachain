<?php

declare(strict_types=1);


namespace Garissman\LaraChain\Facades;


use Garissman\LaraChain\EngineManager;
use Garissman\LaraChain\Models\Chat;
use Illuminate\Support\Facades\Facade;


/**
 * @method static handle(Chat $chat, string $prompt,string $systemPrompt = '')
 * @method static EngineManager invoke()
 */
class LaraChain extends Facade
{

    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor(): string
    {
        return \Garissman\LaraChain\LaraChain::class;
    }
}
