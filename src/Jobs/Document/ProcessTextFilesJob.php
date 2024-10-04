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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

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
            // Determine if the batch has been cancelled...

            return;
        }
        $document = $this->document;

        $filePath = $this->document->pathToFile();

        $content = File::get($filePath);

        $document->update([
            'summary' => $content,
            'original_content' => $content,
        ]);

        $jobs = [];

        $page_number = 1;
        $chunked_chunks = TextChunker::handle($content);

        foreach ($chunked_chunks as $chunkSection => $chunkContent) {

            try {
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
            } catch (\Exception $e) {
                Log::error('Error parsing Text', ['error' => $e->getMessage()]);
            }

        }

        Bus::batch($jobs)
            ->name("Chunking Document - $document->file_path")
            ->finally(function (Batch $batch) use ($document) {
                TagDocumentJob::dispatch($document);
                DocumentProcessingCompleteJob::dispatch($document);
            })
            ->allowFailures()
            ->dispatch();

    }
}
