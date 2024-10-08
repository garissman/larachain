<?php

namespace Garissman\LaraChain\Jobs\Document;

use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Models\DocumentChunk;
use Garissman\LaraChain\Structures\Classes\TextChunker;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class ProcessTextFilesJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Document $document)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been canceled...
            return;
        }
        $document = $this->document;
        if ($this->document->file_path) {
            $content = Storage::get($this->document->file_path);
        } else {
            $content = $this->document->content;
        }
        $document->update([
            'summary' => $content,
            'original_content' => $content,
        ]);
        $jobs = [];
        $page_number = 1;
        $chunked_chunks = TextChunker::handle($content);
        foreach ($chunked_chunks as $chunkSection => $chunkContent) {
            $guid = md5($chunkContent);
            $DocumentChunk = DocumentChunk::updateOrCreate(
                [
                    'document_id' => $document->id,
                    'sort_order' => $page_number,
                    'section_number' => $chunkSection,
                ],
                [
                    'guid' => $guid,
                    'content' => $chunkContent,
                    'sort_order' => $page_number,
                ]
            );
            $jobs[] = [
                new VectorlizeDataJob($DocumentChunk),
            ];
        }
        Bus::batch($jobs)
            ->name("Chunking Document - $document->id")
            ->finally(function (Batch $batch) use ($document) {
                TagDocumentJob::dispatch($document);
                DocumentProcessingCompleteJob::dispatch($document);
            })
            ->allowFailures()
            ->dispatch();
    }
}
