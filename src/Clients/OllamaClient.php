<?php

namespace Garissman\LaraChain\Clients;

use Exception;
use Garissman\LaraChain\Structures\Classes\MessageInDto;
use Garissman\LaraChain\Structures\Classes\Responses\CompletionResponse;
use Garissman\LaraChain\Structures\Classes\Responses\EmbeddingsResponseDto;
use Garissman\LaraChain\Structures\Classes\Responses\OllamaChatCompletionResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaClient extends BaseClient
{
    protected string $driver = 'ollama';

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function embedData(string $prompt): EmbeddingsResponseDto
    {
        Log::info('LlmDriver::Ollama::embedData');

        $response = $this->getClient()->post('/embeddings', [
            'model' => config('larachain.drivers.ollama.models.embedding_model'),
            'prompt' => $prompt,
        ]);

        $results = $response->json();

        return EmbeddingsResponseDto::from([
            'embedding' => data_get($results, 'embedding'),
            'token_count' => 1000,
        ]);
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

    public function addJsonFormat(array $payload): array
    {
        //@NOTE Just too hard if it is an array of objects
        //$payload['format'] = 'json';
        return $payload;
    }

    public function functionPromptChat(array $messages, array $only = []): array
    {
//        $messages = $this->insertFunctionsIntoMessageArray($messages);
        $response = $this->getClient()->post('/chat', [
            'model' => config('larachain.drivers.ollama.models.completion_model'),
            'messages' => $messages,
            'format' => 'json',
            'stream' => false,
        ]);
        $results = $response->json()['message']['content'];
        $functionsFromResults = json_decode($results, true);
        $functions = []; //reset this
        if ($functionsFromResults) {
            if (
                array_key_exists('arguments', $functionsFromResults) &&
                array_key_exists('name', $functionsFromResults) &&
                data_get($functionsFromResults, 'name') !== 'search_and_summarize') {
                $functions[] = $functionsFromResults;
            }
        }


        return $functions;
    }

    /**
     * @param MessageInDto[] $messages
     *
     * @throws ConnectionException
     * @throws Exception
     */
    public function chat(array $messages): CompletionResponse
    {
        $messages = $this->remapMessages($messages);
        $stream = config('larachain.drivers.ollama.stream', false);
        $payload = [
            'model' => config('larachain.drivers.ollama.models.completion_model'),
            'messages' => $messages,
            'stream' => $stream,
            'options' => [
                'temperature' => 0,
            ],
        ];
        $payload = $this->modifyPayload($payload);
        $response = $this->getClient()->post('/chat', $payload);
        if ($stream) {
            $return = $this->streamOutput($response->getBody());
        } else {
            $return = $response->json();
        }

        return OllamaChatCompletionResponse::from($return);
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

    public function streamOutput($body)
    {
        $latestMessage = $this->chat->messages()->latest()->first();
        $return = [];
        $content = '';
        $response = '';
        $tool_call = false;
        while (!$body->eof()) {
            $response .= $body->getContents();
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                $line = json_decode($line, true);
                if ($line) {
                    Broadcast::on('chat.' . $this->chat->id)
                        ->as('AiWhispering')
                        ->with(['content' => $content])
                        ->send();
                    $return = $line;
                    $content .= $line['message']['content'];
                    if ($line['message']['content'] == '[TOOL_CALLS]') {
                        $tool_call = true;
                    }
                    if (!$tool_call) {
                        Broadcast::on('chat.' . $this->chat->id)
                            ->as('AiStreaming')
                            ->with([
                                'last_message' => $latestMessage->id,
                                'stream' => $line,
                            ])
                            ->send();
                    }
                    $response = '';
                }
            }
        }
        if ($tool_call) {
            $return['message']['content'] = '';
            $tool = json_decode(trim(str_replace('[TOOL_CALLS]', '', $content)), true);
            $return['message']['tool_calls'] = $tool;
        } else {
            $return['message']['content'] = $content;
        }

        return $return;
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
