<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use InvalidArgumentException;
use MiniForge\Core\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testReadsNestedValuesWithDotNotation(): void
    {
        $config = new Config(['database' => ['sqlite' => ['path' => ':memory:']]]);

        self::assertSame(':memory:', $config->get('database.sqlite.path'));
        self::assertSame(['path' => ':memory:'], $config->get('database.sqlite'));
    }

    public function testReturnsDefaultForMissingKeys(): void
    {
        $config = new Config(['app' => ['debug' => false]]);

        self::assertSame('x', $config->get('app.missing', 'x'));
        self::assertNull($config->get('nope.deeper'));
        self::assertNull($config->get('app.debug.too.deep'));
    }

    public function testDistinguishesFalsyValuesFromMissingOnes(): void
    {
        $config = new Config(['app' => ['debug' => false, 'name' => null]]);

        self::assertFalse($config->get('app.debug', true));
        self::assertTrue($config->has('app.debug'));
        self::assertTrue($config->has('app.name'));
        self::assertFalse($config->has('app.missing'));
    }

    public function testLoadsFilesFromDirectory(): void
    {
        $config = Config::fromDirectory(__DIR__ . '/../../Fixtures/config');

        self::assertSame('Fixture App', $config->get('app.name'));
        self::assertFalse($config->get('app.debug'));
    }

    public function testRejectsMissingDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Config::fromDirectory(__DIR__ . '/missing');
    }

    public function testProjectConfigurationLoads(): void
    {
        $config = Config::fromDirectory(__DIR__ . '/../../../config');

        self::assertIsString($config->get('app.name'));
        self::assertIsBool($config->get('app.debug'));
    }
}
