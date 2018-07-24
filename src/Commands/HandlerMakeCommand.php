<?php

namespace Hivokas\LaravelHandlers\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\GeneratorCommand;
use Hivokas\LaravelHandlers\Support\ActionsBag;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Hivokas\LaravelHandlers\Exceptions\CommandException;

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
        parent::__construct($files);
    }

    /**
     * Execute the console command.
     *
     * @return bool
     */
    public function handle(): bool
    {
        try {
            $bag = new ActionsBag;

            $this->processResourceOption($bag);
            $this->processActionsOption($bag);
            $this->processExceptOption($bag);
            $this->processApiOption($bag);

            $classNames = $this->generateClassNames($bag, $this->getValidatedNameArgument());

            $finalNamespace = $this->getFinalNamespace();

            foreach ($classNames as $className) {
                $this->nameInput = $finalNamespace.'\\'.$className;
                $this->type = $className.' handler';

                parent::handle();
            }
        } catch (CommandException $exception) {
            $this->error($exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Get final namespace determined by default and specified by user namespaces.
     *
     * @return string
     * @throws CommandException
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
     * @throws CommandException
     */
    protected function getValidatedAndNormalizedNamespaceOption(): ?string
    {
        $namespace = (string) $this->option('namespace');

        if (! $namespace) {
            return null;
        }

        $namespaceWithNormalizedSlashes = preg_replace('/[\/\\\]+/', '\\', $namespace);

        if (! preg_match('/^(\\\|(\\\?\w+)+)$/', $namespaceWithNormalizedSlashes)) {
            throw new CommandException('['.$namespace.'] is not a valid namespace.');
        }

        return $namespaceWithNormalizedSlashes;
    }

    /**
     * Generate class names by specified name and actions.
     *
     * @param ActionsBag $bag
     * @param string $name
     * @return array
     */
    protected function generateClassNames(ActionsBag $bag, string $name): array
    {
        $name = studly_case($name);

        if ($bag->isEmpty()) {
            return [$name];
        } else {
            return array_map(function (string $action) use ($name) {
                return studly_case($action).$name;
            }, $bag->get());
        }
    }

    /**
     * Get validated name argument.
     *
     * @return string
     * @throws CommandException
     */
    protected function getValidatedNameArgument(): string
    {
        $name = (string) $this->argument('name');
        if (! preg_match('/^\w+$/', $name)) {
            throw new CommandException('Name can\'t contain any non-word characters.');
        }

        return $name;
    }

    /**
     * Process --resource option.
     *
     * @param ActionsBag $bag
     * @return void
     */
    protected function processResourceOption(ActionsBag $bag): void
    {
        if ($this->option('resource')) {
            foreach (['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'] as $action) {
                $bag->addIfNotExists($action);
            }
        }
    }

    /**
     * Process --actions option.
     *
     * @param ActionsBag $bag
     * @return void
     * @throws CommandException
     */
    protected function processActionsOption(ActionsBag $bag): void
    {
        if ($actions = (string) $this->option('actions')) {
            foreach (explode(',', $actions) as $action) {
                $bag->addIfNotExists(
                    $this->getValidatedAndNormalizedActionName($action)
                );
            }
        }
    }

    /**
     * Process --except option.
     *
     * @param ActionsBag $bag
     * @return void
     * @throws CommandException
     */
    protected function processExceptOption(ActionsBag $bag): void
    {
        if ($except = (string) $this->option('except')) {
            foreach (explode(',', $except) as $action) {
                $bag->deleteIfExists(
                    $this->getValidatedAndNormalizedActionName($action)
                );
            }
        }
    }

    /**
     * Process an --api option.
     *
     * @param ActionsBag $bag
     * @return void
     */
    protected function processApiOption(ActionsBag $bag): void
    {
        if ($this->option('api')) {
            foreach (['edit', 'create'] as $action) {
                $bag->deleteIfExists($action);
            }
        }
    }

    /**
     * Get validated and normalized action name.
     *
     * @param string $action
     * @return string
     * @throws CommandException
     */
    protected function getValidatedAndNormalizedActionName(string $action): string
    {
        if (preg_match('/^\w+$/', $action)) {
            return snake_case($action);
        }

        throw new CommandException('['.$action.'] is not a valid action name.');
    }

    /**
     * Get the name input.
     *
     * @return null|string
     */
    public function getNameInput(): ?string
    {
        return $this->nameInput;
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__.'/../../stubs/handler.stub';
    }

    /**
     * Get the class name of the base handler.
     *
     * @return string
     * @throws CommandException
     */
    protected function getBaseHandlerClassName(): string
    {
        if (class_exists($base = config('handlers.base'))) {
            return $base;
        }

        throw new CommandException('The ['.$base.'] class specified as the base handler doesn\'t exist.');
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string $stub
     * @param  string $name
     * @return $this
     * @throws CommandException
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            ['DummyNamespace', 'DummyBaseHandlerNamespace'],
            [$this->getNamespace($name), $this->getBaseHandlerClassName()],
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
