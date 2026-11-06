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

    /** @var array<string, string> Header names are stored lower-cased. */
    private readonly array $headers;

    /**
     * @param array<string, mixed> $query Query string parameters
     * @param array<string, mixed> $input Form body parameters
     * @param array<string, string> $headers Header name => value (case-insensitive)
     */
    public function __construct(
        string $method,
        string $path,
        private readonly array $query = [],
        private readonly array $input = [],
        array $headers = [],
    ) {
        $this->method = strtoupper($method);
        $this->path = self::normalizePath($path);
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    /**
     * Builds a Request from the PHP superglobals (used by the front controller).
     *
     * @param array<string, mixed>|null $server Defaults to $_SERVER
     * @param array<string, mixed>|null $query Defaults to $_GET
     * @param array<string, mixed>|null $input Defaults to $_POST
     */
    public static function fromGlobals(?array $server = null, ?array $query = null, ?array $input = null): self
    {
        $server ??= $_SERVER;

        $method = is_string($server['REQUEST_METHOD'] ?? null) ? $server['REQUEST_METHOD'] : 'GET';
        $uri = is_string($server['REQUEST_URI'] ?? null) ? $server['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);

        return new self(
            $method,
            is_string($path) ? rawurldecode($path) : '/',
            $query ?? $_GET,
            $input ?? $_POST,
            self::extractHeaders($server),
        );
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', strtolower(substr($key, 5)))] = $value;
            } elseif ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH') {
                $headers[str_replace('_', '-', strtolower($key))] = $value;
            }
        }

        return $headers;
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
     * Returns a header value (case-insensitive name), or the default.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * Returns a form body parameter, or the default when it is absent.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->input[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function allInput(): array
    {
        return $this->input;
    }

    /**
     * Returns only the given keys from the form body (missing keys are skipped).
     *
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->input, array_flip($keys));
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
