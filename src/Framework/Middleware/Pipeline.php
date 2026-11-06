<?php

declare(strict_types=1);

namespace MiniForge\Middleware;

use Closure;
use MiniForge\Contracts\MiddlewareInterface;
use MiniForge\Http\Request;
use MiniForge\Http\Response;

/**
 * Runs a request through middleware in declaration order, then through the
 * final handler. Responses travel back through the same chain in reverse.
 *
 *     [A, B] : A(before) -> B(before) -> handler -> B(after) -> A(after)
 */
final class Pipeline
{
    /** @var list<MiddlewareInterface> */
    private readonly array $middleware;

    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    /**
     * @param Closure(Request): Response $handler Final step (routing + controller)
     */
    public function handle(Request $request, Closure $handler): Response
    {
        $next = $handler;

        // Wrap from the last middleware to the first so the first runs first.
        foreach (array_reverse($this->middleware) as $middleware) {
            $next = static fn (Request $request): Response => $middleware->process($request, $next);
        }

        return $next($request);
    }
}
