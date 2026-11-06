<?php

declare(strict_types=1);

/*
 * Composition root: the ONLY place where concrete implementations are chosen
 * and wired together. Everything else receives its dependencies through its
 * constructor.
 *
 * Returns a factory so tests can build the application with their own
 * configuration (e.g. an in-memory database).
 */

use App\Contracts\TaskRepositoryInterface;
use App\Controllers\HomeController;
use App\Controllers\TaskController;
use App\Repositories\PdoTaskRepository;
use App\Services\TaskService;
use MiniForge\Container\Container;
use MiniForge\Contracts\ContainerInterface;
use MiniForge\Contracts\ViewRendererInterface;
use MiniForge\Core\Config;
use MiniForge\Database\Connection;
use MiniForge\Exceptions\ExceptionHandler;
use MiniForge\Http\Kernel;
use MiniForge\Routing\Dispatcher;
use MiniForge\Routing\Router;
use MiniForge\View\PhpViewRenderer;

return static function (?Config $config = null): Container {
    $basePath = dirname(__DIR__);
    $config ??= Config::fromDirectory($basePath . '/config');

    $container = new Container();
    $container->instance(Config::class, $config);

    // --- Framework services -------------------------------------------------
    $container->singleton(Router::class, static function () use ($basePath): Router {
        $router = new Router();
        (require $basePath . '/routes/web.php')($router);

        return $router;
    });

    $container->singleton(ViewRendererInterface::class, static fn (): ViewRendererInterface
        => new PhpViewRenderer($basePath . '/resources/views'));

    $container->singleton(ExceptionHandler::class, static fn (ContainerInterface $c): ExceptionHandler => new ExceptionHandler(
        (bool) $c->get(Config::class)->get('app.debug', false),
        null,
        $c->get(ViewRendererInterface::class),
    ));

    $container->singleton(Kernel::class, static fn (ContainerInterface $c): Kernel => new Kernel(
        $c->get(Router::class),
        new Dispatcher($c),
        $c->get(ExceptionHandler::class),
    ));

    // Built lazily: commands or pages that never touch the database never open it.
    $container->singleton(Connection::class, static function (ContainerInterface $c): Connection {
        /** @var array<string, mixed> $settings */
        $settings = $c->get(Config::class)->get('database', []);

        return Connection::fromConfig($settings);
    });

    // --- Application services -----------------------------------------------
    $container->singleton(TaskRepositoryInterface::class, static fn (ContainerInterface $c): TaskRepositoryInterface
        => new PdoTaskRepository($c->get(Connection::class)));

    $container->singleton(TaskService::class, static fn (ContainerInterface $c): TaskService
        => new TaskService($c->get(TaskRepositoryInterface::class)));

    // --- Application controllers --------------------------------------------
    $container->bind(HomeController::class, static fn (ContainerInterface $c): HomeController => new HomeController(
        $c->get(ViewRendererInterface::class),
        (string) $c->get(Config::class)->get('app.name', 'MiniForge'),
    ));

    $container->bind(TaskController::class, static fn (ContainerInterface $c): TaskController => new TaskController(
        $c->get(TaskService::class),
        $c->get(ViewRendererInterface::class),
    ));

    return $container;
};
