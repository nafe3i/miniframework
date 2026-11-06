# ADR-005 — Interfaces only at useful boundaries

**Status:** accepted

## Problem
"Program to an interface" can lead to an interface for every class, which
adds indirection without benefit.

## Options
1. One interface per class.
2. Interfaces only where substitution or polymorphism is real.

## Decision
Option 2. Interfaces: `ContainerInterface`, `ViewRendererInterface`,
`MiddlewareInterface`, `CommandInterface`, `TaskRepositoryInterface`.
Stable concrete classes (`Router`, `Request`, `Response`, `Connection`,
`Validator`, `Config`) have none.

## Justification
Each interface has an identified reason: several implementations
(middleware, commands, repositories: PDO + in-memory), or a consumer that
must not depend on a detail (controllers vs. template engine, dispatcher vs.
container registration API). YAGNI for the rest.

## Consequences
* Fewer files, more direct code.
* Introducing an interface later for a concrete class is a local refactoring.
