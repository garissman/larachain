<?php

namespace Garissman\LaraChain;

use Exception;
use Garissman\Clerk\Engines\NullEngine;
use Garissman\Clerk\Engines\WitEngine;
use Garissman\Wit\Client;
use Garissman\Wit\Wit;
use Illuminate\Support\Manager;
class EngineManager extends Manager
{
    /**
     * Get a driver instance.
     *
     * @param string|null $name
     * @return WitEngine|NullEngine
     */
    public function engine(string $name = null): NullEngine|WitEngine
    {
        return $this->driver($name);
    }

    /**
     * Create Wit engine instance.
     *
     * @return WitEngine
     *
     * @throws Exception
     */
    public function createWitDriver() : WitEngine
    {
        $this->ensureWitClientIsInstalled();
        if (!config('clerk.drivers.wit.api_token',false)) {
            throw new Exception('Wit API Token is empty');
        }
        return new WitEngine(
            new Wit(
                new Client(
                    config('clerk.drivers.wit.api_token'),
                    config('clerk.drivers.wit.api_version')
                )
            )
        );
    }

    /**
     * Ensure the Algolia API client is installed.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function ensureWitClientIsInstalled(): void
    {
        if (class_exists(Wit::class)) {
            return;
        }

        throw new Exception('Please install the suggested Wit client: garissman/wit.');
    }

    /**
     * Create a null engine instance.
     *
     * @return NullEngine
     */
    public function createNullDriver(): NullEngine
    {
        return new NullEngine;
    }

    /**
     * Forget all of the resolved engine instances.
     *
     * @return $this
     */
    public function forgetEngines(): static
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Get the default Scout driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        if (is_null($driver = config('clerk.driver'))) {
            return 'null';
        }

        return $driver;
    }
}
