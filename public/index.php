<?php

declare(strict_types=1);

/*
 * Front Controller: every HTTP request enters the application here.
 *
 *     php -S localhost:8000 -t public
 */

use MiniForge\Http\Kernel;
use MiniForge\Http\Request;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = (require dirname(__DIR__) . '/bootstrap/app.php')();

/** @var Kernel $kernel */
$kernel = $container->get(Kernel::class);

$kernel->handle(Request::fromGlobals())->send();
