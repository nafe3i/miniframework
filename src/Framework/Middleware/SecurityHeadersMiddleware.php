<?php

declare(strict_types=1);

namespace MiniForge\Middleware;

use Closure;
use MiniForge\Contracts\MiddlewareInterface;
use MiniForge\Http\Request;
use MiniForge\Http\Response;

/**
 * Adds defensive HTTP headers to every response, unless the application
 * already set a value for them.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /** @var array<string, string> */
    private const DEFAULT_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'same-origin',
    ];

    /** @var array<string, string> */
    private readonly array $headers;

    /**
     * @param array<string, string> $headers Overrides / additions to the defaults
     */
    public function __construct(array $headers = [])
    {
        $this->headers = [...self::DEFAULT_HEADERS, ...$headers];
    }

    public function process(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach ($this->headers as $name => $value) {
            if ($response->header($name) === null) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}
