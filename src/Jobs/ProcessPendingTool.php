<?php

namespace Garissman\LaraChain\Jobs;

use Garissman\LaraChain\Models\Message;
use Garissman\LaraChain\Structures\Classes\FunctionContract;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessPendingTool implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;


    public function __construct(
        private readonly FunctionContract $tool,
        private readonly Message          $assistanceMessage,
        private readonly Message          $toolMessage,
        private                           $arguments)
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->tool->handle($this->assistanceMessage, $this->toolMessage, $this->arguments);
        if (!$this->batch()->cancelled()) {
            $this->batch()->add([
                new ProcessPendingResponse($this->assistanceMessage->chat)
            ]);
        }
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('message.'.$this->assistanceMessage->id))->dontRelease()];
    }
}
