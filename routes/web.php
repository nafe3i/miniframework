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
    $router->get('/tasks/create', [TaskController::class, 'create']);
    $router->post('/tasks', [TaskController::class, 'store']);
    $router->get('/tasks/{id}', [TaskController::class, 'show']);
    $router->get('/tasks/{id}/edit', [TaskController::class, 'edit']);
    $router->post('/tasks/{id}/update', [TaskController::class, 'update']);
    $router->post('/tasks/{id}/delete', [TaskController::class, 'destroy']);
};
