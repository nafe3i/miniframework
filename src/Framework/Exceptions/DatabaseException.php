<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use RuntimeException;

/**
 * A database operation failed. The original PDOException is kept as
 * "previous" for debugging; it is never shown to clients outside debug mode.
 */
final class DatabaseException extends RuntimeException
{
}
