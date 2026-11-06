<?php

declare(strict_types=1);

/*
 * Database settings. SQLite is the only supported driver in V1.
 *
 * DB_PATH can point to another file (or ":memory:"). The default database
 * file lives in database/ and is ignored by Git.
 */
return [
    'driver' => 'sqlite',
    'path' => getenv('DB_PATH') ?: dirname(__DIR__) . '/database/database.sqlite',
];
