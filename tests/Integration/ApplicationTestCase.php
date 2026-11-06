<?php

declare(strict_types=1);

namespace Tests\Integration;

use MiniForge\Container\Container;
use MiniForge\Core\Config;
use MiniForge\Database\Connection;
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

        // Isolated in-memory database with the real schema (never the user's data).
        $this->db()->executeScript((string) file_get_contents(__DIR__ . '/../../database/schema.sql'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function config(): array
    {
        return [
            'app' => ['name' => 'Test App', 'debug' => false],
            'database' => ['driver' => 'sqlite', 'path' => ':memory:'],
        ];
    }

    protected function db(): Connection
    {
        return $this->container->get(Connection::class);
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
