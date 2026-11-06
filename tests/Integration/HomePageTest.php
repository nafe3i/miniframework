<?php

declare(strict_types=1);

namespace Tests\Integration;

final class HomePageTest extends ApplicationTestCase
{
    public function testHomePageIsRenderedByControllerInsideLayout(): void
    {
        $response = $this->request('GET', '/');

        self::assertSame(200, $response->status());
        self::assertSame('text/html; charset=UTF-8', $response->header('Content-Type'));
        self::assertStringContainsString('<!doctype html>', $response->content());
        self::assertStringContainsString('<title>Home · MiniForge</title>', $response->content());
        self::assertStringContainsString('<h1>Test App</h1>', $response->content());
    }

    public function testUnknownPageReturns404(): void
    {
        self::assertSame(404, $this->request('GET', '/does-not-exist')->status());
    }

    public function testSecurityHeadersMiddlewareIsAppliedGlobally(): void
    {
        self::assertSame('nosniff', $this->request('GET', '/')->header('X-Content-Type-Options'));
        self::assertSame('DENY', $this->request('GET', '/does-not-exist')->header('X-Frame-Options'));
    }
}
