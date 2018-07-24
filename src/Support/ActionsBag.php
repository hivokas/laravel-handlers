<?php

namespace Hivokas\LaravelHandlers\Support;

use Illuminate\Support\Collection;

class ActionsBag
{
    /**
     * Collection of actions.
     *
     * @var Collection
     */
    protected $actions;

    /**
     * ActionsBag constructor.
     */
    public function __construct()
    {
        $this->actions = collect();
    }

    /**
     * Add action if not exists.
     *
     * @param string $action
     * @return ActionsBag
     */
    public function addIfNotExists(string $action): self
    {
        if (! $this->actions->contains($action)) {
            $this->actions->push($action);
        }

        return $this;
    }

    /**
     * Delete action if exists.
     *
     * @param string $action
     * @return ActionsBag
     */
    public function deleteIfExists(string $action): self
    {
        $this->actions = $this->actions->reject(function (string $existingAction) use ($action) {
            return $existingAction === $action;
        });

        return $this;
    }

    /**
     * Get array with all actions.
     *
     * @return array
     */
    public function get(): array
    {
        return $this->actions->toArray();
    }

    /**
     * Determines if bag is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->actions->isEmpty();
    }
}
