<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

/**
 * No binding is registered for the requested service identifier.
 */
final class ServiceNotFoundException extends ContainerException
{
    public static function forId(string $id): self
    {
        return new self(sprintf('No binding registered for service [%s].', $id));
    }
}
