<?php

namespace Garissman\LaraChain\Clients;

use Exception;
use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\MessageInDto;
use Garissman\LaraChain\Structures\Classes\Responses\CompletionResponse;
use Garissman\LaraChain\Structures\Classes\Responses\EmbeddingsResponseDto;
use Garissman\LaraChain\Structures\Classes\Responses\OllamaChatCompletionResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaClient extends BaseClient
{
    protected string $driver = 'ollama';

    /**
     * @param MessageInDto[] $messages
     *
     * @throws ConnectionException
     * @throws Exception
     */
    public function chat(array $messages, ?Message $message=null): CompletionResponse
    {
        $messages = $this->remapMessages($messages);
        $stream = config('larachain.drivers.ollama.stream', false);
        $payload = [
            'model' => config('larachain.drivers.ollama.models.chat_model'),
            'messages' => $messages,
            'stream' => $stream,
            'options' => [
                'temperature' => 0,
            ],
        ];
        $payload = $this->modifyPayload($payload);
        $response = $this->getClient()->post('/chat', $payload);
        if ($stream) {
            $return = $this->streamOutput($response->getBody(), $message);
        } else {
            $return = $response->json();
            if ($message) {
                $message->body = $return['message']['content'];
                $message->is_been_whisper = false;
                $message->save();
            }
        }
        $return['assistanceMessage'] = $message;
        $return = OllamaChatCompletionResponse::from($return);
        return $return;

    }

    /**
     * @param MessageInDto[] $messages
     */
    public function remapMessages(array $messages): array
    {
        return collect($messages)->transform(function (MessageInDto $message): array {
            return collect($message->toArray())
                ->only(['content', 'role', 'tool_calls', 'tool_used', 'input_tokens', 'output_tokens', 'model'])
                ->toArray();
        })->toArray();
    }

    protected function getClient(): PendingRequest
    {
        $api_token = config('larachain.drivers.ollama.api_key');
        $baseUrl = config('larachain.drivers.ollama.api_url');

        if (!$api_token || !$baseUrl) {
            throw new Exception('Ollama API Base URL or Token not found');
        }

        return Http::withHeaders([
            'content-type' => 'application/json',
        ])
            ->retry(3, 6000)
            ->timeout(120)
            ->baseUrl($baseUrl);
    }

    public function processStreamLine(string $line): string
    {
        return $line;
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function embedData(string $prompt): EmbeddingsResponseDto
    {
        $response = $this->getClient()
            ->post('/embeddings', [
                'model' => config('larachain.drivers.ollama.models.embedding_model'),
                'prompt' => $prompt,
            ]);

        $results = $response->json();
        return EmbeddingsResponseDto::from([
            'embedding' => data_get($results, 'embedding'),
            'token_count' => 1000,
        ]);
    }

    public function addJsonFormat(array $payload): array
    {
        //@NOTE Just too hard if it is an array of objects
        //$payload['format'] = 'json';
        return $payload;
    }


    /**
     * @return CompletionResponse[]
     *
     * @throws Exception
     */
    public function completionPool(array $prompts, int $temperature = 0): array
    {
        Log::info('LlmDriver::Ollama::completionPool');
        $baseUrl = config('larachain.drivers.ollama.api_url');

        if (!$baseUrl) {
            throw new Exception('Ollama API Base URL or Token not found');
        }

        $model = config('larachain.drivers.ollama.models.completion_model');
        $responses = Http::pool(function (Pool $pool) use (
            $prompts,
            $model,
            $baseUrl
        ) {
            foreach ($prompts as $prompt) {
                $payload = [
                    'model' => $model,
                    'prompt' => $prompt,
                    'stream' => config('larachain.drivers.ollama.stream', false),
                ];
                $payload = $this->modifyPayload($payload);
                $pool->withHeaders([
                    'content-type' => 'application/json',
                ])->timeout(300)
                    ->baseUrl($baseUrl)
                    ->post('/generate', $payload);
            }
        });

        $results = [];

        foreach ($responses as $index => $response) {
            if ($response->ok()) {
                $results[] = CompletionResponse::from([
                    'content' => $response->json()['response'],
                ]);
            } else {
                Log::error('Ollama API Error ', [
                    'index' => $index,
                    'error' => $response->body(),
                ]);
            }
        }

        return $results;
    }

    /**
     * @throws ConnectionException
     */
    public function completion(string $prompt): CompletionResponse
    {
        Log::info('LlmDriver::Ollama::completion');

        $response = $this->getClient()->post('/generate', [
            'model' => config('larachain.drivers.ollama.models.completion_model'),
            'prompt' => $prompt,
            'stream' => false,
        ]);

        /**
         * @see https://github.com/ollama/ollama/blob/main/docs/api.md#generate-a-chat-completion
         */
        $results = $response->json()['response'];

        return CompletionResponse::from([
            'content' => $results,
            'stop_reason' => 'stop',
        ]);
    }

    public function getFunctions(): array
    {
        $functions = parent::getFunctions();
        return $this->remapFunctions($functions);
    }

    public function remapFunctions(array $functions): array
    {
        return collect($functions)->map(function ($function) {
            $properties = [];
            $required = [];

            foreach (data_get($function, 'parameters.properties', []) as $property) {
                $name = data_get($property, 'name');

                if (data_get($property, 'required', false)) {
                    $required[] = $name;
                }

                $properties[$name] = [
                    'description' => data_get($property, 'description', null),
                    'type' => data_get($property, 'type', 'string'),
                    'default' => data_get($property, 'default', null),
                ];
            }

            return [
                'type' => 'function',
                'function' => [
                    'name' => data_get($function, 'name'),
                    'description' => data_get($function, 'description'),
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $properties,
                        'required' => $required,
                    ],
                ],

            ];

        })->values()->toArray();
    }

    public function isAsync(): bool
    {
        return false;
    }

    public function onQueue(): string
    {
        return 'ollama';
    }
}
