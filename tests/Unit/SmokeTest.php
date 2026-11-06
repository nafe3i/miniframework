<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testAutoloadingAndRuntimeAreAvailable(): void
    {
        self::assertGreaterThanOrEqual(80200, PHP_VERSION_ID);
        self::assertTrue(extension_loaded('pdo_sqlite'));
    }
}
