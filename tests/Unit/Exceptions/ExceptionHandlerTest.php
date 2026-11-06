<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use MiniForge\Exceptions\ExceptionHandler;
use MiniForge\Exceptions\HttpException;
use MiniForge\Exceptions\MethodNotAllowedHttpException;
use MiniForge\Exceptions\NotFoundHttpException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class ExceptionHandlerTest extends TestCase
{
    /** @var list<Throwable> */
    private array $reported = [];

    public function testNotFoundBecomes404(): void
    {
        $response = $this->handler()->render(new NotFoundHttpException('Task not found.'));

        self::assertSame(404, $response->status());
        self::assertStringContainsString('Task not found.', $response->content());
        self::assertSame([], $this->reported, 'Expected HTTP errors are not reported as failures.');
    }

    public function testMethodNotAllowedKeepsAllowHeader(): void
    {
        $response = $this->handler()->render(new MethodNotAllowedHttpException(['POST', 'GET']));

        self::assertSame(405, $response->status());
        self::assertSame('GET, POST', $response->header('Allow'));
    }

    public function testHttpExceptionMessageIsEscaped(): void
    {
        $response = $this->handler()->render(new HttpException(400, '<script>alert(1)</script>'));

        self::assertStringNotContainsString('<script>', $response->content());
        self::assertStringContainsString('&lt;script&gt;', $response->content());
    }

    public function testUnexpectedErrorHidesDetailsOutsideDebugMode(): void
    {
        $response = $this->handler(debug: false)->render(new RuntimeException('DB password is hunter2'));

        self::assertSame(500, $response->status());
        self::assertStringNotContainsString('hunter2', $response->content());
        self::assertStringNotContainsString('RuntimeException', $response->content());
        self::assertCount(1, $this->reported);
    }

    public function testUnexpectedErrorShowsDetailsInDebugMode(): void
    {
        $response = $this->handler(debug: true)->render(new RuntimeException('Boom <b>'));

        self::assertSame(500, $response->status());
        self::assertStringContainsString('RuntimeException', $response->content());
        self::assertStringContainsString('Boom &lt;b&gt;', $response->content());
    }

    private function handler(bool $debug = false): ExceptionHandler
    {
        return new ExceptionHandler($debug, function (Throwable $e): void {
            $this->reported[] = $e;
        });
    }
}
