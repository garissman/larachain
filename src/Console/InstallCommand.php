<?php

namespace Garissman\LaraChain\Console;

use Garissman\LaraChain\LaraChainServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

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
        $this->comment('Publishing Larachain Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'Larachain-assets']);

        $this->comment('Publishing Larachain Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'Larachain-config']);

        $this->comment('Publishing Larachain Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'Larachain-migrations']);

//        $this->registerTelescopeServiceProvider();

        $this->info('Larachain scaffolding installed successfully.');
    }

    /**
     * Register the Telescope service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerTelescopeServiceProvider()
    {
        if (method_exists(ServiceProvider::class, 'addProviderToBootstrapFile') &&
            ServiceProvider::addProviderToBootstrapFile(LaraChainServiceProvider::class)) { // @phpstan-ignore-line
            return;
        }

        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\LaraChainServiceProvider::class')) {
            return;
        }

        $lineEndingCount = [
            "\r\n" => substr_count($appConfig, "\r\n"),
            "\r" => substr_count($appConfig, "\r"),
            "\n" => substr_count($appConfig, "\n"),
        ];

        $eol = array_keys($lineEndingCount, max($lineEndingCount))[0];

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol,
            "{$namespace}\\Providers\RouteServiceProvider::class,".$eol."        {$namespace}\Providers\LaraChainServiceProvider::class,".$eol,
            $appConfig
        ));

        file_put_contents(app_path('Providers/LaraChainServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/LaraChainServiceProvider.php'))
        ));
    }
}
