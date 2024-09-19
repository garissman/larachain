<?php

namespace Garissman\LaraChain\Structures\Classes;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class ToolDto extends Data
{
    public function __construct(
        public string $name,
        public array|string $arguments,
        public string|Optional $id = '',
    ) {
    }
}
