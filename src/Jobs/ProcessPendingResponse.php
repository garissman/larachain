<?php

namespace Garissman\LaraChain\Jobs;

use Garissman\LaraChain\Facades\LaraChain;
use Garissman\LaraChain\Models\Chat;
use Garissman\LaraChain\Structures\Enums\RoleEnum;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ProcessPendingResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;


    public function __construct(protected Chat $chat)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $response = LaraChain::invoke($this->chat)
            ->chat();
        if (!empty($response->tool_calls)) {
            if ($this->chat->messages()->count() > 3) {
                foreach ($response->tool_calls as $tool_call) {
                    $tool = $this->getTool($tool_call->name);
                    $response->assistanceMessage->update([
                        'tool_name' => $tool_call->name,
                        'tool_id' => $tool_call->id,
                        'args' => $tool_call->arguments,
                    ]);
                    $toolMessage = $response->assistanceMessage;
                    if (!$this->batch()->cancelled()) {
                        $this->batch()->add([
                            new ProcessPendingTool($tool, $toolMessage),
                        ]);
                    }
                }
            } else {
                $this->chat
                    ->messages()
                    ->where('role', RoleEnum::Assistant)
                    ->latest()
                    ->first()
                    ->update(['role' => RoleEnum::Tool]);
                if (!$this->batch()->cancelled()) {
                    $this->batch()->add([
                        new ProcessPendingResponse($this->chat)
                    ]);
                }
            }
        }
    }

    private function getTool($name)
    {
        $functions = collect(config('larachain.tools'));
        return $functions
            ->map(function ($function) {
                return new $function();
            })
            ->filter(function ($function) use ($name) {
                return $function->name === $name;
            })
            ->first();
    }
}
