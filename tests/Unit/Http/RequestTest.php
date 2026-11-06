<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use MiniForge\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testExposesUppercasedMethod(): void
    {
        $request = new Request('post', '/tasks');

        self::assertSame('POST', $request->method());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pathProvider(): iterable
    {
        yield 'root' => ['/', '/'];
        yield 'empty' => ['', '/'];
        yield 'simple' => ['/tasks', '/tasks'];
        yield 'trailing slash' => ['/tasks/', '/tasks'];
        yield 'missing leading slash' => ['tasks/1', '/tasks/1'];
        yield 'query string stripped' => ['/tasks?page=2', '/tasks'];
    }

    #[DataProvider('pathProvider')]
    public function testNormalizesPath(string $raw, string $expected): void
    {
        self::assertSame($expected, (new Request('GET', $raw))->path());
    }

    public function testExposesQueryParameters(): void
    {
        $request = new Request('GET', '/tasks', ['page' => '2']);

        self::assertSame('2', $request->query('page'));
        self::assertSame(['page' => '2'], $request->allQuery());
    }

    public function testReturnsDefaultForMissingQueryParameter(): void
    {
        $request = new Request('GET', '/tasks');

        self::assertNull($request->query('page'));
        self::assertSame('1', $request->query('page', '1'));
    }
}
