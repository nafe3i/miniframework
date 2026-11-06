# ADR-001 — Framework separated from the application

**Status:** accepted

## Problem
The challenge asks for a mini framework *and* a small application using it.
If both are mixed, framework classes end up knowing about tasks, and the
framework cannot be understood or reused on its own.

## Options
1. One namespace, application code next to framework code.
2. Two namespaces in one repository: `MiniForge\` (`src/Framework`) and `App\` (`app/`).
3. Two Composer packages / repositories.

## Decision
Option 2. A composition root (`bootstrap/app.php`) is the only file that
references both sides.

## Justification
Gives a clear, enforceable dependency rule (framework never imports `App\`)
without the overhead of publishing a package for a V1.

## Consequences
* The framework can be read and tested without the demo application.
* Moving `src/Framework` to its own package later only requires a new `composer.json`.
* Discipline is required: the rule is checked by review, not by tooling.
