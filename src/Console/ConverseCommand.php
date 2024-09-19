<?php

namespace Garissman\Clerk\Console;

use Garissman\Clerk\Facades\Clerk;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\suggest;

#[AsCommand(name: 'clerk:converse')]
class ConverseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clerk:converse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Converse with your Chat bot";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $conversation = collect();
        $suggestions = collect();
        $intents = config('clerk.intents');
        $conversationId=null;
        foreach ($intents as $intent) {
            $intent = new $intent();
            foreach ($intent->getUtterances() as $utterance=>$utteranceData) {
                if (!is_array($utteranceData)) {
                    $utterance=$utteranceData;
                }
                $suggestions->add($utterance);
            }

        }
        $message='Hi';
        while (true) {
            $response = Clerk::converse($message,$conversationId);
            $conversationId=$response['conversationId'];
            outro("Bot: {$response['message']}");
            $conversation->add([
                'who'=>'Bot',
                'message'=>$response['message'],
            ]);
            $message = suggest('Type Message:', $suggestions);
            $conversation->add([
                'who'=>'You',
                'message'=>$message,
            ]);
        }
        return CommandAlias::SUCCESS;
    }
}
