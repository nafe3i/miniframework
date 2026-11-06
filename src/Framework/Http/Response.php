<?php

declare(strict_types=1);

namespace MiniForge\Http;

use InvalidArgumentException;

/**
 * HTTP response: a status code, headers and a string body.
 */
class Response
{
    public function __construct(
        private readonly string $content = '',
        private readonly int $status = 200,
    ) {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException(sprintf('Invalid HTTP status code [%d].', $status));
        }
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }
}
