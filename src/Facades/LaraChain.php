<?php

declare(strict_types=1);


namespace Garissman\LaraChain\Facades;


use Garissman\LaraChain\Engines\NullEngine;
use Garissman\LaraChain\Engines\OllamaEngine;
use Garissman\LaraChain\Engines\OpenAiEngine;
use Garissman\LaraChain\Models\Chat;
use Illuminate\Support\Facades\Facade;


/**
 * @method static handle(Chat $chat, string $prompt, string $systemPrompt = '')
 * @method static OllamaEngine|NullEngine|OpenAiEngine invoke(Chat $chat)
 * @method static OllamaEngine|NullEngine|OpenAiEngine engine(string $engine)
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
