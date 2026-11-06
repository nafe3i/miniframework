<?php

declare(strict_types=1);

namespace MiniForge\Container;

use Closure;
use MiniForge\Contracts\ContainerInterface;
use MiniForge\Exceptions\ServiceNotFoundException;

/**
 * Lightweight service container with explicit bindings only (no reflection
 * auto-wiring): every service is described by a factory closure.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, Closure(ContainerInterface): mixed> */
    private array $factories = [];

    /**
     * Registers a factory producing a new instance on every resolution.
     *
     * The identifier is usually an interface or class name, which lets an
     * abstraction be bound to a concrete implementation.
     *
     * @param Closure(ContainerInterface): mixed $factory
     */
    public function bind(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }

    public function get(string $id): mixed
    {
        if (!isset($this->factories[$id])) {
            throw ServiceNotFoundException::forId($id);
        }

        return ($this->factories[$id])($this);
    }
}
