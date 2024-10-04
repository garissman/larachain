<?php

namespace Garissman\LaraChain\Structures\Traits;

use Garissman\LaraChain\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait Taggable
{
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function addTag(string $tag): void
    {
        $tag = str($tag)->lower()->trim()->toString();
        $tag = Tag::firstOrCreate(['name' => $tag]);
        $this->tags()->syncWithoutDetaching([$tag->id]);
    }
}
