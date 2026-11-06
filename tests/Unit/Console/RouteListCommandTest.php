<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use MiniForge\Console\Commands\RouteListCommand;
use MiniForge\Console\Output;
use MiniForge\Routing\Router;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\DummyController;

final class RouteListCommandTest extends TestCase
{
    public function testPrintsRoutesInDeclarationOrder(): void
    {
        $router = new Router();
        $router->get('/tasks', [DummyController::class, 'index']);
        $router->post('/tasks/{id}/update', [DummyController::class, 'update']);

        [$code, $text] = $this->execute($router);

        self::assertSame(0, $code);
        $lines = explode(PHP_EOL, trim($text));
        self::assertMatchesRegularExpression('/^METHOD\s+PATH\s+ACTION$/', $lines[0]);
        self::assertMatchesRegularExpression('#^GET\s+/tasks\s+Tests\\\\Fixtures\\\\DummyController@index$#', $lines[1]);
        self::assertMatchesRegularExpression('#^POST\s+/tasks/\{id\}/update\s+Tests\\\\Fixtures\\\\DummyController@update$#', $lines[2]);
        self::assertSame('2 route(s).', end($lines));
    }

    public function testHandlesEmptyRouter(): void
    {
        [$code, $text] = $this->execute(new Router());

        self::assertSame(0, $code);
        self::assertSame('No routes declared.' . PHP_EOL, $text);
    }

    /**
     * @return array{int, string}
     */
    private function execute(Router $router): array
    {
        $stream = fopen('php://memory', 'w+b');
        self::assertIsResource($stream);

        $code = (new RouteListCommand($router))->execute([], new Output($stream));
        rewind($stream);

        return [$code, (string) stream_get_contents($stream)];
    }
}
