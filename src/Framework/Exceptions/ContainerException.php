<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use RuntimeException;

/**
 * A service could not be built by the container.
 */
class ContainerException extends RuntimeException
{
}
