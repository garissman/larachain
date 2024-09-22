<?php

namespace Garissman\LaraChain\Console;

use Garissman\LaraChain\LaraChainServiceProvider;
use Illuminate\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'larachain:install')]
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larachain:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the LaraChain resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Larachain...');
        $this->callSilent('vendor:publish', ['--provider' => LaraChainServiceProvider::class]);

//        if (file_exists(base_path('pnpm-lock.yaml'))) {
//            $this->runCommands(['pnpm install headlessui/vue', 'pnpm install heroicons/vue']);
//        } elseif (file_exists(base_path('yarn.lock'))) {
//            $this->runCommands(['yarn install install headlessui/vue', 'yarn install heroicons/vue']);
//        } elseif (file_exists(base_path('bun.lockb'))) {
//            $this->runCommands(['bun install headlessui/vue', 'bun install heroicons/vue']);
//        } else {
//            $this->runCommands(['npm install headlessui/vue', 'npm install heroicons/vue']);
//        }
        $this->info('Larachain scaffolding installed successfully.');
    }

    protected function runCommands($commands)
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, null, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (RuntimeException $e) {
                $this->output->writeln('  <bg=yellow;fg=black> WARN </> ' . $e->getMessage() . PHP_EOL);
            }
        }

        $process->run(function ($type, $line) {
            $this->output->write('    ' . $line);
        });
    }
}
