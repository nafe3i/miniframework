<?php

declare(strict_types=1);

/*
 * Route declarations: METHOD + path pattern => [Controller::class, 'action'].
 */

use App\Controllers\HomeController;
use MiniForge\Routing\Router;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
};
