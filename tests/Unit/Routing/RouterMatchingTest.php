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
}
