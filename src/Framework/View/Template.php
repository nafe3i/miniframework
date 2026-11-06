<?php

declare(strict_types=1);

namespace MiniForge\View;

use Closure;
use Stringable;

/**
 * Object available as $this inside templates.
 *
 * Escaping is EXPLICIT: every dynamic value must be printed through
 * $this->e(...). Nothing is escaped automatically.
 */
final class Template
{
    /** @var Closure(string, array<string, mixed>): string */
    private readonly Closure $renderer;

    private ?string $layout = null;

    /** @var array<string, mixed> */
    private array $layoutData = [];

    /**
     * @param Closure(string, array<string, mixed>): string $renderer Renders another view
     */
    public function __construct(Closure $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Renders another view (a partial) and returns its HTML, to be printed
     * raw: `<?= $this->insert('tasks/_form', ['task' => $task]) ?>`.
     * The partial only sees the variables passed explicitly.
     *
     * @param array<string, mixed> $data
     */
    public function insert(string $view, array $data = []): string
    {
        return ($this->renderer)($view, $data);
    }

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
