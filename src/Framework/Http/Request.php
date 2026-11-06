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

    public function __construct(string $method, string $path)
    {
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
