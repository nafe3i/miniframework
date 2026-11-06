<?php

declare(strict_types=1);

namespace MiniForge\View;

use Stringable;

/**
 * Object available as $this inside templates.
 *
 * Escaping is EXPLICIT: every dynamic value must be printed through
 * $this->e(...). Nothing is escaped automatically.
 */
final class Template
{
    private ?string $layout = null;

    /** @var array<string, mixed> */
    private array $layoutData = [];

    /**
     * Declares that the current template is wrapped by a layout view.
     *
     * The layout receives the template's rendered HTML as $content (print it
     * raw: it was already escaped by the child template) plus $data.
     *
     * @param array<string, mixed> $data Extra variables for the layout (e.g. a page title)
     */
    public function layout(string $view, array $data = []): void
    {
        $this->layout = $view;
        $this->layoutData = $data;
    }

    public function layoutName(): ?string
    {
        return $this->layout;
    }

    /**
     * @return array<string, mixed>
     */
    public function layoutData(): array
    {
        return $this->layoutData;
    }

    /**
     * Escapes a value for safe output in HTML text and attribute contexts.
     */
    public function e(string|int|float|bool|Stringable|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
