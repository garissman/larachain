<?php

namespace Garissman\LaraChain\Structures\Classes\Responses;


use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\ToolDto;
use Spatie\LaravelData\Optional;

/**
 * @method static from(array $array)
 */
class CompletionResponse extends \Spatie\LaravelData\Data
{
    public function __construct(
        public Message|null $assistanceMessage,
        public mixed $content,
        public string|Optional $stop_reason,
        public ?string $tool_used = '',
        /** @var array<ToolDto> */
        public array $tool_calls = [],
        public ?int $input_tokens = null,
        public ?int $output_tokens = null,
        public ?string $model = null,
    ) {
    }
}
