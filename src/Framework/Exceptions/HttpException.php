<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use RuntimeException;
use Throwable;

/**
 * An exception that maps directly to an HTTP error status.
 *
 * Its message is considered safe to show to the client.
 */
class HttpException extends RuntimeException
{
    /**
     * @param array<string, string> $headers Headers to add to the error response
     */
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        private readonly array $headers = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
