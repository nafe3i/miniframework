<?php

declare(strict_types=1);

namespace MiniForge\View;

use InvalidArgumentException;
use LogicException;
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

    private const MAX_LAYOUT_DEPTH = 10;

    private readonly string $basePath;

    private int $depth = 0;

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
        foreach (array_keys($data) as $name) {
            if (!is_string($name) || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1 || $name === 'this' || str_starts_with($name, '__')) {
                throw new InvalidArgumentException(sprintf('Invalid view variable name [%s].', (string) $name));
            }
        }

        if ($this->depth >= self::MAX_LAYOUT_DEPTH) {
            throw new LogicException(sprintf('Layout nesting deeper than %d levels (recursive layout?) while rendering [%s].', self::MAX_LAYOUT_DEPTH, $view));
        }

        $template = new Template();
        $content = $this->evaluate($this->resolve($view), $data, $template);

        $layout = $template->layoutName();
        if ($layout === null) {
            return $content;
        }

        $this->depth++;
        try {
            return $this->render($layout, [...$data, ...$template->layoutData(), 'content' => $content]);
        } finally {
            $this->depth--;
        }
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
     * Executes the template in an isolated scope, with each data entry exposed
     * as a local variable and a Template helper as $this, and captures its output.
     *
     * @param array<string, mixed> $data
     */
    private function evaluate(string $file, array $data, Template $template): string
    {
        $level = ob_get_level();
        ob_start();

        try {
            (function (string $__file, array $__data): void {
                extract($__data, EXTR_SKIP);
                include $__file;
            })->call($template, $file, $data);
        } catch (Throwable $exception) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $exception;
        }

        return (string) ob_get_clean();
    }
}
