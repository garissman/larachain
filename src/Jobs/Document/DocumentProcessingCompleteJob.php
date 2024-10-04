<?php

namespace Garissman\LaraChain\Jobs\Document;


use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Structures\Enums\StatusEnum;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DocumentProcessingCompleteJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Document $document)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $count = $this->document->document_chunks()->where('section_number', 0)->count();

        $this->document->update([
            'status' => StatusEnum::Complete,
            'document_chunk_count' => $count,
        ]);
    }
}
