# MiniForge PHP — Roadmap

## V1.0 (this version)

| Milestone | Content | State |
|-----------|---------|-------|
| M0 Foundation | Composer/PSR-4, PHPUnit, Git ignore rules | done |
| M1 HTTP & Routing | Request, Response, Router (static, dynamic, 404, 405 + Allow, conflicts) | done |
| M2 Controllers & Views | Container, Dispatcher, Kernel, exception handling, PHP views (data, escaping, layout, partials), front controller | done |
| M3 PDO & CRUD | SQLite connection, prepared queries, schema, Task entity, repository contract + PDO adapter, TaskService, Task Manager CRUD, error pages | done |
| M4 Bonuses | Middleware pipeline + security headers, Validator (required, string, max, in), JSON responses + read-only API, `bin/forge route:list` | done |
| M5 Quality | PHPStan level 8, docs, ADRs, demo script, clean-install check | done |

## Possible next steps (not in V1)

Ordered by usefulness for a real deployment:

1. **CSRF protection** — session + token middleware; required before any public exposure.
2. **Authentication** — the CRUD is open to anyone who can reach the server.
3. **Sessions & flash messages** — replace the `?notice=` whitelist.
4. **Route groups / per-route middleware** and named routes with URL generation.
5. **Method spoofing** (`_method=PUT|DELETE`) with tests, to offer REST-style routes.
6. **JSON error responses** chosen from the `Accept` header in the `ExceptionHandler`.
7. **Migrations** instead of a single idempotent schema file.
8. **Optional reflection auto-wiring** in the container.
9. **Pagination** of the task list.
