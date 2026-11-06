<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use MiniForge\Container\Container;
use MiniForge\Exceptions\ExceptionHandler;
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
        $router->get('/broken', [EchoController::class, 'invalid']);

        $this->kernel = new Kernel(
            $router,
            new Dispatcher($container),
            new ExceptionHandler(false, static function (): void {
            }),
        );
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

    public function testUnknownRouteProduces404Response(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/nope'));

        self::assertSame(404, $response->status());
    }

    public function testWrongMethodProduces405ResponseWithAllowHeader(): void
    {
        $response = $this->kernel->handle(new Request('POST', '/tasks/12'));

        self::assertSame(405, $response->status());
        self::assertSame('GET', $response->header('Allow'));
    }

    public function testProgrammingErrorProducesGeneric500(): void
    {
        $response = $this->kernel->handle(new Request('GET', '/broken'));

        self::assertSame(500, $response->status());
        self::assertStringNotContainsString('EchoController', $response->content());
    }
}
