# ADR-004 — Container with explicit bindings

**Status:** accepted

## Problem
Something must build the object graph. Laravel uses reflection-based
auto-wiring; is that needed for V1?

## Options
1. Reflection auto-wiring.
2. Explicit factory closures per service (`bind`, `singleton`, `instance`).

## Decision
Option 2. `Container::bind()` creates a new instance per resolution,
`singleton()` builds lazily once, `instance()` registers a built object.
Missing bindings raise `ServiceNotFoundException`; cycles raise `ContainerException`.

## Justification
Explicit wiring is easy to read, debug and explain, needs no reflection
magic, and keeps services lazy: `bin/forge route:list` never opens the database
because the `Connection` singleton is never requested.

## Consequences
* Adding a controller means adding one binding.
* Auto-wiring can be added later on top of the same `ContainerInterface` if the
  number of bindings becomes a burden.
