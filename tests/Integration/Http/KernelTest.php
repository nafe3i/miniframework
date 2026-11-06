<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use Closure;
use MiniForge\Container\Container;
use MiniForge\Contracts\MiddlewareInterface;
use MiniForge\Exceptions\ExceptionHandler;
use MiniForge\Http\Kernel;
use MiniForge\Http\Request;
use MiniForge\Http\Response;
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
        $this->kernel = $this->kernel();
    }

    public function testGlobalMiddlewareWrapsControllerResponses(): void
    {
        $response = $this->kernel([$this->stamp()])->handle(new Request('GET', '/tasks'));

        self::assertSame('di: GET /tasks', $response->content());
        self::assertSame('1', $response->header('X-Stamp'));
    }

    public function testErrorResponsesAlsoTravelThroughMiddleware(): void
    {
        $response = $this->kernel([$this->stamp()])->handle(new Request('GET', '/missing'));

        self::assertSame(404, $response->status());
        self::assertSame('1', $response->header('X-Stamp'));
    }

    public function testMiddlewareCanShortCircuitBeforeRouting(): void
    {
        $maintenance = new class () implements MiddlewareInterface {
            public function process(Request $request, Closure $next): Response
            {
                return new Response('Down for maintenance', 503);
            }
        };

        $response = $this->kernel([$maintenance])->handle(new Request('GET', '/does-not-exist'));

        self::assertSame(503, $response->status());
    }

    public function testFailingMiddlewareBecomesErrorResponse(): void
    {
        $broken = new class () implements MiddlewareInterface {
            public function process(Request $request, Closure $next): Response
            {
                throw new \RuntimeException('middleware failure');
            }
        };

        $response = $this->kernel([$broken])->handle(new Request('GET', '/tasks'));

        self::assertSame(500, $response->status());
        self::assertStringNotContainsString('middleware failure', $response->content());
    }

    /**
     * @param list<MiddlewareInterface> $middleware
     */
    private function kernel(array $middleware = []): Kernel
    {
        $container = new Container();
        $container->bind(EchoController::class, static fn (): EchoController => new EchoController('di: '));

        $router = new Router();
        $router->get('/tasks', [EchoController::class, 'index']);
        $router->get('/tasks/{id}', [EchoController::class, 'show']);
        $router->get('/broken', [EchoController::class, 'invalid']);

        return new Kernel(
            $router,
            new Dispatcher($container),
            new ExceptionHandler(false, static function (): void {
            }),
            $middleware,
        );
    }

    private function stamp(): MiddlewareInterface
    {
        return new class () implements MiddlewareInterface {
            public function process(Request $request, Closure $next): Response
            {
                return $next($request)->withHeader('X-Stamp', '1');
            }
        };
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
