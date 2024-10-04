<?php

namespace Garissman\LaraChain\Jobs\Document;


use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Models\DocumentChunk;
use Garissman\LaraChain\Structures\Enums\StatusEnum;
use Garissman\LaraChain\Structures\Traits\JobMiddlewareTrait;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VectorlizeDataJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use JobMiddlewareTrait;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 25;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public DocumentChunk $documentChunk)
    {
        //
    }

    public function middleware(): array
    {
        return $this->driverMiddleware($this->documentChunk);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        if (optional($this->batch())->cancelled()) {
            // Determine if the batch has been cancelled...
            $this->documentChunk->update([
                'status_embeddings' => StatusEnum::Cancelled,
            ]);

            return;
        }

        $content = $this->documentChunk->content;

        $results = LaraChain::engine($this->documentChunk->getEmbeddingDriver())
            ->embedData($content);
        $embedding_column = $this->documentChunk->getEmbeddingColumn();
        $embedding=$results->embedding;
        $this->documentChunk->update([
            $embedding_column => $embedding,
            'status_embeddings' => StatusEnum::Complete,
        ]);
        $this->documentChunk->save();
    }
}
