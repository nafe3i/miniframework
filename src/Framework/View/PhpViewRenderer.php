<?php

declare(strict_types=1);

namespace MiniForge\View;

use InvalidArgumentException;
use MiniForge\Contracts\ViewRendererInterface;
use MiniForge\Exceptions\ViewNotFoundException;
use Throwable;

/**
 * Renders native PHP templates stored under a base directory.
 *
 * View names are restricted to letters, digits, "_" and "-" segments
 * separated by "/" or ".", so a name can never point outside the base
 * directory (no "..", no absolute path, no stream wrapper).
 */
final class PhpViewRenderer implements ViewRendererInterface
{
    private const NAME = '/^[A-Za-z0-9_-]+(?:[\/.][A-Za-z0-9_-]+)*$/';

    private readonly string $basePath;

    public function __construct(string $basePath)
    {
        $real = realpath($basePath);
        if ($real === false || !is_dir($real)) {
            throw new InvalidArgumentException(sprintf('View directory [%s] does not exist.', $basePath));
        }

        $this->basePath = $real;
    }

    public function render(string $view, array $data = []): string
    {
        return $this->evaluate($this->resolve($view));
    }

    public function exists(string $view): bool
    {
        try {
            $this->resolve($view);

            return true;
        } catch (ViewNotFoundException) {
            return false;
        }
    }

    /**
     * Maps a logical view name to an existing template file inside the base path.
     */
    private function resolve(string $view): string
    {
        if (preg_match(self::NAME, $view) !== 1) {
            throw new ViewNotFoundException(sprintf('Invalid view name [%s].', $view));
        }

        $relative = str_replace('.', DIRECTORY_SEPARATOR, str_replace('/', DIRECTORY_SEPARATOR, $view));
        $file = realpath($this->basePath . DIRECTORY_SEPARATOR . $relative . '.php');

        if ($file === false || !is_file($file) || !str_starts_with($file, $this->basePath . DIRECTORY_SEPARATOR)) {
            throw new ViewNotFoundException(sprintf('View [%s] not found.', $view));
        }

        return $file;
    }

    /**
     * Executes the template in an isolated scope and captures its output.
     */
    private function evaluate(string $file): string
    {
        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__file): void {
                include $__file;
            })($file);
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }

        return (string) ob_get_clean();
    }
}
