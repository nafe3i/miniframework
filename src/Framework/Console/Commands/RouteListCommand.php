<?php

declare(strict_types=1);

namespace MiniForge\Console\Commands;

use MiniForge\Console\Output;
use MiniForge\Contracts\CommandInterface;
use MiniForge\Routing\Router;

/**
 * php bin/forge route:list — prints every declared route.
 *
 * Depends on the Router only: no web server or database is needed.
 */
final class RouteListCommand implements CommandInterface
{
    public function __construct(private readonly Router $router)
    {
    }

    public function name(): string
    {
        return 'route:list';
    }

    public function description(): string
    {
        return 'List the declared routes with their methods and actions';
    }

    public function execute(array $arguments, Output $output): int
    {
        $rows = [];
        foreach ($this->router->routes() as $route) {
            [$class, $method] = $route->action();
            $rows[] = [$route->method(), $route->path(), $class . '@' . $method];
        }

        if ($rows === []) {
            $output->writeln('No routes declared.');

            return 0;
        }

        $header = ['METHOD', 'PATH', 'ACTION'];
        $widths = [];
        foreach ([$header, ...$rows] as $row) {
            foreach ($row as $index => $cell) {
                $widths[$index] = max($widths[$index] ?? 0, strlen($cell));
            }
        }

        $format = sprintf('%%-%ds  %%-%ds  %%s', $widths[0], $widths[1]);
        $output->writeln(sprintf($format, ...$header));
        foreach ($rows as $row) {
            $output->writeln(sprintf($format, ...$row));
        }

        $output->writeln();
        $output->writeln(sprintf('%d route(s).', count($rows)));

        return 0;
    }
}
