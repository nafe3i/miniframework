<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use MiniForge\Http\Request;
use MiniForge\Http\Response;
use MiniForge\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testAddsDefaultSecurityHeaders(): void
    {
        $response = (new SecurityHeadersMiddleware())->process(
            new Request('GET', '/'),
            static fn (): Response => Response::html('ok'),
        );

        self::assertSame('nosniff', $response->header('X-Content-Type-Options'));
        self::assertSame('DENY', $response->header('X-Frame-Options'));
        self::assertSame('same-origin', $response->header('Referrer-Policy'));
        self::assertSame('ok', $response->content());
    }

    public function testKeepsHeadersAlreadySetByTheApplication(): void
    {
        $response = (new SecurityHeadersMiddleware())->process(
            new Request('GET', '/'),
            static fn (): Response => new Response('', 200, ['X-Frame-Options' => 'SAMEORIGIN']),
        );

        self::assertSame('SAMEORIGIN', $response->header('X-Frame-Options'));
    }

    public function testAcceptsCustomHeaders(): void
    {
        $response = (new SecurityHeadersMiddleware(['Permissions-Policy' => 'camera=()']))->process(
            new Request('GET', '/'),
            static fn (): Response => new Response(),
        );

        self::assertSame('camera=()', $response->header('Permissions-Policy'));
        self::assertSame('nosniff', $response->header('X-Content-Type-Options'));
    }
}
