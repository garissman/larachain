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
use Illuminate\Queue\Middleware\WithoutOverlapping;


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
            foreach ($response->tool_calls as $tool_call) {
                $tool = $this->getTool($tool_call->name);
                $message = $this
                    ->chat
                    ->messages()
                    ->create(
                        [
                            'body' => sprintf('Tool %s', $tool_call->name),
                            'role' => RoleEnum::Tool,
                            'in_out' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'is_chat_ignored' => true,
                            'tool_name' => $tool_call->name,
                            'tool_id' => $tool_call->id,
                            'args' => $tool_call->arguments,
                        ]);
                $tool->handle($message,$response->assistanceMessage,  $tool_call->arguments);
                $response = LaraChain::invoke($this->chat)
                    ->chat();
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
    public function middleware(): array
    {
        return [(new WithoutOverlapping('chat.'.$this->chat->id))->dontRelease()];
    }
}