<?php

declare(strict_types=1);

namespace MiniForge\Contracts;

use MiniForge\Exceptions\ContainerException;
use MiniForge\Exceptions\ServiceNotFoundException;

/**
 * Read side of the service container: what consumers (the Dispatcher,
 * factories) need in order to obtain services by identifier.
 */
interface ContainerInterface
{
    public function has(string $id): bool;

    /**
     * @throws ServiceNotFoundException When no binding exists for the identifier
     * @throws ContainerException When the service cannot be built
     */
    public function get(string $id): mixed;
}
