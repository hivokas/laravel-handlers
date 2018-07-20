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
