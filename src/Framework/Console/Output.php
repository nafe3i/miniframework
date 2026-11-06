<?php

declare(strict_types=1);

namespace MiniForge\Console;

use InvalidArgumentException;

/**
 * Writes console text to a stream (STDOUT by default, memory in tests).
 */
final class Output
{
    /** @var resource */
    private $stream;

    /**
     * @param resource|null $stream
     */
    public function __construct($stream = null)
    {
        $stream ??= fopen('php://stdout', 'wb');
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Output requires a writable stream.');
        }

        $this->stream = $stream;
    }

    public function write(string $text): void
    {
        fwrite($this->stream, $text);
    }

    public function writeln(string $line = ''): void
    {
        $this->write($line . PHP_EOL);
    }
}
