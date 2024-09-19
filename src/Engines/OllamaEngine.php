<?php

namespace Garissman\LaraChain\Engines;


use Garissman\LaraChain\Clients\OllamaClient;
use Garissman\LaraChain\Models\Chat;

class OllamaEngine extends Engine
{
    public function __construct()
    {
        $this->client = new OllamaClient();
    }


}
