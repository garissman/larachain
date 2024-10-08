<?php

namespace Garissman\LaraChain\Jobs\Document;


use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\MessageInDto;
use Garissman\LaraChain\Structures\Classes\Prompts\SummarizeDocumentPrompt;
use Garissman\LaraChain\Structures\Enums\StatusEnum;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SummarizeDocumentJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $results = '';

    /**
     * Create a new job instance.
     */
    public function __construct(public Document $document, public string $prompt = '')
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $content = $this->document->original_content;
        $prompt = $this->prompt;
        if (empty($this->prompt)) {
            $prompt = SummarizeDocumentPrompt::prompt($content);
        }
        $results = LaraChain::engine(
            $this->document->getDriver()
        )
            ->client
            ->chat([
                MessageInDto::from([
                    'content' => $prompt,
                    'role' => 'user',
                ]),
            ]);
        $this->results = $results->content;
        $this->document->update([
            'summary' => $results->content,
            'status_summary' => StatusEnum::SummaryComplete,
        ]);

    }
}
