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

    public function testExposesFormInput(): void
    {
        $request = new Request('POST', '/tasks', [], ['title' => 'Write docs', 'status' => 'TODO']);

        self::assertSame('Write docs', $request->input('title'));
        self::assertSame('fallback', $request->input('missing', 'fallback'));
        self::assertSame(['title' => 'Write docs', 'status' => 'TODO'], $request->allInput());
    }

    public function testOnlyReturnsRequestedInputKeys(): void
    {
        $request = new Request('POST', '/tasks', [], ['title' => 'A', 'is_admin' => '1']);

        self::assertSame(['title' => 'A'], $request->only(['title', 'description']));
    }

    public function testQueryAndInputAreKeptSeparate(): void
    {
        $request = new Request('POST', '/tasks', ['title' => 'from-query'], ['title' => 'from-body']);

        self::assertSame('from-query', $request->query('title'));
        self::assertSame('from-body', $request->input('title'));
    }

    public function testHeadersAreCaseInsensitive(): void
    {
        $request = new Request('GET', '/', [], [], ['Accept' => 'application/json']);

        self::assertSame('application/json', $request->header('accept'));
        self::assertSame('application/json', $request->header('ACCEPT'));
        self::assertNull($request->header('X-Missing'));
    }

    public function testBuildsFromGlobals(): void
    {
        $request = Request::fromGlobals(
            [
                'REQUEST_METHOD' => 'post',
                'REQUEST_URI' => '/tasks/42/update?redirect=1',
                'HTTP_ACCEPT' => 'text/html',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
            ],
            ['redirect' => '1'],
            ['title' => 'Updated'],
        );

        self::assertSame('POST', $request->method());
        self::assertSame('/tasks/42/update', $request->path());
        self::assertSame('1', $request->query('redirect'));
        self::assertSame('Updated', $request->input('title'));
        self::assertSame('text/html', $request->header('Accept'));
        self::assertSame('XMLHttpRequest', $request->header('X-Requested-With'));
        self::assertSame('application/x-www-form-urlencoded', $request->header('Content-Type'));
    }

    public function testFromGlobalsFallsBackToSafeDefaults(): void
    {
        $request = Request::fromGlobals([], [], []);

        self::assertSame('GET', $request->method());
        self::assertSame('/', $request->path());
    }
}
