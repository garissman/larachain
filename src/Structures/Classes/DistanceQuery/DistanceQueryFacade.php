<?php

namespace Garissman\LaraChain\Structures\Classes\DistanceQuery;

use Illuminate\Support\Facades\Facade;
use Pgvector\Laravel\Vector;

/**
 * @method static cosineDistance(Vector $embedding, ?int $embeddingSize=null)
 */
class DistanceQueryFacade extends Facade
{
    /**
     * @see DistanceQueryClient
     */
    protected static function getFacadeAccessor(): string
    {
        return DistanceQueryClient::class;
    }
}
