<?php

declare(strict_types=1);

namespace MiniForge\Contracts;

use Closure;
use MiniForge\Http\Request;
use MiniForge\Http\Response;

/**
 * A step of the HTTP pipeline (Chain of Responsibility).
 *
 * A middleware may act before and/or after the rest of the chain by calling
 * $next($request), or short-circuit it by returning its own Response.
 */
interface MiddlewareInterface
{
    /**
     * @param Closure(Request): Response $next The rest of the pipeline
     */
    public function process(Request $request, Closure $next): Response;
}
