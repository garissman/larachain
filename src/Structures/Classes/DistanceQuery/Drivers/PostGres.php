<?php

namespace Garissman\LaraChain\Structures\Classes\DistanceQuery\Drivers;

use Garissman\LaraChain\Models\DocumentChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

class PostGres extends Base
{
    public function cosineDistance(
        Vector $embedding,
        string $embeddingSize=null,
    ): Collection
    {
        if ($embeddingSize === null) {
            $embeddingSize=count($embedding->embedding->toArray());
        }

        $commonQuery = DocumentChunk::query();
        $embeddingColumn='embedding_'.$embeddingSize;

        $neighborsCosine = $commonQuery
            ->nearestNeighbors($embeddingColumn, $embedding, Distance::Cosine)
            ->get();

        $results = collect($neighborsCosine)
            ->unique('id')
            ->take(4);

        $siblingsIncluded = $this->getSiblings($results);

        return $siblingsIncluded;
    }
}
