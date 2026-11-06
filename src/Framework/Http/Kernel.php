<?php

declare(strict_types=1);

namespace MiniForge\Http;

use MiniForge\Routing\Dispatcher;
use MiniForge\Routing\Router;

/**
 * HTTP kernel: turns a Request into a Response.
 *
 * It only orchestrates the pipeline (routing then dispatching) and holds no
 * business logic, which keeps it testable without a web server.
 */
final class Kernel
{
    public function __construct(
        private readonly Router $router,
        private readonly Dispatcher $dispatcher,
    ) {
    }

    public function handle(Request $request): Response
    {
        $match = $this->router->match($request);

        return $this->dispatcher->dispatch($match, $request);
    }
}
