<?php

namespace Garissman\LaraChain\Engines;


use Garissman\LaraChain\Clients\OllamaClient;

class OllamaEngine extends Engine
{
    public function __construct()
    {
        $this->client = new OllamaClient();
    }


}
