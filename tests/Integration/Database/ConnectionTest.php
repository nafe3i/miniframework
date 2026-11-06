<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use InvalidArgumentException;
use MiniForge\Core\Config;
use MiniForge\Database\Connection;
use MiniForge\Exceptions\DatabaseException;
use PHPUnit\Framework\TestCase;

final class ConnectionTest extends TestCase
{
    public function testOpensInMemorySqliteConnection(): void
    {
        $connection = Connection::fromConfig(['driver' => 'sqlite', 'path' => ':memory:']);

        self::assertSame('sqlite', $connection->driverName());
    }

    public function testOpensFileDatabaseInTemporaryDirectory(): void
    {
        $file = sys_get_temp_dir() . '/miniforge-test-' . bin2hex(random_bytes(4)) . '.sqlite';

        try {
            Connection::fromConfig(['path' => $file]);
            self::assertSame('sqlite', Connection::fromConfig(['path' => $file])->driverName());
        } finally {
            @unlink($file);
        }
    }

    public function testRejectsUnsupportedDriver(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Connection::fromConfig(['driver' => 'mysql', 'path' => 'x']);
    }

    public function testRejectsMissingPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Connection::fromConfig(['driver' => 'sqlite']);
    }

    public function testReportsMissingDirectoryClearly(): void
    {
        $this->expectException(DatabaseException::class);

        Connection::fromConfig(['path' => __DIR__ . '/missing-dir/db.sqlite']);
    }

    public function testProjectDatabaseConfigurationIsValid(): void
    {
        $config = Config::fromDirectory(__DIR__ . '/../../../config');

        self::assertSame('sqlite', $config->get('database.driver'));
        self::assertIsString($config->get('database.path'));
    }
}
