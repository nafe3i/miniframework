<?php

declare(strict_types=1);

namespace MiniForge\Contracts;

use MiniForge\Exceptions\ViewNotFoundException;

/**
 * Renders a named view into an HTML string.
 *
 * Controllers depend on this contract, not on the template engine.
 */
interface ViewRendererInterface
{
    /**
     * @param string $view Logical name such as "tasks/index" or "tasks.index"
     * @param array<string, mixed> $data Variables exposed to the template
     *
     * @throws ViewNotFoundException
     */
    public function render(string $view, array $data = []): string;

    public function exists(string $view): bool;
}
