<?php

namespace Hivokas\LaravelHandlers\Commands;

use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class HandlerMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:handler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Handler generator.';

    /**
     * The collection of actions.
     *
     * @var Collection
     */
    protected $actions;

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Handler';

    /**
     * The name input.
     *
     * @var string|null
     */
    protected $nameInput = null;

    /**
     * HandlerMakeCommand constructor.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->actions = collect();

        parent::__construct($files);
    }

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $finalNamespace = $this->getFinalNamespace();

        $this->getGeneratedClassNames()
            ->each(function (string $className) use ($finalNamespace) {
                $this->nameInput = $finalNamespace.'\\'.$className;
                $this->type = $className.' handler';

                parent::handle();
            });

        return true;
    }

    /**
     * Get final namespace determined by default and specified by user namespaces.
     *
     * @return string
     */
    protected function getFinalNamespace(): string
    {
        $defaultNamespace = $this->laravel->getNamespace().'Http\\Handlers';

        if (! is_null($namespaceOption = $this->getValidatedAndNormalizedNamespaceOption())) {
            if (starts_with($namespaceOption, '\\')) {
                return $namespaceOption;
            }

            return $defaultNamespace.'\\'.$namespaceOption;
        }

        return $defaultNamespace;
    }

    /**
     * Get validated and normalized namespace option.
     *
     * @return string|null
     */
    protected function getValidatedAndNormalizedNamespaceOption(): ?string
    {
        $namespace = (string) $this->option('namespace');

        if (! $namespace) {
            return null;
        }

        $namespaceWithNormalizedSlashes = preg_replace('/[\/\\\]+/', '\\', $namespace);

        if (! preg_match('/^(\\\|(\\\?\w+)+)$/', $namespaceWithNormalizedSlashes)) {
            $this->error('['.$namespace.'] is not a valid namespace.');
            exit;
        }

        return $namespaceWithNormalizedSlashes;
    }

    /**
     * Get class names generated from specified name and actions.
     *
     * @return Collection
     */
    protected function getGeneratedClassNames(): Collection
    {
        $this->actions = collect();

        $this->processResourceOption()
            ->processActionsOption()
            ->processExceptOption()
            ->processApiOption();

        $name = studly_case($this->getValidatedNameArgument());

        if ($this->actions->isEmpty()) {
            return collect([$name]);
        } else {
            return $this->actions->map(function (string $action) use ($name) {
                return studly_case($action).$name;
            });
        }
    }

    /**
     * Get validated name argument.
     *
     * @return string
     */
    protected function getValidatedNameArgument(): string
    {
        $name = (string) $this->argument('name');
        if (! preg_match('/^\w+$/', $name)) {
            $this->error('Name can\'t contain any non-word characters.');
            exit;
        }

        return $name;
    }

    /**
     * Process --resource option.
     *
     * @return HandlerMakeCommand
     */
    protected function processResourceOption(): self
    {
        if ($this->option('resource')) {
            collect(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'])
                ->each(function (string $resourceAction) {
                    $this->addActionIfNotExists($resourceAction);
                });
        }

        return $this;
    }

    /**
     * Process --actions option.
     *
     * @return HandlerMakeCommand
     */
    protected function processActionsOption(): self
    {
        if ($actions = (string) $this->option('actions')) {
            collect(explode(',', $actions))
                ->each(function (string $action) {
                    $this->addActionIfNotExists(
                        $this->getValidatedAndNormalizedActionName($action)
                    );
                });
        }

        return $this;
    }

    /**
     * Process --except option.
     *
     * @return HandlerMakeCommand
     */
    protected function processExceptOption(): self
    {
        if ($except = (string) $this->option('except')) {
            collect(explode(',', $except))
                ->each(function (string $action) {
                    $this->deleteActionIfExists(
                        $this->getValidatedAndNormalizedActionName($action)
                    );
                });
        }

        return $this;
    }

    /**
     * Process an --api option.
     *
     * @return HandlerMakeCommand
     */
    public function processApiOption(): self
    {
        if ($this->option('api')) {
            collect(['edit', 'create'])
                ->each(function (string $action) {
                    $this->deleteActionIfExists($action);
                });
        }

        return $this;
    }

    /**
     * Add an action to the actions collection if it doesn't already exist there.
     *
     * @param string $action
     * @return void
     */
    public function addActionIfNotExists(string $action): void
    {
        if (! $this->actions->contains($action)) {
            $this->actions->push($action);
        }
    }

    /**
     * Delete an action from the actions collection if it already exists there.
     *
     * @param string $action
     */
    public function deleteActionIfExists(string $action): void
    {
        $this->actions = $this->actions->reject(function (string $existingAction) use ($action, &$exists) {
            return $existingAction === $action;
        });
    }

    /**
     * Get validated and normalized action name.
     *
     * @param string $action
     * @return string
     */
    protected function getValidatedAndNormalizedActionName(string $action): string
    {
        if (! preg_match('/^\w+$/', $action)) {
            $this->error('['.$action.'] is not a valid action name.');
            exit;
        }

        return snake_case($action);
    }

    /**
     * Get the name input.
     *
     * @return null|string
     */
    public function getNameInput()
    {
        return $this->nameInput;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../../stubs/handler.stub';
    }

    /*
     * Get the class name of the base handler.
     *
     * @return string
     */
    protected function getBaseHandlerClassName(): string
    {
        if (class_exists($base = config('handlers.base')))
        {
            return $base;
        }

        $this->error('The [' . $base . '] class specified as the base handler doesn\'t exist.');
        exit;
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            ['DummyNamespace', 'DummyBaseHandlerNamespace'],
            [$this->getNamespace($name), $this->getBaseHandlerClassName(), config('auth.providers.users.model')],
            $stub
        );

        return $this;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['resource', 'r', InputOption::VALUE_NONE, 'Generate handlers for all resource actions.'],
            ['api', 'a', InputOption::VALUE_NONE, 'Exclude the create and edit actions.'],
            ['namespace', null, InputOption::VALUE_REQUIRED, 'The namespace for generated handler(-s).'],
            ['force', 'f', InputOption::VALUE_NONE, 'Override existing handlers.'],
            ['actions', null, InputOption::VALUE_REQUIRED, 'Generate handlers for all specified actions separated by coma.'],
            ['except', null, InputOption::VALUE_REQUIRED, 'Exclude specified actions separated by coma.'],
        ];
    }
}
