<?php

namespace Hivokas\LaravelHandlers\Tests;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Hivokas\LaravelHandlers\Providers\HandlersServiceProvider;

abstract class AbstractTestCase extends OrchestraTestCase
{
    /**
     * @var Filesystem
     */
    protected $files;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application   $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->files = new Filesystem();
    }

    /**
     * Get package providers.
     *
     * @return array
     */
    protected function getPackageProviders()
    {
        return [
            HandlersServiceProvider::class,
        ];
    }

    /**
     * Make the directory.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory(string $path): string
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }

        return $path;
    }

    /**
     * Put contents to file.
     *
     * @param string $path
     * @param string $contents
     * @param bool $lock
     */
    protected function forceFilePutContents(string $path, string $contents, bool $lock = false): void
    {
        $this->makeDirectory($path);
        $this->files->put($path, $contents, $lock);
    }
}
