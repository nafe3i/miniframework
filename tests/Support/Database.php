<?php

declare(strict_types=1);

namespace Tests\Support;

use MiniForge\Database\Connection;

/**
 * Creates isolated in-memory databases with the real project schema.
 */
final class Database
{
    public static function fresh(): Connection
    {
        $connection = Connection::fromConfig(['driver' => 'sqlite', 'path' => ':memory:']);
        $connection->executeScript((string) file_get_contents(__DIR__ . '/../../database/schema.sql'));

        return $connection;
    }
}
