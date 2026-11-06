# ADR-003 — Constructor dependency injection

**Status:** accepted

## Problem
Classes need collaborators (repository, renderer, router...). How do they get them?

## Options
1. Create them with `new` inside the class.
2. Service locator: pass the container and call `$container->get()` inside classes.
3. Constructor injection: declare dependencies as constructor parameters.

## Decision
Constructor injection everywhere. Only factories in the composition root and
the `Dispatcher` (which must instantiate a controller chosen at runtime) talk to the container.

## Justification
Dependencies are explicit and visible in signatures, classes are testable
with plain `new` and fakes, and no business class depends on the container.

## Consequences
* Controllers do not know the container exists.
* Every service needs a binding in `bootstrap/app.php` (see ADR-004).
