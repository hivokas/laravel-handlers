<?php

namespace Hivokas\LaravelHandlers\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Hivokas\LaravelHandlers\Providers\HandlersServiceProvider;

abstract class AbstractTestCase extends OrchestraTestCase
{
    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            HandlersServiceProvider::class,
        ];
    }
}
