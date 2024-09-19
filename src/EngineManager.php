<?php

namespace Garissman\LaraChain;


use Exception;
use Garissman\LaraChain\Engines\NullEngine;
use Garissman\LaraChain\Engines\OllamaEngine;
use Garissman\LaraChain\Engines\OpenAiEngine;
use Illuminate\Support\Manager;
use OpenAI\Laravel\Facades\OpenAI;

class EngineManager extends Manager
{
    /**
     * Get a driver instance.
     *
     * @param string|null $name
     * @return NullEngine|OllamaEngine|OpenAiEngine
     */
    public function engine(string $name = null): NullEngine|OllamaEngine|OpenAiEngine
    {
        return $this->driver($name);
    }

    /**
     * Create Ollama engine instance.
     *
     * @return OllamaEngine
     *
     * @throws Exception
     */
    public function createOllamaDriver(): OllamaEngine
    {
        return new OllamaEngine();
    }

    /**
     * Create OpenAi engine instance.
     *
     * @return OpenAiEngine
     *
     * @throws Exception
     */
    public function createOpenAiDriver(): OpenAiEngine
    {
        if (!config('larachain.drivers.open_ai.api_token', false)) {
            throw new Exception('OpenAi API Token is empty');
        }

        return new OpenAiEngine();
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
     * Forget all the resolved engine instances.
     *
     * @return $this
     */
    public function forgetEngines(): static
    {
        $this->drivers = [];

        return $this;
    }

    /**
     * Get the default LaraChain driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        if (is_null($driver = config('larachain.driver'))) {
            return 'null';
        }

        return $driver;
    }

    protected function ensureWitClientIsInstalled(): void
    {
        if (class_exists(OpenAi::class)) {
            return;
        }

        throw new Exception('Please install the suggested OpenAI client: openai-php/laravel.');
    }
}
