<?php

namespace Garissman\LaraChain\Observers;


use Garissman\LaraChain\Jobs\Document\ProcessFileJob;
use Garissman\LaraChain\Models\Document;

class DocumentObserver
{
    /**
     * Handle the Message "created" event.
     */
    public function created(Document $document): void
    {
        ProcessFileJob::dispatch($document);
    }

    /**
     * Handle the Message "updated" event.
     */
    public function updated(Document $document): void
    {
        ProcessFileJob::dispatch($document);
    }

    /**
     * Handle the Message "deleted" event.
     */
    public function deleted(Document $document): void
    {
        //
    }

    /**
     * Handle the Message "restored" event.
     */
    public function restored(Document $document): void
    {
        //
    }

    /**
     * Handle the Message "force deleted" event.
     */
    public function forceDeleted(Document $document): void
    {
        //
    }
}
