<?php

declare(strict_types=1);

namespace Tests\Unit\Routing;

use InvalidArgumentException;
use MiniForge\Exceptions\DuplicateRouteException;
use MiniForge\Routing\Router;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\DummyController;

final class RouterRegistrationTest extends TestCase
{
    public function testRegistersGetAndPostRoutesInDeclarationOrder(): void
    {
        $router = new Router();
        $router->get('/tasks', [DummyController::class, 'index']);
        $router->post('/tasks', [DummyController::class, 'store']);

        $routes = $router->routes();

        self::assertCount(2, $routes);
        self::assertSame('GET', $routes[0]->method());
        self::assertSame('/tasks', $routes[0]->path());
        self::assertSame([DummyController::class, 'index'], $routes[0]->action());
        self::assertSame('POST', $routes[1]->method());
    }

    public function testNormalizesRoutePath(): void
    {
        $route = (new Router())->get('tasks/', [DummyController::class, 'index']);

        self::assertSame('/tasks', $route->path());
    }

    public function testDetectsParameters(): void
    {
        $router = new Router();
        $static = $router->get('/tasks', [DummyController::class, 'index']);
        $dynamic = $router->get('/tasks/{id}/edit', [DummyController::class, 'edit']);

        self::assertTrue($static->isStatic());
        self::assertFalse($dynamic->isStatic());
        self::assertSame(['id'], $dynamic->parameterNames());
    }

    public function testRejectsDuplicateRoute(): void
    {
        $router = new Router();
        $router->get('/tasks', [DummyController::class, 'index']);

        $this->expectException(DuplicateRouteException::class);
        $router->get('/tasks/', [DummyController::class, 'other']);
    }

    public function testRejectsRoutesThatOnlyDifferByParameterName(): void
    {
        $router = new Router();
        $router->get('/tasks/{id}', [DummyController::class, 'show']);

        $this->expectException(DuplicateRouteException::class);
        $router->get('/tasks/{task}', [DummyController::class, 'other']);
    }

    public function testSamePathWithDifferentMethodsIsAllowed(): void
    {
        $router = new Router();
        $router->get('/tasks', [DummyController::class, 'index']);
        $router->post('/tasks', [DummyController::class, 'store']);

        self::assertCount(2, $router->routes());
    }

    public function testRejectsRepeatedParameterNames(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Router())->get('/a/{id}/b/{id}', [DummyController::class, 'index']);
    }

    public function testRejectsMalformedAction(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Router())->add('GET', '/tasks', ['OnlyClass']);
    }
}
