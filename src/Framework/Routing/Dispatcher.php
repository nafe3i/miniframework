<?php

declare(strict_types=1);

namespace MiniForge\Routing;

use MiniForge\Contracts\ContainerInterface;
use MiniForge\Exceptions\DispatchException;
use MiniForge\Http\Request;
use MiniForge\Http\Response;
use ReflectionMethod;

/**
 * Invokes the controller action of a matched route.
 *
 * Route lookup is the Router's job; the Dispatcher only resolves the
 * controller from the container and calls the action as:
 *
 *     $controller->action(Request $request, ...$routeParameters)
 *
 * Route parameters are passed as named arguments, so "/tasks/{id}" maps to
 * an action parameter named $id.
 */
final class Dispatcher
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function dispatch(RouteMatch $match, Request $request): Response
    {
        [$class, $method] = $match->route()->action();

        $controller = $this->container->get($class);
        if (!is_object($controller)) {
            throw new DispatchException(sprintf('Service [%s] did not resolve to a controller object.', $class));
        }

        $arguments = $this->arguments($controller, $method, $match->parameters());
        $response = $controller->{$method}($request, ...$arguments);

        if (!$response instanceof Response) {
            throw new DispatchException(sprintf(
                'Action [%s::%s] must return %s, %s returned.',
                $class,
                $method,
                Response::class,
                get_debug_type($response),
            ));
        }

        return $response;
    }

    /**
     * Checks the action exists and declares every route parameter.
     *
     * @param array<string, string> $parameters
     * @return array<string, string>
     */
    private function arguments(object $controller, string $method, array $parameters): array
    {
        if (!method_exists($controller, $method) || !(new ReflectionMethod($controller, $method))->isPublic()) {
            throw new DispatchException(sprintf('Action [%s::%s] does not exist or is not public.', $controller::class, $method));
        }

        $declared = [];
        foreach ((new ReflectionMethod($controller, $method))->getParameters() as $parameter) {
            $declared[$parameter->getName()] = true;
        }

        foreach (array_keys($parameters) as $name) {
            if (!isset($declared[$name])) {
                throw new DispatchException(sprintf(
                    'Action [%s::%s] must declare a $%s parameter for the route parameter {%s}.',
                    $controller::class,
                    $method,
                    $name,
                    $name,
                ));
            }
        }

        return $parameters;
    }
}
