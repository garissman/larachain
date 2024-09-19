<?php

namespace Garissman\LaraChain\Clients;

use Garissman\LaraChain\Functions\FunctionDto;
use Garissman\LaraChain\Structures\Classes\MessageInDto;
use Garissman\LaraChain\Structures\Classes\Responses\CompletionResponse;
use Garissman\LaraChain\Structures\Classes\Responses\EmbeddingsResponseDto;
use Garissman\LaraChain\Structures\Classes\Responses\OpenAiChatCompletionResponse;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
class OpenAiClient extends BaseClient
{
    protected string $baseUrl = 'https://api.openai.com/v1';

    protected string $driver = 'openai';

    public function chat(array $messages): CompletionResponse
    {
        $token = config("larachain.drivers.openai.api_key");

        if (is_null($token)) {
            throw new \Exception('Missing open ai api key');
        }

        $payload = [
            'model' => config('larachain.drivers.openai.models.chat_model'),
            'messages' => $this->messagesToArray($messages),
        ];

        $payload = $this->modifyPayload($payload);
        unset($payload['system']);
        $response = Http::withHeaders([
            'Content-type' => 'application/json',
        ])
            ->withToken($token)
            ->baseUrl($this->baseUrl)
            ->timeout(240)
            ->post('/chat/completions', $payload);

        if ($response->failed()) {
            $body = json_decode($response->body(), true);
            Log::error('OpenAi API Error ', [
                'error' => $response->body(),
            ]);

            throw new \Exception('OpenAi API Error Chat '.$response->getStatusCode());
        }

        $json = $response->json();

        return OpenAiChatCompletionResponse::from($json);
    }

    protected function messagesToArray(array $messages): array
    {
        return collect($messages)->map(function ($message) {
            if (! is_array($message)) {
                $message = $message->toArray();
            }
            if ($message['role'] == 'tool') {
                $message['role'] = 'function';
                $message['name'] = $message['tool'];
            }
            return $message;
        })->toArray();
    }

    public function embedData(string $data): EmbeddingsResponseDto
    {

        $response = OpenAI::embeddings()->create([
            'model' => $this->getConfig('openai')['models']['embedding_model'],
            'input' => $data,
        ]);

        $results = [];

        foreach ($response->embeddings as $embedding) {
            $results = $embedding->embedding; // [0.018990106880664825, -0.0073809814639389515, ...]
        }

        return EmbeddingsResponseDto::from([
            'embedding' => $results,
            'token_count' => $response->usage->totalTokens,
        ]);
    }

    /**
     * @return CompletionResponse[]
     *
     * @throws \Exception
     */
    public function completionPool(array $prompts, int $temperature = 0): array
    {
        $token = config('larachain.drivers.openai.api_key');

        if (is_null($token)) {
            throw new \Exception('Missing open ai api key');
        }

        $responses = Http::pool(function (Pool $pool) use ($prompts, $token) {

            foreach ($prompts as $prompt) {
                $payload = [
                    'model' => config('larachain.drivers.openai.models.completion_model'),
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ];

                $payload = $this->modifyPayload($payload);

                $pool->withHeaders([
                    'content-type' => 'application/json',
                    'Authorization' => 'Bearer '.$token,
                ])->withToken($token)
                    ->baseUrl($this->baseUrl)
                    ->timeout(240)
                    ->retry(3, function (int $attempt, \Exception $exception) {
                        Log::info('OpenAi API Error going to retry', [
                            'attempt' => $attempt,
                            'error' => $exception->getMessage(),
                        ]);

                        return 60000;
                    })
                    ->post('/chat/completions', $payload);
            }

        });

        $results = [];

        foreach ($responses as $index => $response) {
            if ($response->ok()) {
                [$data, $tool_used, $stop_reason] = $this->getContentAndToolTypeFromResults($response);
                $results[] = CompletionResponse::from([
                    'content' => $data,
                    'tool_used' => $tool_used,
                    'stop_reason' => $stop_reason,
                    'input_tokens' => data_get($response, 'usage.prompt_tokens', null),
                    'output_tokens' => data_get($response, 'usage.completion_tokens', null),
                ]);
            } else {
                Log::error('OpenAi API Error ', [
                    'index' => $index,
                    'error' => $response->body(),
                ]);
            }
        }

        return $results;
    }

    public function completion(string $prompt, int $temperature = 0): CompletionResponse
    {
        $token = config('larachain.drivers.openai.api_key');

        if (is_null($token)) {
            throw new \Exception('Missing open ai api key');
        }

        $payload = [
            'model' => config('larachain.drivers.openai.models.completion_model'),
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ];

        $payload = $this->modifyPayload($payload);

        $response = Http::withHeaders([
            'Content-type' => 'application/json',
        ])
            ->withToken($token)
            ->baseUrl($this->baseUrl)
            ->timeout(240)
            ->retry(3, function (int $attempt, \Exception $exception) {
                Log::info('OpenAi API Error going to retry', [
                    'attempt' => $attempt,
                    'error' => $exception->getMessage(),
                ]);

                return 60000;
            })
            ->post('/chat/completions', $payload);

        if ($response->failed()) {
            Log::error('OpenAi API Error ', [
                'error' => $response->body(),
            ]);

            throw new \Exception('OpenAi API Error Chat');
        }

        [$data, $tool_used, $stop_reason] = $this->getContentAndToolTypeFromResults($response);

        return CompletionResponse::from([
            'content' => $data,
            'tool_used' => $tool_used,
            'stop_reason' => $stop_reason,
            'input_tokens' => data_get($response, 'usage.prompt_tokens', null),
            'output_tokens' => data_get($response, 'usage.completion_tokens', null),
        ]);
    }

    public function getContentAndToolTypeFromResults($json): array
    {
        $results = $json;
        $tool_used = '';
        $data = null;
        $stop_reason = data_get($results, 'choices.0.finish_reason', 'stop');
        $tool_calls = data_get($results, 'choices.0.message.tool_calls', []);

        if ($stop_reason === 'tool_calls' || ! empty($tool_calls)) {
            /**
             * @TOOD
             * The tool should be used here to get the
             * output since it might be different
             * for each tool
             * Right now it assumes the JSON one is being used
             */
            foreach ($results['choices'] as $content) {
                //                $tool_calls[] = [
                //                    'function'=>[
                //                        'id' => '',
                //                        'name' => data_get($content, 'message.tool_calls.0.function.name'),
                //                        'arguments' => json_decode(data_get($content, 'message.tool_calls.0.function.arguments', []), true),
                //                    ]
                //                ];
            }
        } else {
            foreach (data_get($results, 'choices', []) as $result) {
                $data = data_get($result, 'message.content', '');
            }
        }

        return [$data, $tool_used, $tool_calls, $stop_reason];
    }

    //    public function modifyPayload(array $payload, bool $noTools = false): array
    //    {
    //        Log::info('LlmDriver::OpenAi::modifyPayload', [
    //            'payload' => $payload,
    //            'forceTool' => $this->forceTool,
    //        ]);
    //
    //        if (! empty($this->forceTool)) {
    //            $function = [$this->forceTool];
    //            $function = $this->remapFunctions($function);
    //
    //            $payload['tools'] = $function;
    //            $payload['tool_choice'] = [
    //                'type' => 'function',
    //                'function' => [
    //                    'name' => $this->forceTool->name,
    //                ],
    //            ];
    //        } else {
    //            //@TODO
    //            //is this needed any more see how base client does it
    //        }
    //
    //        $payload = $this->addJsonFormat($payload);
    //
    //        return $payload;
    //    }

    public function addJsonFormat(array $payload): array
    {
        // @NOTE the results are not great if you want an array of objects

        // if ($this->formatJson) {
        //     $payload['response_format'] = [
        //         'type' => 'json_object',
        //     ];
        // }

        return $payload;
    }

    /**
     * This is to get functions out of the llm
     * if none are returned your system
     * can error out or try another way.
     *
     * @param  MessageInDto[]  $messages
     */
    public function functionPromptChat(array $messages, array $only = []): array
    {

        Log::info('LlmDriver::OpenAiClient::functionPromptChat', $messages);

        $functions = $this->getFunctions();

        $response = OpenAI::chat()->create([
            'model' => config('larachain.drivers.openai.models.chat_model'),
            'messages' => collect($messages)->map(function ($message) {
                return $message->toArray();
            })->toArray(),
            'tool_choice' => 'auto',
            'tools' => $functions,
        ]);

        $functions = [];
        foreach ($response->choices as $result) {
            foreach (data_get($result, 'message.toolCalls', []) as $tool) {
                if (data_get($tool, 'type') === 'function') {
                    $name = data_get($tool, 'function.name', null);
                    if (! in_array($name, $only)) {
                        $functions[] = [
                            'name' => $name,
                            'arguments' => json_decode(data_get($tool, 'function.arguments', []), true),
                        ];
                    }
                }
            }
        }

        /**
         * @TODO
         * make this a dto
         */
        return $functions;
    }

    /**
     * @NOTE
     * Since this abstraction layer is based on OpenAi
     * Not much needs to happen here
     * but on the others I might need to do XML?
     */
    public function getFunctions(): array
    {
        $functions = parent::getFunctions();

        return $this->remapFunctions($functions);

    }

    /**
     * @param  FunctionDto[]  $functions
     */
    public function remapFunctions(array $functions): array
    {
        return collect($functions)->map(function ($function) {
            $properties = [];
            $required = [];

            $type = data_get($function, 'parameters.type', 'object');

            foreach (data_get($function, 'parameters.properties', []) as $property) {
                $name = data_get($property, 'name');

                if (data_get($property, 'required', false)) {
                    $required[] = $name;
                }

                $properties[$name] = [
                    'description' => data_get($property, 'description', null),
                    'type' => data_get($property, 'type', 'string'),
                ];
            }

            $itemsOrProperties = $properties;

            if ($type === 'array') {
                $itemsOrProperties = [
                    'results' => [
                        'type' => 'array',
                        'description' => 'The results of prompt',
                        'items' => [
                            'type' => 'object',
                            'properties' => $properties,
                        ],
                    ],
                ];
            }

            return [
                'type' => 'function',
                'function' => [
                    'name' => data_get($function, 'name'),
                    'description' => data_get($function, 'description'),
                    'parameters' => [
                        'type' => 'object',
                        'properties' => $itemsOrProperties,
                    ],
                ],
            ];
        })->toArray();
    }
}
