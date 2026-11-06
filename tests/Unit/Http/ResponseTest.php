<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use InvalidArgumentException;
use MiniForge\Http\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testDefaultsToEmptyOkResponse(): void
    {
        $response = new Response();

        self::assertSame(200, $response->status());
        self::assertSame('', $response->content());
    }

    public function testHoldsContentAndStatus(): void
    {
        $response = new Response('Not here', 404);

        self::assertSame(404, $response->status());
        self::assertSame('Not here', $response->content());
    }

    public function testRejectsInvalidStatusCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response('', 999);
    }
}
