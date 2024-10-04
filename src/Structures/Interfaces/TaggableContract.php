<?php

namespace Garissman\LaraChain\Structures\Interfaces;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface TaggableContract
{
    public function tags(): MorphToMany;

    public function addTag(string $tag): void;

    public function siblingTags(): array;
}
