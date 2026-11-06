<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use LogicException;

/**
 * A matched route could not be dispatched because of a programming error
 * (missing action method, unknown parameter, invalid return value...).
 */
final class DispatchException extends LogicException
{
}
