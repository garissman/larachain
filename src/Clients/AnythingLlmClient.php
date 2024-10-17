<?php

namespace Garissman\LaraChain\Clients;

use Exception;
use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\MessageInDto;
use Garissman\LaraChain\Structures\Classes\Responses\AnythingChatCompletionResponse;
use Garissman\LaraChain\Structures\Classes\Responses\CompletionResponse;
use Garissman\LaraChain\Structures\Classes\Responses\EmbeddingsResponseDto;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnythingLlmClient extends BaseClient
{
    protected string $driver = 'ollama';

    /**
     * @param MessageInDto[] $messages
     *
     * @throws ConnectionException
     * @throws Exception
     */
    public function chat(array $messages, ?Message $message = null): CompletionResponse
    {
        $messages = $this->remapMessages($messages);
        $stream = config('larachain.drivers.anything_llm.stream', false);
        $workspace = config('larachain.drivers.anything_llm.workspace', 'default');
        $mode = config('larachain.drivers.anything_llm.mode', 'chat');
        $thread = $message->chat->metadata['anything_llm']['thread']['slug'];
        $payload = [
            'message' => $messages[count($messages) - 1]['content'],
            'mode' => $mode
        ];
        $url = "api/v1/workspace/" . $workspace . "/thread/" . $thread . "/";
        if ($stream) {
            $url.="stream-chat";
        }else{
            $url.="chat";
        }
        $payload = $this->modifyPayload($payload);

        $response = $this->getClient()->post($url, $payload);
        if ($stream) {
            $return = $this->streamOutput($response->getBody(), $message);
        } else {
            $return = $response->json();
            if ($message) {
                $message->body = $return['textResponse'];
                $message->is_been_whisper = false;
                $message->save();
            }
        }
        $return['assistanceMessage'] = $message;
        return AnythingChatCompletionResponse::from($return);

    }
    public function streamOutput($body, ?Message $message = null): array
    {
        $return = [];
        $content = '';
        $response = '';
        $tool_call = false;
        while (!$body->eof()) {
            $response .= $body->getContents();
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                $line = $this->processStreamLine($line);
                $line = json_decode($line, true);
                if ($line) {

                    $return = $line;
                    if (isset($line['textResponse'])) {
                        $content .= $line['textResponse'];
                        if (
                            $line['textResponse'] == '[TOOL_CALLS]'
                            || str_contains($content, '{"name":')
                        ) {
                            $tool_call = true;
                        }
                    }
                    if (!$tool_call) {
                        if ($message) {
                            $message->body = $content;
                        }
                    } else {
                        if ($message) {
                            $message->body = '';
                        }
                    }
                    if ($message) {
                        $message->save();
                    }
                    $response = '';
                }
            }
        }
        if ($tool_call) {
            $return['textResponse'] = '';
            $tool = json_decode(trim(str_replace('[TOOL_CALLS]', '', $content)), true);
            if (str_contains(config('larachain.drivers.ollama.models.chat_model'), 'llama')) {
                $tool['arguments'] = $tool['parameters'];
                $tool = [$tool];
            }
            $return['tool_calls'] = $tool;
            if ($message) {
                $message->body = '';
            }
//            $message->role =  RoleEnum::Tool;
        } else {
            $return['textResponse'] = $content;
            if ($message) {
                $message->is_been_whisper = false;
                $message->body = $content;
            }
        }
        if ($message) {
            $message->save();
        }
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
        $api_token = config('larachain.drivers.anything_llm.api_key');
        $baseUrl = config('larachain.drivers.anything_llm.api_url');

        if (!$api_token || !$baseUrl) {
            throw new Exception('Anything LLM API Base URL or Token not found');
        }

        return Http::withHeaders([
            'content-type' => 'application/json',
        ])
            ->withToken($api_token)
            ->baseUrl($baseUrl);
    }

    /**
     * @return array|mixed
     * @throws ConnectionException
     * @throws Exception
     */
    public function createChat(): mixed
    {
        $workspace = config('larachain.drivers.anything_llm.workspace', false);
        $response = $this->getClient()
            ->post("api/v1/workspace/" . $workspace . "/thread/new");
        return $response->json();
    }

    /**
     * @param string $thread
     * @return array|mixed
     * @throws ConnectionException
     * @throws Exception
     */
    public function deleteChat(string $thread): mixed
    {
        $workspace = config('larachain.drivers.anything_llm.workspace', false);
        $response = $this->getClient()
            ->delete("api/v1/workspace/" . $workspace . "/thread/" . $thread);
        return $response->json();
    }

    public function processStreamLine(string $line): string
    {
        return trim(str_replace("data:","",$line));
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
        $stream = config('larachain.drivers.ollama.stream', false);
        $response = $this->getClient()->post('/generate', [
            'model' => config('larachain.drivers.ollama.models.completion_model'),
            'prompt' => $prompt,
            'stream' => $stream,
        ]);
        if ($stream) {
            $return = $this->streamOutputCompletion($response->getBody());
        } else {
            $return = $response->json();
        }
        /**
         * @see https://github.com/ollama/ollama/blob/main/docs/api.md#generate-a-chat-completion
         */
        $results = $return['response'];

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
