<?php

namespace Hivokas\LaravelHandlers\Providers;

use Hivokas\LaravelHandlers\Commands\HandlerMakeCommand;
use Illuminate\Support\ServiceProvider;

class HandlersServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.handler.make', HandlerMakeCommand::class);

        $this->commands([
            'command.handler.make',
        ]);
    }
}