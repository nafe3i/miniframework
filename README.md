# MiniForge PHP

A minimal, educational **PHP MVC framework** inspired by Laravel's core mechanisms,
built for the **Lara Academy challenge — "Construis ton mini Laravel"**, together
with a small **Task Manager** application that uses it.

The goal is not to reproduce Laravel but to show, in readable code, how a framework
works: front controller, router, dispatcher, service container, views, PDO database
access, middleware, validation, error handling and a CLI — each piece covered by tests.

| | |
|---|---|
| Language | PHP 8.2+ (`declare(strict_types=1)` everywhere) |
| Dependencies at runtime | none (only PHP extensions) |
| Dev tools | PHPUnit 11.5, PHPStan 2 (level 8) |
| Database | SQLite through PDO |
| Tests | 253 tests / 481 assertions (179 unit, 74 integration) |
| Status | V1 complete for local use — **not production-ready** (no CSRF, no auth) |

---

## Table of contents

1. [Quick start](#1-quick-start)
2. [Features](#2-features)
3. [How to use the application](#3-how-to-use-the-application)
4. [How to use the framework](#4-how-to-use-the-framework)
5. [Architecture](#5-architecture)
6. [Project structure](#6-project-structure)
7. [Tests and static analysis](#7-tests-and-static-analysis)
8. [How the project was built](#8-how-the-project-was-built)
9. [Guide for reviewers and AI assistants](#9-guide-for-reviewers-and-ai-assistants)
10. [Known limitations and issues](#10-known-limitations-and-issues)
11. [Documentation index](#11-documentation-index)
12. [AI assistance](#12-ai-assistance)
13. [License](#13-license)

---

## 1. Quick start

### Requirements

* **PHP 8.2 or newer** with the extensions `pdo_sqlite`, `mbstring` and `ctype`
  (check with `php -m`)
* **Composer 2**
* Git

### Install, initialize, run

```bash
git clone https://github.com/nafe3i/miniframework.git
cd miniframework
composer install
composer db:init
php -S localhost:8000 -t public
```

Open <http://localhost:8000>, then click **Tasks**.

### Check that everything works

```bash
composer test        # 253 tests, all green
composer analyse     # PHPStan level 8: "No errors"
php bin/forge route:list
```

### Configuration (environment variables)

| Variable | Default | Meaning |
|----------|---------|---------|
| `APP_DEBUG` | `false` | Show exception details on error pages. **Local use only.** |
| `APP_NAME` | `MiniForge Task Manager` | Title shown on the home page |
| `DB_PATH` | `database/database.sqlite` | SQLite file (or `:memory:`). Prefer an absolute path. |

```bash
# bash / Git Bash
APP_DEBUG=1 php -S localhost:8000 -t public
```

```powershell
# PowerShell
$env:APP_DEBUG = "1"; php -S localhost:8000 -t public
```

`composer db:init` (same as `php database/init.php`) creates the SQLite file and applies
`database/schema.sql`. The schema is idempotent: running it again keeps existing data.
The `.sqlite` file is git-ignored.

---

## 2. Features

### Framework (`MiniForge\` → `src/Framework/`)

| Area | What it does |
|------|--------------|
| HTTP | Immutable `Request` (method, normalized path, query, form input, headers, `fromGlobals()`); immutable `Response` (`html()`, `json()`, `redirect()`, `withHeader()`, `send()`); rejects CR/LF in headers |
| Routing | `GET`/`POST` routes, static and dynamic paths (`/tasks/{id}`), 404, 405 with a correct `Allow` header, duplicate-route detection, static-before-dynamic precedence |
| Dispatcher | Resolves the controller from the container, passes route parameters as **named arguments**, enforces a `Response` return value |
| Kernel | Middleware → routing → dispatch → response; every exception becomes an error response |
| Container | Explicit bindings (`bind`, `singleton`, `instance`), interface → implementation, lazy services, circular-dependency detection |
| Views | Native PHP templates, data as local variables, explicit escaping `$this->e()`, layouts, partials, safe view-name resolution, bounded nesting |
| Database | PDO wrapper for SQLite: `select`, `selectOne`, `execute`, `executeScript`, `lastInsertId`; prepared statements only; errors wrapped in `DatabaseException` |
| Middleware | `MiddlewareInterface`, ordered `Pipeline` with short-circuit support, `SecurityHeadersMiddleware` |
| Validation | `required`, `string`, `max:N`, `in:a,b`, structured errors (`field => [messages]`), HTTP 422 |
| Errors | `ExceptionHandler`: 404 / 405 / 422 / 500, application error view, internal details only in debug mode |
| Console | `php bin/forge route:list` (Command pattern) |
| Config | `config/*.php` with dot-notation access and environment overrides |

### Demo application (`App\` → `app/`)

Task Manager: list, show, create, edit, update and delete tasks (title, optional
description, status `TODO` / `IN_PROGRESS` / `DONE`), validation errors shown in the
form, styled 404 page, and a read-only JSON API.

---

## 3. How to use the application

| Method + URL | Page / effect |
|-----|------|
| `GET /` | Home page |
| `GET /tasks` | Task list (most recent first) |
| `GET /tasks/create` | Creation form |
| `POST /tasks` | Store a task → redirect to `/tasks/{id}?notice=created` (422 + errors if invalid) |
| `GET /tasks/{id}` | Task details (404 if unknown or not a positive integer) |
| `GET /tasks/{id}/edit` | Edit form |
| `POST /tasks/{id}/update` | Update → redirect (422 if invalid) |
| `POST /tasks/{id}/delete` | Delete → redirect to `/tasks?notice=deleted` |
| `GET /api/tasks` | JSON list: `{"data":[{"id":1,"title":"…","description":null,"status":"TODO"}]}` |
| `GET /api/tasks/{id}` | JSON task, or `404 {"error":"Task #42 was not found."}` |

Validation rules: title required (after trimming), at most 255 characters; description
optional, at most 2000 characters; status one of `TODO`, `IN_PROGRESS`, `DONE`
(default `TODO`). The same rules are enforced by `CHECK` constraints in `database/schema.sql`.

Try it from the command line:

```bash
curl -i -X POST -d "title=Write the README&status=TODO" http://localhost:8000/tasks
curl -s http://localhost:8000/api/tasks
curl -i -X PUT http://localhost:8000/tasks          # 405, Allow: GET, POST
```

A 5-minute demo script is in [docs/demo.md](docs/demo.md).

---

## 4. How to use the framework

### Declare a route — `routes/web.php`

```php
return static function (Router $router): void {
    $router->get('/tasks', [TaskController::class, 'index']);
    $router->get('/tasks/create', [TaskController::class, 'create']);
    $router->post('/tasks', [TaskController::class, 'store']);
    $router->get('/tasks/{id}', [TaskController::class, 'show']);
};
```

* `{name}` matches one non-empty path segment and is passed to the action as the
  **string** argument named `$name`.
* Static routes win over dynamic ones (`/tasks/create` vs `/tasks/{id}`); among dynamic
  routes, the first declared wins.
* Registering the same method + pattern twice throws `DuplicateRouteException`.
* No path match → 404; path matches with another method → 405 + `Allow`.

### Write a controller

Plain class, dependencies through the constructor; an action receives the `Request`,
then route parameters by name, and must return a `Response`:

```php
final class TaskController
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly ViewRendererInterface $views,
    ) {
    }

    public function show(Request $request, string $id): Response
    {
        $task = $this->tasks->get($this->taskId($id)); // 404 if missing

        return Response::html($this->views->render('tasks/show', ['task' => $task]));
    }
}
```

### Register it in the container — `bootstrap/app.php`

`bootstrap/app.php` is the **composition root**: the only place that chooses concrete
classes. Bindings are explicit (no reflection auto-wiring):

```php
$container->bind(TaskController::class, static fn (ContainerInterface $c): TaskController => new TaskController(
    $c->get(TaskService::class),
    $c->get(ViewRendererInterface::class),
));                                                    // new instance on each get()
$container->singleton(TaskRepositoryInterface::class, static fn (ContainerInterface $c): TaskRepositoryInterface
    => new PdoTaskRepository($c->get(Connection::class)));   // built lazily, once
$container->instance(Config::class, $config);         // already built object
```

Unknown services throw `ServiceNotFoundException`; circular dependencies throw
`ContainerException`.

### Write a view — `resources/views/tasks/show.php`

```php
<?php $this->layout('layouts/app', ['title' => $task->title()]); ?>
<h1><?= $this->e($task->title()) ?></h1>
<?= $this->insert('tasks/_form', ['old' => $old, 'errors' => $errors /* ... */]) ?>
```

* `$this->e($value)` escapes for HTML. **Escaping is explicit, not automatic.**
* `$this->layout(name, data)` wraps the page; the layout prints `$content` raw.
* `$this->insert(name, data)` renders a partial that only sees the data passed.
* View names are restricted to `[A-Za-z0-9_-]` segments and confined to `resources/views`.

### Query the database

```php
$db = Connection::fromConfig(['driver' => 'sqlite', 'path' => ':memory:']);
$db->execute('INSERT INTO tasks (title, status) VALUES (:title, :status)', ['title' => $title, 'status' => 'TODO']);
$id   = $db->lastInsertId();
$task = $db->selectOne('SELECT * FROM tasks WHERE id = ?', [$id]);
```

Values are always bound, never concatenated into SQL.

### Validate input

```php
$validator->validate($input, [
    'title'       => 'required|string|max:255',
    'description' => 'string|max:2000',          // optional: skipped when empty
    'status'      => 'required|in:TODO,IN_PROGRESS,DONE',
]); // throws ValidationException (422) with ['title' => ['The title is required.']]
```

### Add a middleware

```php
final class TimingMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);                 // rest of the pipeline

        return $response->withHeader('X-Response-Time', sprintf('%.1fms', (microtime(true) - $start) * 1000));
    }
}
```

Add it to the list passed to the `Kernel` in `bootstrap/app.php`. Middleware runs in
declaration order; returning a `Response` without calling `$next` short-circuits.

### Add a console command

Implement `MiniForge\Contracts\CommandInterface` (`name()`, `description()`,
`execute(array $arguments, Output $output): int`) and register it in `bin/forge`.

---

## 5. Architecture

```
Browser
  → public/index.php (Front Controller: Request::fromGlobals())
  → Http\Kernel::handle()
  → Middleware\Pipeline (SecurityHeadersMiddleware)
  → Routing\Router::match()        → RouteMatch(route, ['id' => '5'])
  → Routing\Dispatcher::dispatch() → Container::get(TaskController::class)
  → App\Controllers\TaskController::show($request, id: '5')
  → App\Services\TaskService::get(5)
  → App\Contracts\TaskRepositoryInterface ← App\Repositories\PdoTaskRepository
  → Database\Connection (PDO prepared statement) → SQLite
  → View\PhpViewRenderer ('tasks/show' inside 'layouts/app')
  → Http\Response → back through the middleware → send()
```

Dependency rules:

* The **framework** (`MiniForge\`) never references the application (`App\`).
* The **application** depends only on the framework's public API.
* Business code depends on **contracts** at useful boundaries
  (`TaskRepositoryInterface`, `ViewRendererInterface`, `ContainerInterface`,
  `MiddlewareInterface`, `CommandInterface`).
* Exceptions raised by routing or controllers become error responses that still travel
  through the middleware (so error pages also get the security headers).

Design patterns used: Front Controller, Composition Root, DI Container, Chain of
Responsibility (middleware pipeline), Command (CLI), Repository, Adapter (PDO),
Template View, Service Layer, static factory methods, immutable value objects.

More: [docs/architecture.md](docs/architecture.md) and the eight decision records in
[docs/adr/](docs/adr).

---

## 6. Project structure

```
├── app/                    Demo application (App\)
│   ├── Contracts/          TaskRepositoryInterface
│   ├── Controllers/        HomeController, TaskController, TaskApiController
│   ├── Entities/           Task, TaskStatus (enum)
│   ├── Exceptions/         TaskNotFoundException
│   ├── Repositories/       PdoTaskRepository
│   └── Services/           TaskService
├── bin/forge               Console entry point
├── bootstrap/app.php       Composition root (container bindings)
├── config/                 app.php, database.php
├── database/               schema.sql, init.php
├── docs/                   architecture.md, adr/, roadmap.md, demo.md
├── public/                 index.php (front controller), css/app.css
├── resources/views/        layouts/, tasks/, errors/, home.php
├── routes/web.php          Route declarations
├── src/Framework/          Framework core (MiniForge\)
│   ├── Console/  Container/  Contracts/  Core/  Database/  Exceptions/
│   └── Http/  Middleware/  Routing/  Validation/  View/
└── tests/
    ├── Unit/               Framework and domain classes in isolation
    ├── Integration/        Real application + in-memory SQLite, real CLI process
    ├── Support/            InMemoryTaskRepository, repository contract test case
    └── Fixtures/           Test controllers, views, config
```

---

## 7. Tests and static analysis

```bash
composer test                               # whole suite
vendor/bin/phpunit --testsuite Unit         # 179 tests
vendor/bin/phpunit --testsuite Integration  # 74 tests
vendor/bin/phpunit --testdox                # readable list of behaviours
vendor/bin/phpunit --filter RouterMatchingTest
composer analyse                            # PHPStan level 8
```

* Integration tests boot the real application (`bootstrap/app.php`) with an
  **in-memory SQLite database**; they never touch `database/database.sqlite`.
* The repository contract (`tests/Support/TaskRepositoryContractTestCase.php`) runs
  against **both** the PDO and the in-memory implementations.
* PHPUnit is strict: risky tests, warnings and unexpected output fail the run.

Last verified run: PHP 8.5.0, PHPUnit 11.5.57, PHPStan 2.3.5 →
`OK (253 tests, 481 assertions)` and `[OK] No errors`.

---

## 8. How the project was built

The project followed a written engineering contract and was built incrementally in
six milestones, each made of small tested steps:

| Milestone | Content |
|-----------|---------|
| M0 Foundation | Composer + PSR-4 autoloading, PHPUnit, Git ignore rules |
| M1 HTTP & Routing | Request, Response, Router (static, dynamic, 404, 405 + Allow, conflicts) |
| M2 Controllers & Views | Container, Dispatcher, Kernel, exception handling, PHP views, front controller |
| M3 PDO & CRUD | SQLite connection, schema, Task entity, repository contract + PDO adapter, TaskService, CRUD pages, error pages |
| M4 Bonuses | Middleware pipeline, Validator, JSON API, `bin/forge route:list` |
| M5 Quality | PHPStan level 8, architecture docs, ADRs, demo script, clean-install check |

Working method (see [ADR-008](docs/adr/008-atomic-micro-commits.md)):

1. Implement **one** small behaviour.
2. Add or update its tests.
3. Lint the changed PHP files and run the **whole** test suite.
4. Stage only the related files, check `git diff --cached --check`.
5. Make one **Conventional Commit** (`feat(routing): …`, `fix(view): …`).

The Git history therefore tells the story of the framework in order. Read it with:

```bash
git log --oneline --reverse
```

Good commits to study: the router built in five steps (`feat(routing): …`), the
container in three (`feat(container): …`), the first end-to-end page
(`feat(app): serve a home page through the front controller`), a real bug fix with a
regression test (`fix(view): bound partial nesting to stop infinite recursion`) and a
refactoring (`refactor(tasks): validate task input with the framework Validator`).

---

## 9. Guide for reviewers and AI assistants

This section is written so that a human reviewer or an AI assistant can install, run
and audit the project without prior context.

### 9.1 Run and verify (≈ 5 minutes)

```bash
git clone https://github.com/nafe3i/miniframework.git && cd miniframework
php -v                                  # must be >= 8.2
php -m | grep -iE "pdo_sqlite|mbstring|ctype"
composer install
composer test                           # expect: OK (253 tests, 481 assertions)
composer analyse                        # expect: [OK] No errors
php bin/forge route:list                # expect: 10 route(s)
```

Optional HTTP smoke test against a throw-away database (bash):

```bash
export DB_PATH="$PWD/database/review.sqlite"
php database/init.php
php -S 127.0.0.1:8765 -t public &
curl -i http://127.0.0.1:8765/                                   # 200 + security headers
curl -i http://127.0.0.1:8765/nope                               # 404
curl -i -X PUT http://127.0.0.1:8765/tasks                       # 405, Allow: GET, POST
curl -i -X POST -d "title=   " http://127.0.0.1:8765/tasks       # 422
curl -i -X POST --data-urlencode "title=<b>x</b>" http://127.0.0.1:8765/tasks   # 303
curl -s http://127.0.0.1:8765/tasks/1 | grep "&lt;b&gt;x&lt;/b&gt;"           # escaped
curl -s http://127.0.0.1:8765/api/tasks                          # JSON
```

Stop the server afterwards and delete `database/review.sqlite`.

### 9.2 Suggested reading order

1. `public/index.php` — entry point (21 lines)
2. `bootstrap/app.php` — every binding, i.e. the whole object graph
3. `routes/web.php` — the 10 routes
4. `src/Framework/Http/Request.php`, `Response.php`, `Kernel.php`
5. `src/Framework/Routing/Route.php`, `Router.php`, `Dispatcher.php`
6. `src/Framework/Container/Container.php`
7. `src/Framework/Middleware/Pipeline.php`, `Exceptions/ExceptionHandler.php`
8. `src/Framework/View/PhpViewRenderer.php`, `Template.php`, then `resources/views/`
9. `src/Framework/Database/Connection.php`, `database/schema.sql`
10. `app/Contracts/TaskRepositoryInterface.php` → `app/Repositories/PdoTaskRepository.php`
11. `app/Services/TaskService.php`, `src/Framework/Validation/Validator.php`
12. `app/Controllers/*`
13. `src/Framework/Console/*`, `bin/forge`
14. The tests next to each piece (`tests/Unit/...`, `tests/Integration/...`)

### 9.3 What to check

* **Dependency direction**: `src/Framework` must not use `App\`
  (`grep -rnF 'App\' src/` returns nothing).
* **SOLID / DI**: constructor injection everywhere; only the Dispatcher and the
  composition root call the container.
* **Security**: SQL only through bound parameters (`Connection::run()`); every dynamic
  value in templates printed with `$this->e()`; view names cannot leave
  `resources/views`; no internal details in 500 pages unless `APP_DEBUG=1`.
* **Router semantics**: precedence, 404 vs 405, `Allow` header.
* **Tests**: does each behaviour above have a test? Are edge cases missing?
* **Known issues** in section 10 — confirm them, and look for others.

### 9.4 Questions worth answering

* Is the separation Router / Dispatcher / Kernel justified at this size?
* Explicit bindings vs reflection auto-wiring: right trade-off for a learning project?
* Should escaping be automatic? What would a CSRF design look like here?
* Do the repository contract tests really prove substitutability?
* Does the project actually run on PHP 8.2 (only verified on 8.5)?

---

## 10. Known limitations and issues

MiniForge V1 is meant to run **locally** for learning and demonstration.

By design (V1 scope):

* **No CSRF protection** — forms that create, update or delete tasks can be triggered
  from another site. Do not expose the application publicly.
* **No authentication or authorization.**
* **No sessions** — success messages use a whitelisted `?notice=` query parameter.
* Escaping in views is **manual** (`$this->e()`).
* SQLite only, no transactions, no migrations (one idempotent schema file).
* Router: only `GET`/`POST`, no route groups, named routes, per-route middleware or
  method spoofing; route parameters are always strings.
* `APP_DEBUG=1` reveals exception messages and stack traces.
* The PHP built-in server is for development only.

Found during the read-only audit (not fixed yet):

* `HEAD` requests get **405** instead of being served like `GET`.
* `mbstring` and `ctype` are used but not declared in `composer.json`.
* PHP 8.2 compatibility is declared but only verified on PHP 8.5.
* An encoded slash (`%2F`) is decoded before routing (`/tasks/1%2Fedit` reaches the edit page).
* Unknown `/api/...` URLs and 405s return HTML, not JSON.
* A route parameter named `{request}`, or an action requiring a parameter absent from the
  route, produces a generic 500 instead of a clear `DispatchException`.

Possible next steps are listed in [docs/roadmap.md](docs/roadmap.md).

---

## 11. Documentation index

| Document | Content |
|----------|---------|
| [docs/architecture.md](docs/architecture.md) | Layers, request lifecycle, dependency rules |
| [docs/adr/](docs/adr) | 8 architecture decision records (framework/app split, MVC, DI, container, interfaces, PDO + SQLite, native templates, micro-commits) |
| [docs/roadmap.md](docs/roadmap.md) | Milestones and next steps |
| [docs/demo.md](docs/demo.md) | 5-minute demo script |

---

## 12. AI assistance

This project was developed with the assistance of an AI coding agent (**Claude**, by
Anthropic, running in Claude Code). The agent wrote code, tests and documentation under
a written engineering contract defined by the project owner (architecture, scope,
micro-commit protocol). The commit timestamps reflect the agent's pace (the 60 initial
commits were created in about 30 minutes); they show the order in which the framework
was built, not a human development timeline. Every result reported here comes from
commands actually executed.

---

## 13. License

MIT, as declared in `composer.json`. A `LICENSE` file naming the copyright holder
should be added by the owner.
