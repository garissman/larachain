<?php

namespace Garissman\Clerk;

use Garissman\LaraChain\LaraChain;
use Illuminate\Support\ServiceProvider;

class LaraChainServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/larachain.php', 'larachain');

        $this->app->bind(LaraChain::class, function ($app) {
            return new LaraChain($app);
        });

        $this->app->alias(LaraChain::class, 'larachain');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configurePublishing();
        $this->registerCommands();
    }

    /**
     * Configure the publishable resources offered by the package.
     *
     * @return void
     */
    protected function configurePublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../stubs/larachain.php' => config_path('larachain.php'),
            ], 'larachain-config');
        }
    }

    /**
     * Configure the routes offered by the application.
     *
     * @return void
     */
    protected function configureRoutes()
    {

    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
    }

    /**
     * Register the response bindings.
     *
     * @return void
     */
    protected function registerResponseBindings()
    {

    }
}
