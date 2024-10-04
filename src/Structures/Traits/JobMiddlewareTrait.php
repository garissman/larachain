<?php

namespace Garissman\LaraChain\Structures\Traits;

use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Structures\Interfaces\HasDrivers;
use Illuminate\Queue\Middleware\WithoutOverlapping;


trait JobMiddlewareTrait
{
    public function driverMiddleware(HasDrivers $hasDrivers): array
    {
        $defaults = [];

        /**
         * @NOTE
         * Basically, Ollama can only handle one job
         * at a time from what I can tell right now.
         * So this prevents many jobs hitting
         * it at once
         */
        if (LaraChain::engine($hasDrivers->getDriver())->isAsync()) {
            return $defaults;
        }

        return [
            (new WithoutOverlapping($hasDrivers->getDriver()))
                ->releaseAfter(30)
                ->expireAfter(600),
        ];
    }
}
