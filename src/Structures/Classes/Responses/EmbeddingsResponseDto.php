<?php

namespace Garissman\LaraChain\Structures\Classes\Responses;


use Pgvector\Laravel\Vector;
use Spatie\LaravelData\Attributes\WithCastable;

/**
 * @method static from(array $array)
 */
class EmbeddingsResponseDto extends \Spatie\LaravelData\Data
{
    public function __construct(
        #[WithCastable(VectorCaster::class)]
        public Vector $embedding,
        public int $token_count
    ) {
    }
}
