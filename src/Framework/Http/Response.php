<?php

declare(strict_types=1);

namespace MiniForge\Http;

use InvalidArgumentException;

/**
 * Immutable HTTP response: a status code, headers and a string body.
 */
final class Response
{
    /** @var array<string, string> Keyed by lower-cased name for lookups. */
    private readonly array $headers;

    /** @var array<string, string> Lower-cased name => name as originally written. */
    private readonly array $headerNames;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $content = '',
        private readonly int $status = 200,
        array $headers = [],
    ) {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP status code [%d].', $status));
        }

        $normalized = [];
        $names = [];
        foreach ($headers as $name => $value) {
            self::assertValidHeader($name, $value);
            $normalized[strtolower($name)] = $value;
            $names[strtolower($name)] = $name;
        }

        $this->headers = $normalized;
        $this->headerNames = $names;
    }

    /**
     * Creates an HTML response with the proper content type.
     */
    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Creates a JSON response. Encoding errors throw instead of silently
     * producing an empty body.
     *
     * @throws \JsonException
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new self($json, $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    /**
     * Creates a redirect response. 303 makes browsers follow a POST with a GET.
     */
    public static function redirect(string $location, int $status = 303): self
    {
        if ($status < 300 || $status > 399) {
            throw new InvalidArgumentException(sprintf('Invalid redirect status code [%d].', $status));
        }

        return new self('', $status, ['Location' => $location]);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * @return array<string, string> Header names as originally written.
     */
    public function headers(): array
    {
        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[$this->headerNames[$key]] = $value;
        }

        return $headers;
    }

    /**
     * Returns a copy of the response with the given header set (replacing any previous value).
     */
    public function withHeader(string $name, string $value): self
    {
        $headers = $this->headers();
        foreach (array_keys($headers) as $existing) {
            if (strcasecmp($existing, $name) === 0) {
                unset($headers[$existing]);
            }
        }
        $headers[$name] = $value;

        return new self($this->content, $this->status, $headers);
    }

    /**
     * Emits the status line, headers and body to the client.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers() as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }

        echo $this->content;
    }

    private static function assertValidHeader(string $name, string $value): void
    {
        if (preg_match('/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid header name [%s].', $name));
        }

        // Rejecting CR/LF prevents HTTP response splitting.
        if (preg_match('/[\r\n]/', $value) === 1) {
            throw new InvalidArgumentException(sprintf('Invalid value for header [%s].', $name));
        }
    }
}
