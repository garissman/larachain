<?php

namespace Garissman\LaraChain\Engines;


use Garissman\LaraChain\Clients\OpenAiClient;


class OpenAiEngine extends Engine
{
    public function __construct()
    {
        $this->client = new OpenAiClient();
    }
}
