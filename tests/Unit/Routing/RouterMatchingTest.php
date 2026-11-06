<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use MiniForge\Http\Request;
use MiniForge\Routing\Router;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\DummyController;

final class RouterMatchingTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', [DummyController::class, 'home']);
        $this->router->get('/tasks', [DummyController::class, 'index']);
        $this->router->post('/tasks', [DummyController::class, 'store']);
        $this->router->get('/tasks/create', [DummyController::class, 'create']);
    }

    public function testMatchesValidGetRoute(): void
    {
        $match = $this->router->match(new Request('GET', '/tasks'));

        self::assertNotNull($match);
        self::assertSame([DummyController::class, 'index'], $match->route()->action());
        self::assertSame([], $match->parameters());
    }

    public function testMatchesValidPostRoute(): void
    {
        $match = $this->router->match(new Request('POST', '/tasks'));

        self::assertNotNull($match);
        self::assertSame([DummyController::class, 'store'], $match->route()->action());
    }

    public function testMatchesRootRoute(): void
    {
        $match = $this->router->match(new Request('GET', '/'));

        self::assertNotNull($match);
        self::assertSame('home', $match->route()->action()[1]);
    }

    public function testMatchesRegardlessOfTrailingSlash(): void
    {
        $match = $this->router->match(new Request('GET', '/tasks/create/'));

        self::assertNotNull($match);
        self::assertSame('create', $match->route()->action()[1]);
    }

    public function testExtractsDynamicParameter(): void
    {
        $this->router->get('/tasks/{id}', [DummyController::class, 'show']);

        $match = $this->router->match(new Request('GET', '/tasks/42'));

        self::assertNotNull($match);
        self::assertSame('show', $match->route()->action()[1]);
        self::assertSame(['id' => '42'], $match->parameters());
    }

    public function testExtractsSeveralParameters(): void
    {
        $this->router->get('/projects/{project}/tasks/{task}', [DummyController::class, 'nested']);

        $match = $this->router->match(new Request('GET', '/projects/7/tasks/9'));

        self::assertNotNull($match);
        self::assertSame(['project' => '7', 'task' => '9'], $match->parameters());
    }

    public function testParameterMatchesExactlyOneSegment(): void
    {
        $this->router->get('/tasks/{id}', [DummyController::class, 'show']);

        self::assertNull($this->router->match(new Request('GET', '/tasks/1/extra')));
    }

    public function testLiteralPartsAreNotTreatedAsRegex(): void
    {
        $this->router->get('/files/{name}.txt', [DummyController::class, 'file']);

        self::assertNotNull($this->router->match(new Request('GET', '/files/readme.txt')));
        self::assertNull($this->router->match(new Request('GET', '/files/readmeXtxt')));
    }

    public function testStaticRouteWinsOverDynamicRouteDeclaredFirst(): void
    {
        $router = new Router();
        $router->get('/tasks/{id}', [DummyController::class, 'show']);
        $router->get('/tasks/create', [DummyController::class, 'create']);

        $match = $router->match(new Request('GET', '/tasks/create'));

        self::assertNotNull($match);
        self::assertSame('create', $match->route()->action()[1]);
    }

    public function testFirstDeclaredDynamicRouteWinsOnAmbiguity(): void
    {
        $router = new Router();
        $router->get('/a/{x}/b', [DummyController::class, 'first']);
        $router->get('/a/b/{y}', [DummyController::class, 'second']);

        $match = $router->match(new Request('GET', '/a/b/b'));

        self::assertNotNull($match);
        self::assertSame('first', $match->route()->action()[1]);
        self::assertSame(['x' => 'b'], $match->parameters());
    }
}
