<?php

declare(strict_types=1);

namespace MiniForge\Routing;

use MiniForge\Exceptions\DuplicateRouteException;
use MiniForge\Exceptions\NotFoundHttpException;
use MiniForge\Http\Request;

/**
 * Stores route definitions and finds the route matching a request.
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function get(string $path, array $action): Route
    {
        return $this->add('GET', $path, $action);
    }

    /**
     * @param array{0: class-string, 1: string} $action
     */
    public function post(string $path, array $action): Route
    {
        return $this->add('POST', $path, $action);
    }

    /**
     * Registers a route. Two routes with the same method and the same pattern
     * (ignoring parameter names) are rejected, so the matching order never
     * silently hides a route.
     *
     * @param array<mixed> $action
     */
    public function add(string $method, string $path, array $action): Route
    {
        $route = new Route($method, $path, $action);

        foreach ($this->routes as $existing) {
            if ($existing->method() === $route->method() && $existing->signature() === $route->signature()) {
                throw new DuplicateRouteException(sprintf(
                    'Route [%s %s] conflicts with already registered route [%s %s].',
                    $route->method(),
                    $route->path(),
                    $existing->method(),
                    $existing->path(),
                ));
            }
        }

        $this->routes[] = $route;

        return $route;
    }

    /**
     * @return list<Route> Routes in declaration order.
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * Finds the route matching the request method and path.
     *
     * Ambiguity rule: static routes always win over dynamic ones (so
     * "/tasks/create" beats "/tasks/{id}" whatever the declaration order);
     * among dynamic routes, the first declared wins.
     *
     * @throws NotFoundHttpException When no route matches the path
     */
    public function match(Request $request): RouteMatch
    {
        foreach ($this->orderedRoutes() as $route) {
            if ($route->method() !== $request->method()) {
                continue;
            }

            $parameters = $route->matchPath($request->path());
            if ($parameters !== null) {
                return new RouteMatch($route, $parameters);
            }
        }

        throw new NotFoundHttpException(sprintf('No route matches [%s %s].', $request->method(), $request->path()));
    }

    /**
     * @return list<Route> Static routes first, then dynamic ones, each group in declaration order.
     */
    private function orderedRoutes(): array
    {
        $static = array_filter($this->routes, static fn (Route $route): bool => $route->isStatic());
        $dynamic = array_filter($this->routes, static fn (Route $route): bool => !$route->isStatic());

        return [...array_values($static), ...array_values($dynamic)];
    }
}
