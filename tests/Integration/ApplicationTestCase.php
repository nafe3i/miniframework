<?php

declare(strict_types=1);

namespace Tests\Integration;

use MiniForge\Container\Container;
use MiniForge\Core\Config;
use MiniForge\Http\Kernel;
use MiniForge\Http\Request;
use MiniForge\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * Boots the real application (bootstrap/app.php) with test configuration.
 */
abstract class ApplicationTestCase extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        /** @var callable(?Config): Container $factory */
        $factory = require __DIR__ . '/../../bootstrap/app.php';
        $this->container = $factory(new Config($this->config()));
    }

    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return [
            'app' => ['name' => 'Test App', 'debug' => false],
        ];
    }

    /**
     * @param array<string, mixed> $input
     */
    protected function request(string $method, string $uri, array $input = []): Response
    {
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        /** @var array<string, mixed> $query */
        return $this->container->get(Kernel::class)->handle(new Request($method, $uri, $query, $input));
    }
}
