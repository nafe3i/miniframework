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
    /**
     * Escapes a value for safe output in HTML text and attribute contexts.
     */
    public function e(string|int|float|bool|Stringable|null $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
