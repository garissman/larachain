<?php

namespace Garissman\LaraChain\Structures\Classes;


use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Structures\Classes\Responses\CompletionResponse;
use Illuminate\Support\Collection;

class TagManager
{
    protected Collection $tags;

    protected string $tagsAsString = '';

    public function handle(Document $document): void
    {
        if (!$document->summary) {
            return;
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

        $this->tags->take(3)
            ->map(function ($tag) use ($document) {
                $tag = str($tag)
                    ->remove('Here Are 3 Tags:')
                    ->remove('Here Are The Tags:')
                    ->trim()
                    ->toString();
                $document->addTag($tag);
            });

    }
}
