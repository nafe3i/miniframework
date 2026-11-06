<?php

declare(strict_types=1);

namespace MiniForge\Routing;

use InvalidArgumentException;

/**
 * A single route definition: HTTP method + URI pattern + controller action.
 *
 * Patterns may contain named parameters such as "/tasks/{id}". A parameter
 * matches exactly one non-empty path segment.
 */
final class Route
{
    private const PARAMETER = '/\{([A-Za-z_][A-Za-z0-9_]*)\}/';

    private readonly string $method;
    private readonly string $path;

    /** @var array{0: class-string, 1: string} */
    private readonly array $action;

    /**
     * @param array<mixed> $action Controller class and method name: [Controller::class, 'method']
     */
    public function __construct(string $method, string $path, array $action)
    {
        if (count($action) !== 2 || !is_string($action[0] ?? null) || !is_string($action[1] ?? null)) {
            throw new InvalidArgumentException('A route action must be an array of [ControllerClass::class, \'method\'].');
        }

        /** @var array{0: class-string, 1: string} $action */
        $this->action = $action;
        $this->method = strtoupper($method);
        $this->path = '/' . trim($path, '/');

        $names = $this->parameterNames();
        if (count($names) !== count(array_unique($names))) {
            throw new InvalidArgumentException(sprintf('Route [%s] declares the same parameter twice.', $this->path));
        }
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array{0: class-string, 1: string}
     */
    public function action(): array
    {
        return $this->action;
    }

    /**
     * A static route contains no parameters.
     */
    public function isStatic(): bool
    {
        return preg_match(self::PARAMETER, $this->path) !== 1;
    }

    /**
     * @return list<string>
     */
    public function parameterNames(): array
    {
        preg_match_all(self::PARAMETER, $this->path, $matches);

        return $matches[1];
    }

    /**
     * Pattern with parameter names erased: "/tasks/{id}" and "/tasks/{task}"
     * share the signature "/tasks/{}" and would therefore collide.
     */
    public function signature(): string
    {
        return (string) preg_replace(self::PARAMETER, '{}', $this->path);
    }
}
