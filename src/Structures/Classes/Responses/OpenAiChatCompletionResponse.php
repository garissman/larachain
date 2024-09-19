<?php

namespace Garissman\LaraChain\Structures\Classes\Responses;

use Garissman\LaraChain\Structures\Classes\OpenAiToolDto;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Optional;

class OpenAiChatCompletionResponse extends CompletionResponse
{
    public function __construct(
        #[MapInputName('choices.0.message.content')]
        public mixed $content,
        #[MapInputName('choices.0.finish_reason')]
        public string|Optional $stop_reason,
        public ?string $tool_used = '',
        /** @var array<OpenAiToolDto> */
        #[MapInputName('choices.0.message.tool_calls')]
        public array $tool_calls = [],
        #[MapInputName('usage.prompt_tokens')]
        public ?int $input_tokens = null,
        #[MapInputName('usage.completion_tokens')]
        public ?int $output_tokens = null,
        public ?string $model = null,
    ) {
    }
}
