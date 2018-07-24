<?php

namespace Hivokas\LaravelHandlers\Providers;

use Illuminate\Support\ServiceProvider;
use Hivokas\LaravelHandlers\Commands\HandlerMakeCommand;

class HandlersServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/handlers.php' => config_path('handlers.php'),
        ], 'config');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/handlers.php', 'handlers');

        $this->app->singleton('command.handler.make', HandlerMakeCommand::class);

        $this->commands([
            'command.handler.make',
        ]);
    }
}
