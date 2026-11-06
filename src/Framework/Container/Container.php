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

    /** @var array<string, true> Identifiers whose instance is shared. */
    private array $shared = [];

    /** @var array<string, mixed> Already built shared instances. */
    private array $instances = [];

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
        $this->register($id, $factory, false);
    }

    /**
     * Registers a factory whose result is built lazily, once, then shared.
     *
     * @param Closure(ContainerInterface): mixed $factory
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->register($id, $factory, true);
    }

    /**
     * Registers an already built object as a shared service.
     */
    public function instance(string $id, object $instance): void
    {
        $this->register($id, static fn (): object => $instance, true);
        $this->instances[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw ServiceNotFoundException::forId($id);
        }

        $service = ($this->factories[$id])($this);

        if (isset($this->shared[$id])) {
            $this->instances[$id] = $service;
        }

        return $service;
    }

    /**
     * @param Closure(ContainerInterface): mixed $factory
     */
    private function register(string $id, Closure $factory, bool $shared): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id], $this->shared[$id]);

        if ($shared) {
            $this->shared[$id] = true;
        }
    }
}
