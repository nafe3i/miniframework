<?php

declare(strict_types=1);

namespace MiniForge\Http;

use MiniForge\Exceptions\ExceptionHandler;
use MiniForge\Middleware\Pipeline;
use MiniForge\Routing\Dispatcher;
use MiniForge\Routing\Router;
use Throwable;

/**
 * HTTP kernel: turns a Request into a Response.
 *
 *     Request -> global middleware -> Router -> Dispatcher -> Controller
 *
 * It only orchestrates the pipeline and holds no business logic, which keeps
 * it testable without a web server. Exceptions raised by routing or
 * controllers become error responses that still travel back through the
 * middleware (e.g. to receive security headers); exceptions raised by a
 * middleware itself are converted at the outermost level.
 */
final class Kernel
{
    private readonly Pipeline $pipeline;

    /**
     * @param list<\MiniForge\Contracts\MiddlewareInterface> $middleware Global middleware, in execution order
     */
    public function __construct(
        private readonly Router $router,
        private readonly Dispatcher $dispatcher,
        private readonly ExceptionHandler $exceptionHandler,
        array $middleware = [],
    ) {
        $this->pipeline = new Pipeline($middleware);
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->pipeline->handle($request, $this->dispatchToRoute(...));
        } catch (Throwable $exception) {
            return $this->exceptionHandler->render($exception);
        }
    }

    private function dispatchToRoute(Request $request): Response
    {
        try {
            $match = $this->router->match($request);

            return $this->dispatcher->dispatch($match, $request);
        } catch (Throwable $exception) {
            return $this->exceptionHandler->render($exception);
        }
    }
}
