<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use Throwable;

/**
 * 404 — no resource exists at the requested location.
 */
class NotFoundHttpException extends HttpException
{
    public function __construct(string $message = 'Not Found', ?Throwable $previous = null)
    {
        parent::__construct(404, $message, [], $previous);
    }
}
