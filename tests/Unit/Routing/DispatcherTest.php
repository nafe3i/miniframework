<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use MiniForge\Container\Container;
use MiniForge\Exceptions\DispatchException;
use MiniForge\Exceptions\ServiceNotFoundException;
use MiniForge\Http\Request;
use MiniForge\Routing\Dispatcher;
use MiniForge\Routing\Route;
use MiniForge\Routing\RouteMatch;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\EchoController;

final class DispatcherTest extends TestCase
{
    private Container $container;
    private Dispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->bind(EchoController::class, static fn (): EchoController => new EchoController('injected: '));
        $this->dispatcher = new Dispatcher($this->container);
    }

    public function testInvokesControllerResolvedFromContainer(): void
    {
        $response = $this->dispatcher->dispatch(
            $this->match('GET', '/tasks', 'index'),
            new Request('GET', '/tasks'),
        );

        self::assertSame('injected: GET /tasks', $response->content());
    }

    public function testPassesRouteParametersByName(): void
    {
        $response = $this->dispatcher->dispatch(
            $this->match('GET', '/tasks/{id}', 'show', ['id' => '7']),
            new Request('GET', '/tasks/7'),
        );

        self::assertSame('injected: show 7', $response->content());
    }

    public function testParameterOrderDoesNotDependOnRouteOrder(): void
    {
        $response = $this->dispatcher->dispatch(
            $this->match('GET', '/p/{project}/t/{task}', 'nested', ['project' => '1', 'task' => '2']),
            new Request('GET', '/p/1/t/2'),
        );

        self::assertSame('project 1, task 2', $response->content());
    }

    public function testUnboundControllerIsReported(): void
    {
        $this->expectException(ServiceNotFoundException::class);

        (new Dispatcher(new Container()))->dispatch(
            $this->match('GET', '/tasks', 'index'),
            new Request('GET', '/tasks'),
        );
    }

    public function testMissingActionIsReported(): void
    {
        $this->expectException(DispatchException::class);
        $this->expectExceptionMessage('does not exist or is not public');

        $this->dispatcher->dispatch($this->match('GET', '/x', 'missing'), new Request('GET', '/x'));
    }

    public function testPrivateActionIsRejected(): void
    {
        $this->expectException(DispatchException::class);

        $this->dispatcher->dispatch($this->match('GET', '/x', 'hidden'), new Request('GET', '/x'));
    }

    public function testUndeclaredRouteParameterIsReported(): void
    {
        $this->expectException(DispatchException::class);
        $this->expectExceptionMessage('must declare a $slug parameter');

        $this->dispatcher->dispatch(
            $this->match('GET', '/tasks/{slug}', 'index', ['slug' => 'abc']),
            new Request('GET', '/tasks/abc'),
        );
    }

    public function testNonResponseReturnValueIsRejected(): void
    {
        $this->expectException(DispatchException::class);
        $this->expectExceptionMessage('must return');

        $this->dispatcher->dispatch($this->match('GET', '/x', 'invalid'), new Request('GET', '/x'));
    }

    /**
     * @param array<string, string> $parameters
     */
    private function match(string $method, string $path, string $action, array $parameters = []): RouteMatch
    {
        return new RouteMatch(new Route($method, $path, [EchoController::class, $action]), $parameters);
    }
}
