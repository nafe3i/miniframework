<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use LogicException;

/**
 * Raised at registration time when two routes would match the same requests.
 */
final class DuplicateRouteException extends LogicException
{
}
