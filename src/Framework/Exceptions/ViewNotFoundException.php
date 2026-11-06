<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use RuntimeException;

/**
 * The requested view name is invalid or has no template file.
 */
final class ViewNotFoundException extends RuntimeException
{
}
