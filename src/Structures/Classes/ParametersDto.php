<?php

namespace Garissman\LaraChain\Structures\Classes;

class ParametersDto extends \Spatie\LaravelData\Data
{
    /**
     * @param  PropertyDto[]  $properties
     * @return void
     */
    public function __construct(
        public string $type = 'object',
        public array $properties = [],
    ) {
    }
}
