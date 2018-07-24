<?php

namespace Hivokas\LaravelHandlers\Tests;

use SplFileInfo;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Exception\RuntimeException;

class HandlerMakeCommandTest extends AbstractTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->files->cleanDirectory($this->app->path());
    }

    public function test_name_specified()
    {
        $filePath = $this->app->path('Http/Handlers/ShowProfile.php');

        $this->assertFileNotExists($filePath);

        $this->artisan('make:handler', [
            'name' => 'ShowProfile',
        ]);

        $this->assertFileExists($filePath);
    }

    public function test_name_not_specified()
    {
        $this->expectException(RuntimeException::class);

        $this->artisan('make:handler');
    }

    public function test_invalid_name_specified()
    {
        $this->artisan('make:handler', [
            'name' => 'ShowProfile%',
        ]);

        $this->assertDirectoryNotExists($this->app->path('Handlers'));

        $this->assertEquals(Artisan::output(), 'Name can\'t contain any non-word characters.'.PHP_EOL);
    }

    public function test_create_existent_handler_without_force_option()
    {
        $initialHandlerContent = str_random();
        $filePath = $this->app->path('Http/Handlers/ShowProfile.php');

        $this->forceFilePutContents($filePath, $initialHandlerContent);

        $this->assertEquals($initialHandlerContent, file_get_contents($filePath));

        $this->artisan('make:handler', [
            'name' => 'ShowProfile',
        ]);

        $this->assertEquals($initialHandlerContent, file_get_contents($filePath));

        $this->assertEquals(Artisan::output(), 'ShowProfile handler already exists!'.PHP_EOL);
    }

    public function test_create_existent_handler_with_force_option()
    {
        $initialHandlerContent = str_random();
        $filePath = $this->app->path('Http/Handlers/ShowProfile.php');

        $this->forceFilePutContents($filePath, $initialHandlerContent);

        $this->assertEquals($initialHandlerContent, file_get_contents($filePath));

        $this->artisan('make:handler', [
            'name' => 'ShowProfile',
            '--force' => true,
        ]);

        $this->assertNotEquals($initialHandlerContent, file_get_contents($filePath));
    }

    public function test_namespace_option_with_relative_path()
    {
        $filePath = $this->app->path('Http/Handlers/Profile/ShowProfile.php');

        $this->artisan('make:handler', [
            'name' => 'ShowProfile',
            '--namespace' => 'Profile',
        ]);

        $this->assertFileExists($filePath);
    }

    public function test_namespace_option_with_absolute_path()
    {
        $filePath = $this->app->path('Custom/ShowProfile.php');

        $this->artisan('make:handler', [
            'name' => 'ShowProfile',
            '--namespace' => '\\Custom',
        ]);

        $this->assertFileExists($filePath);
    }

    public function test_invalid_namespace_option()
    {
        $this->artisan('make:handler', [
            'name' => 'ShowProfile',
            '--namespace' => 'InvalidNamespace%',
        ]);

        $this->assertDirectoryNotExists($this->app->path('Handlers'));

        $this->assertEquals(Artisan::output(), '[InvalidNamespace%] is not a valid namespace.'.PHP_EOL);
    }

    public function test_resource_option()
    {
        $handlersPath = $this->app->path('Http/Handlers');

        $this->artisan('make:handler', [
            'name' => 'Profile',
            '--resource' => true,
        ]);

        $this->assertDirectoryExists($handlersPath);

        $actions = ['index', 'show', 'edit', 'update', 'create', 'store', 'destroy'];

        $expectedFiles = $this->getHandlerFileNamesByNameAndActions('Profile', $actions);
        $actualFiles = $this->getFileNamesByPath($handlersPath);

        $this->assertEquals(array_sort_recursive($expectedFiles), array_sort_recursive($actualFiles));
    }

    public function test_resource_option_with_api_option()
    {
        $handlersPath = $this->app->path('Http/Handlers');

        $this->artisan('make:handler', [
            'name' => 'Profile',
            '--resource' => true,
            '--api' => true,
        ]);

        $this->assertDirectoryExists($handlersPath);

        $actions = ['index', 'show', 'update', 'store', 'destroy'];

        $expectedFiles = $this->getHandlerFileNamesByNameAndActions('Profile', $actions);
        $actualFiles = $this->getFileNamesByPath($handlersPath);

        $this->assertEquals(array_sort_recursive($expectedFiles), array_sort_recursive($actualFiles));
    }

    public function test_actions_option()
    {
        $handlersPath = $this->app->path('Http/Handlers');

        $actions = ['drop', 'move', 'store'];

        $this->artisan('make:handler', [
            'name' => 'Profile',
            '--actions' => implode(',', $actions),
        ]);

        $this->assertDirectoryExists($handlersPath);

        $expectedFiles = $this->getHandlerFileNamesByNameAndActions('Profile', $actions);
        $actualFiles = $this->getFileNamesByPath($handlersPath);

        $this->assertEquals(array_sort_recursive($expectedFiles), array_sort_recursive($actualFiles));
    }

    public function test_invalid_actions_option()
    {
        $this->artisan('make:handler', [
            'name' => 'Profile',
            '--actions' => 'show,destroy%',
        ]);

        $this->assertDirectoryNotExists($this->app->path('Handlers'));

        $this->assertEquals(Artisan::output(), '[destroy%] is not a valid action name.'.PHP_EOL);
    }

    public function test_except_option()
    {
        $handlersPath = $this->app->path('Http/Handlers');

        $exceptActions = ['index', 'show', 'destroy'];

        $this->artisan('make:handler', [
            'name' => 'Profile',
            '--resource' => true,
            '--except' => implode(',', $exceptActions),
        ]);

        $this->assertDirectoryExists($handlersPath);

        $expectedActions = ['edit', 'update', 'create', 'store'];

        $expectedFiles = $this->getHandlerFileNamesByNameAndActions('Profile', $expectedActions);
        $actualFiles = $this->getFileNamesByPath($handlersPath);

        $this->assertEquals(array_sort_recursive($expectedFiles), array_sort_recursive($actualFiles));
    }

    public function test_invalid_except_option()
    {
        $this->artisan('make:handler', [
            'name' => 'Profile',
            '--resource' => true,
            '--except' => 'show,destroy%',
        ]);

        $this->assertDirectoryNotExists($this->app->path('Handlers'));

        $this->assertEquals(Artisan::output(), '[destroy%] is not a valid action name.'.PHP_EOL);
    }

    public function test_proper_file_content_generation()
    {
        $this->artisan('make:handler', [
            'name' => 'ShowProfile',
        ]);

        $generatedContent = file_get_contents($this->app->path('Http/Handlers/ShowProfile.php'));
        $expectedContent = file_get_contents(__DIR__.'/Stubs/ShowProfile.stub');

        $this->assertEquals($generatedContent, $expectedContent);
    }

    public function test_proper_file_content_generation_with_custom_base_handler()
    {
        config([
            'handlers.base' => Controller::class,
        ]);

        $this->artisan('make:handler', [
            'name' => 'ShowProfile',
        ]);

        $generatedContent = file_get_contents($this->app->path('Http/Handlers/ShowProfile.php'));
        $expectedContent = file_get_contents(__DIR__.'/Stubs/ShowProfileWithCustomBaseHandler.stub');

        $this->assertEquals($generatedContent, $expectedContent);
    }

    /**
     * Get handler file names by name and actions.
     *
     * @param string $name
     * @param array $actions
     * @return array
     */
    protected function getHandlerFileNamesByNameAndActions(string $name, array $actions): array
    {
        return array_map(function (string $action) use ($name) {
            return studly_case($action).studly_case($name).'.php';
        }, $actions);
    }

    /**
     * Get file names by path.
     *
     * @param string $path
     * @return array
     */
    protected function getFileNamesByPath(string $path): array
    {
        return array_map(function (SplFileInfo $file) {
            return $file->getFilename();
        }, $this->files->files($path));
    }
}
