<?php

declare(strict_types=1);

/*
 * Application settings. Values can be overridden with environment variables.
 *
 * APP_DEBUG=1 shows exception details in error pages. Never enable it on a
 * publicly reachable server.
 */
return [
    'name' => getenv('APP_NAME') ?: 'MiniForge Task Manager',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL),
];
