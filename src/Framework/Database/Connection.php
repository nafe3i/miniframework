<?php

declare(strict_types=1);

namespace MiniForge\Database;

use InvalidArgumentException;
use MiniForge\Exceptions\DatabaseException;
use PDO;
use PDOException;

/**
 * Thin wrapper around PDO.
 *
 * The connection is configured to throw exceptions, return associative
 * arrays and use real (non-emulated) prepared statements.
 */
final class Connection
{
    public function __construct(private readonly PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * Opens a SQLite connection from configuration.
     *
     * @param array<string, mixed> $config ['driver' => 'sqlite', 'path' => file path or ':memory:']
     */
    public static function fromConfig(array $config): self
    {
        $driver = $config['driver'] ?? 'sqlite';
        if ($driver !== 'sqlite') {
            throw new InvalidArgumentException(sprintf('Unsupported database driver [%s]. Only "sqlite" is supported.', (string) $driver));
        }

        $path = $config['path'] ?? null;
        if (!is_string($path) || $path === '') {
            throw new InvalidArgumentException('The SQLite database path must be a non-empty string.');
        }

        if ($path !== ':memory:' && !is_dir(dirname($path))) {
            throw new DatabaseException(sprintf('The directory of the SQLite database [%s] does not exist.', $path));
        }

        try {
            $connection = new self(new PDO('sqlite:' . $path));
        } catch (PDOException $exception) {
            throw new DatabaseException('Could not open the SQLite database.', 0, $exception);
        }

        $connection->pdo->exec('PRAGMA foreign_keys = ON');

        return $connection;
    }

    public function driverName(): string
    {
        return (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
