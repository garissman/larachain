<?php

namespace Garissman\LaraChain\Engines;


use Garissman\LaraChain\Clients\AnythingLlmClient;
use Garissman\LaraChain\Clients\OllamaClient;

class AnythingLlmEngine extends Engine
{
    public function __construct()
    {
        $this->client = new AnythingLlmClient();
    }
}
