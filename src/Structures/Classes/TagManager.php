<?php

namespace Garissman\LaraChain\Structures\Classes;


use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Structures\Classes\Prompts\TagPrompt;
use Garissman\LaraChain\Structures\Classes\Responses\CompletionResponse;
use Illuminate\Support\Collection;

class TagManager
{
    protected Collection $tags;

    protected string $tagsAsString = '';

    public function handle(Document $document): ?Document
    {
        if (!$document->summary) {
            return $document;
        }
        $summary = $document->summary;
        $prompt = TagPrompt::prompt($summary);

        /** @var CompletionResponse $response */
        $response = LaraChain::engine($document->getDriver())
            ->completion(
                prompt: $prompt
            );

        $this->tagsAsString = $response->content;

        $this->tags = collect(explode(',', $this->tagsAsString));
        $this->tags
            ->map(function ($tag) use ($document) {
                $tag = str($tag)
                    ->trim()
                    ->toString();
                $document->addTag(trim($tag));
            });
        return $document->load(['tags']);

    }
}
