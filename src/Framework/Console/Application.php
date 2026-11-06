<?php

declare(strict_types=1);

namespace MiniForge\Console;

use InvalidArgumentException;
use MiniForge\Contracts\CommandInterface;

/**
 * Console entry point: finds the command named on the command line and
 * executes it.
 *
 *     php bin/forge <command> [arguments...]
 */
final class Application
{
    /** @var array<string, CommandInterface> */
    private array $commands = [];

    /**
     * @param list<CommandInterface> $commands
     */
    public function __construct(array $commands = [])
    {
        foreach ($commands as $command) {
            $this->add($command);
        }
    }

    public function add(CommandInterface $command): void
    {
        if (isset($this->commands[$command->name()])) {
            throw new InvalidArgumentException(sprintf('Command [%s] is already registered.', $command->name()));
        }

        $this->commands[$command->name()] = $command;
    }

    /**
     * @param list<string> $argv Raw arguments, $argv[0] being the script name
     * @return int Exit code
     */
    public function run(array $argv, Output $output): int
    {
        $name = $argv[1] ?? 'list';
        $arguments = array_slice($argv, 2);

        if ($name === 'list' || $name === 'help' || $name === '--help') {
            $this->listCommands($output);

            return 0;
        }

        if (!isset($this->commands[$name])) {
            $output->writeln(sprintf('Command "%s" is not defined.', $name));
            $output->writeln();
            $this->listCommands($output);

            return 1;
        }

        return $this->commands[$name]->execute($arguments, $output);
    }

    private function listCommands(Output $output): void
    {
        $output->writeln('MiniForge console');
        $output->writeln();
        $output->writeln('Usage: php bin/forge <command> [arguments]');
        $output->writeln();
        $output->writeln('Available commands:');

        $width = max([4, ...array_map('strlen', array_keys($this->commands))]);
        $output->writeln(sprintf('  %-' . $width . 's  %s', 'list', 'List available commands'));

        ksort($this->commands);
        foreach ($this->commands as $command) {
            $output->writeln(sprintf('  %-' . $width . 's  %s', $command->name(), $command->description()));
        }
    }
}
