<?php

declare(strict_types=1);

namespace Tests\Integration\Console;

use PHPUnit\Framework\TestCase;

/**
 * Runs the real bin/forge script in a separate PHP process.
 */
final class ForgeCliTest extends TestCase
{
    public function testRouteListPrintsApplicationRoutesWithoutDatabase(): void
    {
        // An unusable database path: route:list must not try to open it.
        [$code, $stdout, $stderr] = $this->forge(['route:list'], ['DB_PATH' => __DIR__ . '/no-such-dir/db.sqlite']);

        self::assertSame(0, $code, $stderr);
        self::assertMatchesRegularExpression('#GET\s+/tasks\s+App\\\\Controllers\\\\TaskController@index#', $stdout);
        self::assertMatchesRegularExpression('#POST\s+/tasks/\{id\}/delete\s+App\\\\Controllers\\\\TaskController@destroy#', $stdout);
        self::assertMatchesRegularExpression('#GET\s+/api/tasks/\{id\}\s+App\\\\Controllers\\\\TaskApiController@show#', $stdout);
    }

    public function testWithoutArgumentsListsCommands(): void
    {
        [$code, $stdout] = $this->forge([]);

        self::assertSame(0, $code);
        self::assertStringContainsString('route:list', $stdout);
    }

    public function testUnknownCommandExitsWithError(): void
    {
        [$code, $stdout] = $this->forge(['does:not-exist']);

        self::assertSame(1, $code);
        self::assertStringContainsString('is not defined', $stdout);
    }

    /**
     * @param list<string> $arguments
     * @param array<string, string> $env
     * @return array{int, string, string}
     */
    private function forge(array $arguments, array $env = []): array
    {
        $command = [PHP_BINARY, dirname(__DIR__, 3) . '/bin/forge', ...$arguments];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, [...getenv(), ...$env]);
        self::assertIsResource($process);

        $stdout = (string) stream_get_contents($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $stdout, $stderr];
    }
}
