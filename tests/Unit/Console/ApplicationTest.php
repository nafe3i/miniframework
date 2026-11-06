<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use InvalidArgumentException;
use MiniForge\Console\Application;
use MiniForge\Console\Output;
use MiniForge\Contracts\CommandInterface;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase
{
    /** @var resource */
    private $stream;
    private Output $output;

    protected function setUp(): void
    {
        $stream = fopen('php://memory', 'w+b');
        self::assertIsResource($stream);
        $this->stream = $stream;
        $this->output = new Output($this->stream);
    }

    public function testRunsNamedCommandWithArguments(): void
    {
        $app = new Application([$this->greetCommand()]);

        $code = $app->run(['forge', 'greet', 'Ada', 'Grace'], $this->output);

        self::assertSame(0, $code);
        self::assertSame('Hello Ada, Grace' . PHP_EOL, $this->written());
    }

    public function testPropagatesCommandExitCode(): void
    {
        $failing = new class () implements CommandInterface {
            public function name(): string
            {
                return 'fail';
            }

            public function description(): string
            {
                return 'Always fails';
            }

            public function execute(array $arguments, Output $output): int
            {
                return 3;
            }
        };

        self::assertSame(3, (new Application([$failing]))->run(['forge', 'fail'], $this->output));
    }

    public function testListsCommandsByDefault(): void
    {
        $code = (new Application([$this->greetCommand()]))->run(['forge'], $this->output);

        self::assertSame(0, $code);
        self::assertStringContainsString('Available commands:', $this->written());
        self::assertMatchesRegularExpression('/greet\s+Greets people/', $this->written());
    }

    public function testUnknownCommandFailsWithHelp(): void
    {
        $code = (new Application([$this->greetCommand()]))->run(['forge', 'nope'], $this->output);

        self::assertSame(1, $code);
        self::assertStringContainsString('Command "nope" is not defined.', $this->written());
        self::assertStringContainsString('greet', $this->written());
    }

    public function testRejectsDuplicateCommandNames(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Application([$this->greetCommand(), $this->greetCommand()]);
    }

    private function written(): string
    {
        rewind($this->stream);

        return (string) stream_get_contents($this->stream);
    }

    private function greetCommand(): CommandInterface
    {
        return new class () implements CommandInterface {
            public function name(): string
            {
                return 'greet';
            }

            public function description(): string
            {
                return 'Greets people';
            }

            public function execute(array $arguments, Output $output): int
            {
                $output->writeln('Hello ' . implode(', ', $arguments));

                return 0;
            }
        };
    }
}
