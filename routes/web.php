<?php

declare(strict_types=1);

/*
 * Route declarations: METHOD + path pattern => [Controller::class, 'action'].
 */

use App\Controllers\HomeController;
use App\Controllers\TaskController;
use MiniForge\Routing\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);

    // Task Manager
    $router->get('/tasks', [TaskController::class, 'index']);
};
