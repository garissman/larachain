<?php

namespace Garissman\LaraChain\Engines;


use Garissman\LaraChain\Clients\OpenAiClient;
use Illuminate\Contracts\Container\Container;


class OpenAiEngine extends Engine
{
    public function __construct()
    {
        $this->client = new OpenAiClient();
    }
}
