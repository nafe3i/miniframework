# ADR-008 — Atomic micro-commits

**Status:** accepted

## Problem
The history must show how the framework was built, and every step must be
verifiable.

## Options
1. One commit per module ("implement routing").
2. One commit per small, coherent, tested behaviour.

## Decision
Option 2, with Conventional Commits (`feat(routing): ...`, `fix(view): ...`).
For each change: implement → add tests → lint changed PHP files → run the
test suite → stage only the related files → `git diff --cached --check` → commit.

## Justification
Small commits are easy to review, to revert and to bisect, and the history
documents the design decisions in order.

## Consequences
* A commit never contains unrelated work and never lands with a red suite.
* Independent bugs found later get their own `fix(...)` commit with a
  regression test (e.g. `fix(view): bound partial nesting...`).
* Some units cannot be split further without creating stubs (e.g. a class
  implementing a whole interface); they are committed as one coherent unit.
