<?php

namespace Garissman\Clerk\Console;

use Illuminate\Console\Command;
use Garissman\Clerk\EngineManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command as CommandAlias;
use function Laravel\Prompts\progress;

#[AsCommand(name: 'clerk:train')]
class TrainCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clerk:train';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Train you Chat bot";

    /**
     * Execute the console command.
     *
     * @param EngineManager $manager
     * @return int
     */
    public function handle(EngineManager $manager): int
    {
        $engine = $manager->engine();

        $driver = config('clerk.driver');

        if (! method_exists($engine, 'train')) {
            $this->error('The "'.$driver.'" engine does not support Training.');
            return CommandAlias::FAILURE;
        }
        $intents = config('clerk.intents');
        $progress = progress(label: 'Training Model', steps: count($intents));
        $progress->start();
        foreach ($intents as $intent) {
            $intent=new $intent();
            $progress->hint('Training '.$intent->getIntent());
            $engine->train(new $intent());
            $progress->advance();
        }
        $progress->finish();
        return CommandAlias::SUCCESS;
    }
}
