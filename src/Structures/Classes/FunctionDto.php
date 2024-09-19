<?php

namespace Garissman\LaraChain\Structures\Classes;

/**
 * @method static from(array $array)
 */
class FunctionDto extends \Spatie\LaravelData\Data
{
    public function __construct(
        public string $name,
        public string $description,
        public ParametersDto $parameters,
    ) {
    }
}
