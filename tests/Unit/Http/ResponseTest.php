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

    public function testHeadersAreCaseInsensitiveAndKeepOriginalNames(): void
    {
        $response = new Response('', 200, ['X-Custom' => 'yes']);

        self::assertSame('yes', $response->header('x-custom'));
        self::assertSame(['X-Custom' => 'yes'], $response->headers());
        self::assertNull($response->header('Missing'));
    }

    public function testWithHeaderReturnsModifiedCopy(): void
    {
        $original = new Response('body', 201, ['x-a' => '1']);
        $copy = $original->withHeader('X-A', '2')->withHeader('X-B', '3');

        self::assertSame('1', $original->header('X-A'));
        self::assertNull($original->header('X-B'));
        self::assertSame('2', $copy->header('x-a'));
        self::assertSame('3', $copy->header('x-b'));
        self::assertSame(201, $copy->status());
        self::assertSame('body', $copy->content());
    }

    public function testHtmlFactorySetsContentType(): void
    {
        $response = Response::html('<p>Hi</p>', 201);

        self::assertSame('text/html; charset=UTF-8', $response->header('Content-Type'));
        self::assertSame(201, $response->status());
        self::assertSame('<p>Hi</p>', $response->content());
    }

    public function testRedirectFactorySetsLocationAndSeeOther(): void
    {
        $response = Response::redirect('/tasks/1');

        self::assertSame(303, $response->status());
        self::assertSame('/tasks/1', $response->header('Location'));
    }

    public function testRedirectRejectsNonRedirectStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Response::redirect('/tasks', 200);
    }

    public function testRejectsHeaderValuesWithLineBreaks(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response('', 200, ['Location' => "/ok\r\nSet-Cookie: evil=1"]);
    }

    public function testRejectsInvalidHeaderNames(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Response('', 200, ['Bad Header' => 'x']);
    }

    public function testSendEmitsBody(): void
    {
        $this->expectOutputString('<h1>Hello</h1>');

        Response::html('<h1>Hello</h1>')->send();
    }
}
