<?php

namespace Garissman\LaraChain\Engines;

use Garissman\LaraChain\Clients\BaseClient;
use Garissman\LaraChain\Models\Chat;
use Garissman\LaraChain\Structures\Classes\MessageInDto;
use Garissman\LaraChain\Structures\Classes\Responses\CompletionResponse;
use Garissman\LaraChain\Structures\Classes\Responses\EmbeddingsResponseDto;
use Garissman\LaraChain\Structures\Enums\RoleEnum;
use Garissman\LaraChain\Structures\Enums\ToolTypes;

abstract class Engine
{
    protected BaseClient $client;
    protected string $systemPrompt = '';
    protected ToolTypes $toolType;
    private Chat $chat;

    public function setChat(Chat $chat): self
    {
        $this->chat = $chat;
        return $this;
    }

    public function setSystemPrompt(string $systemPrompt): self
    {
        $this->systemPrompt = $systemPrompt;
        return $this;
    }

    public function chat(): CompletionResponse
    {
        $this->setToolType(ToolTypes::Chat);
        $messages = $this->getMessageThread();
        $message = $this->chat
            ->addInput(
                message: '',
                role: RoleEnum::Assistant,
                is_been_whisper: true,
            );
        return $this->client
            ->setToolType(ToolTypes::Chat)
            ->chat($messages, $message);
    }

    public function setToolType(ToolTypes $toolType): self
    {
        $this->toolType = $toolType;
        return $this;
    }

    public function getMessageThread(int $limit = 5): array
    {
        return $this->getChatResponse($limit);
    }

    public function getChatResponse(int $limit = 5): array
    {
        $latestMessages = $this->chat->messages()
            ->orderBy('id', 'desc')
            ->get();

        $latestMessagesArray = [];

        foreach ($latestMessages as $message) {
            /**
             * @NOTE
             * I am super verbose here due to an odd BUG
             * I keep losing the data due to some
             * magic toArray() method that
             * was not working
             */
            $asArray = [
                'role' => $message->role->value,
                'content' => $message->body,
                'tool_id' => $message->tool_id,
                'tool' => $message->tool_name,
                'args' => $message->args ?? [],
            ];

            $dto = new MessageInDto(
                content: $this->cleanString($asArray['content']),
                role: $asArray['role'],
                tool: $asArray['tool'],
                tool_id: $asArray['tool_id'],
                args: $asArray['args'],
            );
            $latestMessagesArray[] = $dto;
        }

        return array_reverse($latestMessagesArray);

    }

    public function cleanString($string): string
    {
        // Remove Unicode escape sequences
        $string = preg_replace('/\\\\u[0-9A-F]{4}/i', '', $string);

        // Remove all non-printable characters except for newlines and tabs
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        // Optionally, you can also remove extra whitespace:
        $string = preg_replace('/\s+/', ' ', $string);

        return trim($string);
    }

    public function embedData($prompt): EmbeddingsResponseDto
    {
        return $this->client
            ->embedData($prompt);
    }

    public function isAsync(): bool
    {
        return $this->client->isAsync();
    }
}
