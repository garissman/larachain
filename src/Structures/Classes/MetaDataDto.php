<?php

namespace Garissman\LaraChain\Structures\Classes;

use Spatie\LaravelData\Data;

/**
 * @method static from(array $array)
 */
class MetaDataDto extends Data
{
    public function __construct(
        public mixed $persona = '',
        public mixed $filter = null,
        public bool $completion = false,
        public mixed $tool = '',
        public mixed $tool_id = '',
        public mixed $date_range = '',
        public mixed $input = '',
        public mixed $driver = '',
        public mixed $source = '',
        public mixed $reference_collection_id = '',
        public array $args = []
    ) {

    }
}
