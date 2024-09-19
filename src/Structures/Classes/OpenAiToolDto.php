<?php

namespace Garissman\LaraChain\Structures\Classes;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Optional;

class OpenAiToolDto extends ToolDto
{
    public function __construct(
        #[MapInputName('function.name')]
        public string $name,
        #[MapInputName('function.arguments')]
        public array|string $arguments,
        public string|Optional $id = '',
    ) {
        $this->arguments = json_decode($this->arguments, true);
    }
}
