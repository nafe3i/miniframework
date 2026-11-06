# ADR-007 — Native PHP templates with explicit escaping

**Status:** accepted

## Problem
How are HTML views produced, and how is XSS prevented?

## Options
1. A compiled template language (Blade/Twig-like) with auto-escaping.
2. Native PHP templates rendered in an isolated scope, with an explicit escape helper.

## Decision
Option 2. `PhpViewRenderer` resolves names like `tasks/index` (or
`tasks.index`) inside `resources/views`, exposes data as local variables
and a `Template` helper as `$this`: `$this->e()`, `$this->layout()`,
`$this->insert()`.

## Justification
No compiler to write or learn; the mechanism is fully visible. Security is
handled by:
* **Explicit** escaping: every dynamic value is printed with `$this->e()`.
  This is *not* automatic and is documented as such.
* View names restricted to `[A-Za-z0-9_-]` segments and confined to the
  views directory (no traversal, no absolute path, no stream wrapper).
* Bounded nesting of layouts/partials (recursion fails fast).

## Consequences
* Forgetting `$this->e()` creates an XSS hole: integration tests check
  escaping of user data on every page.
* Layout content (`$content`) and partials are printed raw because they are
  already-rendered HTML.
