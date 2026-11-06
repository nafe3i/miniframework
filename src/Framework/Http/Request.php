<?php

declare(strict_types=1);

namespace MiniForge\Http;

/**
 * Immutable representation of an incoming HTTP request.
 *
 * It can be built from PHP globals for the real server, or directly in tests.
 */
final class Request
{
    private readonly string $method;
    private readonly string $path;

    /**
     * @param array<string, mixed> $query Query string parameters
     */
    public function __construct(
        string $method,
        string $path,
        private readonly array $query = [],
    ) {
        $this->method = strtoupper($method);
        $this->path = self::normalizePath($path);
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
     * Returns a query string parameter, or the default when it is absent.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * Normalizes a URI path: strips the query string, guarantees a leading
     * slash and removes trailing slashes (except for the root path).
     */
    private static function normalizePath(string $path): string
    {
        $path = explode('?', $path, 2)[0];
        $path = '/' . trim($path, '/');

        return $path;
    }
}
