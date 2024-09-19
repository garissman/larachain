<?php

namespace Garissman\LaraChain\Structures\Classes;

use Spatie\LaravelData\Data;

class ToolsDto extends Data
{
    /**
     * @param  FunctionCallDto[]  $tools
     */
    public function __construct(
        public array $tools = []
    ) {
    }
}
