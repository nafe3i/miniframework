<?php

declare(strict_types=1);

namespace MiniForge\Contracts;

use MiniForge\Console\Output;

/**
 * A console command (Command pattern): the console Application only knows
 * this contract, so new commands are added without modifying it.
 */
interface CommandInterface
{
    /**
     * Name typed on the command line, e.g. "route:list".
     */
    public function name(): string;

    public function description(): string;

    /**
     * @param list<string> $arguments Arguments following the command name
     * @return int Process exit code (0 = success)
     */
    public function execute(array $arguments, Output $output): int;
}
