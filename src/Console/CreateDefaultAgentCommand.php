<?php

namespace Garissman\LaraChain\Console;

use Illuminate\Console\Command;
use Garissman\LaraChain\Models\Agent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;

#[AsCommand(name: 'larachain:create_default_agent')]
class CreateDefaultAgentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larachain:create_default_agent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create the First for the Chat Bot";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        Agent::create([
            'name'=>config('app.name')." Agent",
            'description'=>'Default Agent',
            'context'=>'You are a help agent that help user to find data',
            'is_default'=>true,
            'active'=>true,
        ]);
        return CommandAlias::SUCCESS;
    }
}
