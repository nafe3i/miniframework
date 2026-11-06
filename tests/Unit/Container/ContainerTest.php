<?php

declare(strict_types=1);

namespace Tests\Unit\Container;

use ArrayIterator;
use Countable;
use MiniForge\Container\Container;
use MiniForge\Contracts\ContainerInterface;
use MiniForge\Exceptions\ContainerException;
use MiniForge\Exceptions\ServiceNotFoundException;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ContainerTest extends TestCase
{
    public function testImplementsContainerContract(): void
    {
        self::assertInstanceOf(ContainerInterface::class, new Container());
    }

    public function testRegistersBinding(): void
    {
        $container = new Container();
        self::assertFalse($container->has('logger'));

        $container->bind('logger', static fn (): stdClass => new stdClass());

        self::assertTrue($container->has('logger'));
    }

    public function testResolvesBindingThroughFactory(): void
    {
        $container = new Container();
        $container->bind('config', static fn (): array => ['debug' => true]);

        self::assertSame(['debug' => true], $container->get('config'));
    }

    public function testBindingProducesNewInstanceEachTime(): void
    {
        $container = new Container();
        $container->bind(stdClass::class, static fn (): stdClass => new stdClass());

        self::assertNotSame($container->get(stdClass::class), $container->get(stdClass::class));
    }

    public function testBindsInterfaceToImplementation(): void
    {
        $container = new Container();
        $container->bind(Countable::class, static fn (): ArrayIterator => new ArrayIterator([1, 2, 3]));

        $service = $container->get(Countable::class);

        self::assertInstanceOf(Countable::class, $service);
        self::assertCount(3, $service);
    }

    public function testFactoryReceivesContainerToResolveDependencies(): void
    {
        $container = new Container();
        $container->bind('greeting', static fn (): string => 'Hello');
        $container->bind('message', static fn (ContainerInterface $c): string => $c->get('greeting') . ', world');

        self::assertSame('Hello, world', $container->get('message'));
    }

    public function testRebindingReplacesPreviousBinding(): void
    {
        $container = new Container();
        $container->bind('value', static fn (): int => 1);
        $container->bind('value', static fn (): int => 2);

        self::assertSame(2, $container->get('value'));
    }

    public function testSingletonIsBuiltLazilyOnceAndShared(): void
    {
        $container = new Container();
        $builds = 0;
        $container->singleton('db', static function () use (&$builds): stdClass {
            $builds++;

            return new stdClass();
        });

        self::assertSame(0, $builds, 'A singleton must not be built before it is requested.');
        self::assertSame($container->get('db'), $container->get('db'));
        self::assertSame(1, $builds);
    }

    public function testRegistersPrebuiltInstance(): void
    {
        $container = new Container();
        $object = new stdClass();
        $container->instance('object', $object);

        self::assertTrue($container->has('object'));
        self::assertSame($object, $container->get('object'));
    }

    public function testRebindingDiscardsPreviouslySharedInstance(): void
    {
        $container = new Container();
        $container->singleton('service', static fn (): stdClass => new stdClass());
        $first = $container->get('service');

        $container->bind('service', static fn (): stdClass => new stdClass());

        self::assertNotSame($first, $container->get('service'));
        self::assertNotSame($container->get('service'), $container->get('service'));
    }

    public function testDetectsCircularDependencies(): void
    {
        $container = new Container();
        $container->bind('a', static fn (ContainerInterface $c): mixed => $c->get('b'));
        $container->bind('b', static fn (ContainerInterface $c): mixed => $c->get('a'));

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected: a -> b -> a.');

        $container->get('a');
    }

    public function testContainerRecoversAfterFailedResolution(): void
    {
        $container = new Container();
        $fail = true;
        $container->bind('flaky', static function () use (&$fail): string {
            if ($fail) {
                throw new \RuntimeException('boom');
            }

            return 'ok';
        });

        try {
            $container->get('flaky');
        } catch (\RuntimeException) {
        }
        $fail = false;

        self::assertSame('ok', $container->get('flaky'));
    }

    public function testMissingServiceThrowsClearException(): void
    {
        $container = new Container();

        try {
            $container->get('App\\Missing');
            self::fail('Expected a ServiceNotFoundException.');
        } catch (ServiceNotFoundException $exception) {
            self::assertInstanceOf(ContainerException::class, $exception);
            self::assertStringContainsString('App\\Missing', $exception->getMessage());
        }
    }
}
