<?php

declare(strict_types=1);

namespace App\Exceptions;

use MiniForge\Exceptions\NotFoundHttpException;

/**
 * Raised when a task identifier does not match any stored task.
 * Being a NotFoundHttpException, it is rendered as a 404 page.
 */
final class TaskNotFoundException extends NotFoundHttpException
{
    public static function withId(int|string $id): self
    {
        return new self(sprintf('Task #%s was not found.', (string) $id));
    }
}
