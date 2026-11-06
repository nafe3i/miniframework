<?php

declare(strict_types=1);

/*
 * Creates the SQLite database file (if needed) and applies database/schema.sql.
 *
 *     php database/init.php        (or: composer db:init)
 *
 * The schema is idempotent, so running this twice is harmless and keeps data.
 */

use MiniForge\Core\Config;
use MiniForge\Database\Connection;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = Config::fromDirectory(dirname(__DIR__) . '/config');
/** @var array<string, mixed> $database */
$database = $config->get('database', []);

$connection = Connection::fromConfig($database);
$connection->executeScript((string) file_get_contents(__DIR__ . '/schema.sql'));

fwrite(STDOUT, sprintf("Database schema applied to [%s].\n", (string) $database['path']));
