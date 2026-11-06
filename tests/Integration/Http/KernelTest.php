<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use MiniForge\Container\Container;
use MiniForge\Http\Kernel;
use MiniForge\Http\Request;
use MiniForge\Routing\Dispatcher;
use MiniForge\Routing\Router;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\EchoController;

/**
 * Request -> Kernel -> Router -> Dispatcher -> Controller -> Response.
 */
final class KernelTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        $container = new Container();
        $container->bind(EchoController::class, static fn (): EchoController => new EchoController('di: '));

        $router = new Router();
        $router->get('/tasks', [EchoController::class, 'index']);
        $router->get('/tasks/{id}', [EchoController::class, 'show']);

        $this->kernel = new Kernel($router, new Dispatcher($container));
    }

    public function testHandlesStaticRouteThroughController(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/tasks'));

        self::assertSame(200, $response->status());
        self::assertSame('di: GET /tasks', $response->content());
    }

    public function testHandlesDynamicRouteThroughController(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/tasks/12'));

        self::assertSame('di: show 12', $response->content());
    }
}
