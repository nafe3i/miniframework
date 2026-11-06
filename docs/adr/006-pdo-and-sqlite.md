# ADR-006 — PDO and SQLite

**Status:** accepted

## Problem
The challenge requires database access through PDO. Which database, and how much abstraction?

## Options
1. MySQL/PostgreSQL server.
2. SQLite file, through a thin `Connection` wrapper over PDO.
3. An ORM / query builder.

## Decision
SQLite through `MiniForge\Database\Connection`: `select`, `selectOne`,
`execute`, `executeScript`, `lastInsertId`. PDO is configured with
exceptions, associative fetches and real (non-emulated) prepared statements.
Every value is bound as a parameter. The schema (`database/schema.sql`) is
idempotent and mirrors the validation rules with `CHECK` constraints.

## Justification
Zero setup (no server), reproducible installs, `:memory:` databases for
isolated tests. A thin wrapper keeps SQL visible, which is the point of an
educational framework. An ORM is out of V1 scope.

## Consequences
* Only the `sqlite` driver is accepted in V1.
* PDO errors are wrapped in `DatabaseException` (details shown only in debug mode).
* No migrations system: one idempotent schema file + `composer db:init`.
