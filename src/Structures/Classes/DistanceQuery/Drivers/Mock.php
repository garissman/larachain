<?php

namespace Garissman\LaraChain\Structures\Classes\DistanceQuery\Drivers;


use Garissman\LaraChain\Models\DocumentChunk;
use Garissman\LaraChain\Structures\Classes\MetaDataDto;
use Illuminate\Support\Collection;
use Pgvector\Laravel\Vector;

class Mock extends Base
{
    public function cosineDistance(
        string                       $embeddingSize,
        int                          $collectionId,
        Vector                       $embedding,
        MetaDataDto|null $meta_data = null
    ): Collection
    {
        return DocumentChunk::query()
            ->get();
    }
}
