<?php

declare(strict_types=1);

namespace MiniForge\Http;

use MiniForge\Exceptions\ExceptionHandler;
use MiniForge\Routing\Dispatcher;
use MiniForge\Routing\Router;
use Throwable;

/**
 * HTTP kernel: turns a Request into a Response.
 *
 * It only orchestrates the pipeline (routing then dispatching) and holds no
 * business logic, which keeps it testable without a web server. Any
 * exception is converted into an error Response by the ExceptionHandler.
 */
final class Kernel
{
    public function __construct(
        private readonly Router $router,
        private readonly Dispatcher $dispatcher,
        private readonly ExceptionHandler $exceptionHandler,
    ) {
    }

    public function handle(Request $request): Response
    {
        try {
            $match = $this->router->match($request);

            return $this->dispatcher->dispatch($match, $request);
        } catch (Throwable $exception) {
            return $this->exceptionHandler->render($exception);
        }
    }
}
