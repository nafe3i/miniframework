<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Closure;
use MiniForge\Contracts\MiddlewareInterface;
use MiniForge\Http\Request;
use MiniForge\Http\Response;
use MiniForge\Middleware\Pipeline;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
    /** @var list<string> */
    private array $log = [];

    public function testWithoutMiddlewareCallsHandlerDirectly(): void
    {
        $response = (new Pipeline())->handle(new Request('GET', '/'), static fn (): Response => new Response('handler'));

        self::assertSame('handler', $response->content());
    }

    public function testRunsMiddlewareInDeclarationOrderAroundHandler(): void
    {
        $pipeline = new Pipeline([$this->tracer('A'), $this->tracer('B')]);

        $pipeline->handle(new Request('GET', '/'), function (): Response {
            $this->log[] = 'handler';

            return new Response('ok');
        });

        self::assertSame(['A:before', 'B:before', 'handler', 'B:after', 'A:after'], $this->log);
    }

    public function testMiddlewareCanPassModifiedRequestToNext(): void
    {
        $rewrite = new class () implements MiddlewareInterface {
            public function process(Request $request, Closure $next): Response
            {
                return $next(new Request($request->method(), '/rewritten'));
            }
        };

        $response = (new Pipeline([$rewrite]))->handle(
            new Request('GET', '/original'),
            static fn (Request $request): Response => new Response($request->path()),
        );

        self::assertSame('/rewritten', $response->content());
    }

    public function testMiddlewareCanModifyResponseOnTheWayBack(): void
    {
        $header = new class () implements MiddlewareInterface {
            public function process(Request $request, Closure $next): Response
            {
                return $next($request)->withHeader('X-Processed', 'yes');
            }
        };

        $response = (new Pipeline([$header]))->handle(new Request('GET', '/'), static fn (): Response => new Response());

        self::assertSame('yes', $response->header('X-Processed'));
    }

    public function testMiddlewareCanShortCircuitThePipeline(): void
    {
        $blocker = new class () implements MiddlewareInterface {
            public function process(Request $request, Closure $next): Response
            {
                return new Response('blocked', 403);
            }
        };

        $pipeline = new Pipeline([$this->tracer('A'), $blocker, $this->tracer('C')]);

        $response = $pipeline->handle(new Request('GET', '/'), function (): Response {
            $this->log[] = 'handler';

            return new Response('ok');
        });

        self::assertSame(403, $response->status());
        self::assertSame('blocked', $response->content());
        self::assertSame(['A:before', 'A:after'], $this->log, 'Neither C nor the handler may run.');
    }

    private function tracer(string $name): MiddlewareInterface
    {
        $log = &$this->log;

        return new class ($name, $log) implements MiddlewareInterface {
            /**
             * @param list<string> $log
             */
            public function __construct(private readonly string $name, private array &$log)
            {
            }

            public function process(Request $request, Closure $next): Response
            {
                $this->log[] = $this->name . ':before';
                $response = $next($request);
                $this->log[] = $this->name . ':after';

                return $response;
            }
        };
    }
}
