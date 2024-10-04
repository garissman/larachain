<?php

namespace Garissman\LaraChain\Models;

use Garissman\LaraChain\Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;


class Tag extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }

    public function document_chunks(): MorphToMany
    {
        return $this->morphedByMany(DocumentChunk::class, 'taggable');
    }

    public function documents(): MorphToMany
    {
        return $this->morphedByMany(Document::class, 'taggable');
    }
}
