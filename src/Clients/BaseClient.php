<?php

namespace Garissman\LaraChain\Clients;

use Garissman\LaraChain\Models\Chat;
use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\FunctionContract;
use Garissman\LaraChain\Structures\Classes\FunctionDto;
use Garissman\LaraChain\Structures\Classes\Responses\CompletionResponse;
use Garissman\LaraChain\Structures\Classes\Responses\EmbeddingsResponseDto;
use Garissman\LaraChain\Structures\Enums\ToolTypes;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseClient
{
    protected string $driver = 'mock';

    protected int $poolSize = 3;

    protected ?string $system = null;

    protected ?Chat $chat = null;
    protected ?Collection $messages = null;

    protected bool $limitByShowInUi = false;

    protected ToolTypes $toolType = ToolTypes::NoFunction;

    protected bool $formatJson = false;

    protected ?FunctionDto $forceTool = null;

    abstract function chat(array $messages, ?Message $message = null): CompletionResponse;

    abstract public function embedData(string $prompt): EmbeddingsResponseDto;

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
                    $content .= $line['message']['content'];
                    if (
                        $line['message']['content'] == '[TOOL_CALLS]'
                        || str_contains($content, '{"name":')
                    ) {
                        $tool_call = true;
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
            $return['message']['content'] = '';
            $tool = json_decode(trim(str_replace('[TOOL_CALLS]', '', $content)), true);
            if (str_contains(config('larachain.drivers.ollama.models.chat_model'), 'llama')) {
                $tool['arguments'] = $tool['parameters'];
                $tool = [$tool];
            }
            $return['message']['tool_calls'] = $tool;
            if ($message) {
                $message->body = '';
            }
//            $message->role =  RoleEnum::Tool;
        } else {
            $return['message']['content'] = $content;
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
    public function streamOutputCompletion($body): array
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
                    $content .= $line['response'];
                    $response = '';
                }
            }
        }
        return ['response'=>$content];
    }

    abstract function processStreamLine(string $line): string;

    public function setSystemPrompt(string $systemPrompt = ''): self
    {
        $this->system = $systemPrompt;

        return $this;
    }

    public function setLimitByShowInUi(bool $limitByShowInUi): self
    {
        $this->limitByShowInUi = $limitByShowInUi;

        return $this;
    }

    public function setChat(Chat $chat): self
    {
        $this->chat = $chat;
        $this->setMessages($chat->messages);
        return $this;
    }

    public function setMessages(Collection $messages): self
    {
        $this->messages = $messages;
        return $this;
    }

    public function setForceTool(FunctionDto $tool): self
    {
        $this->forceTool = $tool;

        return $this;
    }

    public function setFormatJson(): self
    {
        $this->formatJson = true;

        return $this;
    }

    public function modifyPayload(array $payload, bool $noTools = false): array
    {
        if (($noTools === false && $this->toolType !== ToolTypes::NoFunction) || $this->forceTool !== null) {
            $payload['tools'] = $this->getFunctions();
        }

        if ($this->system) {
            $payload['system'] = $this->system;
        }

        $payload = $this->addJsonFormat($payload);

        return $payload;
    }

    public function getFunctions(): array
    {
        $functions = collect(config('larachain.tools'));
        $functions = $functions->map(function ($function) {
            return new $function();
        });
        if (isset($this->toolType) && $this->toolType !== ToolTypes::NoFunction) {
            $functions = $functions->filter(function (FunctionContract $function) {
                return in_array($this->toolType, $function->toolTypes);
            });
        }

        if ($this->limitByShowInUi) {
            $functions = $functions->filter(function (FunctionContract $function) {
                return $function->showInUi;
            });
        }
        $return = $functions->map(function (FunctionContract $function) {
            return $function;
        })
            ->values()
            ->map(function (FunctionContract $function) {
                return $function->getFunction();
            })->toArray();

        return $return;
    }

    public function addJsonFormat(array $payload): array
    {
        return $payload;
    }

    public function setToolType(ToolTypes $toolType): self
    {
        $this->toolType = $toolType;

        return $this;
    }

    public function isAsync(): bool
    {
        return config('larachain.drivers.' . $this->driver . '.async', false);
    }

    public function getEmbeddingSize(): int
    {
        $embedding_model = config('larachain.drivers.' . $this->driver . '.models.embedding_model', 3072);
        return config('larachain.embedding_sizes.' . $embedding_model, 3072);
    }

    protected function messagesToArray(array $messages): array
    {
        return collect($messages)->map(function ($message) {
            if (!is_array($message)) {
                $message = $message->toArray();
            }

            return $message;
        })->toArray();
    }

}
