<?php

namespace Garissman\LaraChain;

use Garissman\LaraChain\Console\CreateDefaultAgentCommand;
use Garissman\LaraChain\Console\InstallCommand;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaraChainServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
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
    public function boot(): void
    {
        $this->configurePublishing();
        $this->registerCommands();
        $this->configureRoutes();
    }

    /**
     * Configure the publishable resources offered by the package.
     *
     * @return void
     */
    protected function configurePublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/larachain.php' => config_path('larachain.php'),
            ], ['larachain', 'larachain-config']);
            $method = method_exists($this, 'publishesMigrations') ? 'publishesMigrations' : 'publishes';

            $this->{$method}([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], ['larachain', 'larachain-migrations']);

            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/larachain'),
            ], ['larachain', 'larachain-assets']);
            $this->publishes([
                __DIR__ . '/../resources/views/app.blade.php' => resource_path('views/vendor/larachain/app.blade.php'),
            ], ['larachain', 'larachain-view']);
            $this->publishes([
                __DIR__ . '/../resources/js/Pages/Chat.vue' => resource_path('js/Pages/vendor/LaraChain/Chat.vue'),
            ], ['larachain', 'larachain-chat-component']);

            $this->publishes([
                __DIR__ . '/../resources/js/Pages/ChatMessages.vue' => resource_path('js/Pages/vendor/LaraChain/ChatMessages.vue'),
            ], ['larachain', 'larachain-chat-message-component']);

            $this->publishes([
                __DIR__ . '/../resources/js/Pages/SelectDriver.vue' => resource_path('js/Pages/vendor/LaraChain/SelectDriver.vue'),
            ], ['larachain', 'larachain-chat-select-driver-component']);
        }
    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                CreateDefaultAgentCommand::class
            ]);
        }
    }

    /**
     * Configure the routes offered by the application.
     *
     * @return void
     */
    protected function configureRoutes(): void
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        Route::group([
            'prefix' => config('larachain.path'),
            'middleware' => config('larachain.middleware', 'web'),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
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
