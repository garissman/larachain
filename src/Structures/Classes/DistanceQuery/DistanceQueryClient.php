<?php

namespace Garissman\LaraChain\Structures\Classes\DistanceQuery;

use Garissman\LaraChain\Structures\Classes\DistanceQuery\Drivers\Mock;
use Garissman\LaraChain\Structures\Classes\DistanceQuery\Drivers\PostGres;

class DistanceQueryClient
{
    protected array $drivers = [];

    public function driver($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        if (! isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    protected function createDriver($name): Mock|PostGres
    {
        return match ($name) {
            'mock' => new Mock(),
            'pgsql' => new PostGres(),
            default => throw new \InvalidArgumentException("Driver [{$name}] is not supported."),
        };
    }

    public function __call($method, $arguments)
    {
        return $this->driver()->$method(...$arguments);
    }

    protected function getDefaultDriver()
    {
        return config('larachain.distance_driver','pgsql');
    }
}
