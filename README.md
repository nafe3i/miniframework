# MiniForge PHP

A minimal, educational PHP MVC framework inspired by Laravel's core mechanisms,
built for the **Lara Academy challenge — "Construis ton mini Laravel"**, together
with a small **Task Manager** application that uses it.

The goal is not to reproduce Laravel but to show, in readable code, how a
framework works: front controller, router, dispatcher, container, views,
database access, middleware, validation and a CLI — with tests for each piece.

---

## 1. Features actually implemented

**Framework (`MiniForge\`, `src/Framework/`)**

| Area | What it does |
|------|--------------|
| HTTP | Immutable `Request` (method, normalized path, query, form input, headers, `fromGlobals()`), immutable `Response` (status, headers, `html()`, `json()`, `redirect()`, `send()`), header injection protection |
| Routing | `GET`/`POST` routes, static and dynamic (`/tasks/{id}`) paths, 404, 405 with a correct `Allow` header, duplicate-route detection, static-before-dynamic precedence |
| Dispatcher | Resolves the controller from the container, passes route parameters as **named arguments**, enforces a `Response` return value |
| Kernel | Middleware → routing → dispatch → response, exceptions converted to error responses |
| Container | Explicit bindings (`bind`, `singleton`, `instance`), interface → implementation, clear errors, circular-dependency detection |
| Views | Native PHP templates, data passing, explicit escaping helper `$this->e()`, layouts, partials, safe view-name resolution |
| Database | PDO wrapper for SQLite: `select`, `selectOne`, `execute`, `executeScript`, `lastInsertId`, prepared statements only |
| Middleware | `MiddlewareInterface`, ordered `Pipeline` with short-circuit support, `SecurityHeadersMiddleware` |
| Validation | `required`, `string`, `max:N`, `in:a,b` with structured errors (`field => [messages]`) |
| Errors | `ExceptionHandler`: 404 / 405 / 422 / 500, application error view, no internal details unless debug mode |
| Console | `php bin/forge route:list` (Command pattern) |
| Config | `config/*.php` files with dot-notation access and environment overrides |

**Demo application (`App\`, `app/`)** — Task Manager: list, show, create,
edit, update, delete tasks (title, optional description, status
`TODO` / `IN_PROGRESS` / `DONE`), validation messages in forms, 404 page for
unknown tasks, and a read-only JSON API.

## 2. Architecture

```
public/index.php (Front Controller)
   → Http\Kernel → Middleware\Pipeline → Routing\Router → Routing\Dispatcher
   → App\Controllers\TaskController → App\Services\TaskService
   → App\Contracts\TaskRepositoryInterface ← App\Repositories\PdoTaskRepository → SQLite
   → View\PhpViewRenderer → Http\Response
```

* **Framework core** never references the application.
* **Application** depends on the framework's public API.
* **`bootstrap/app.php`** is the composition root: the only place that picks
  concrete classes and wires them into the container.

Details: [docs/architecture.md](docs/architecture.md) · decisions:
[docs/adr/](docs/adr) · roadmap: [docs/roadmap.md](docs/roadmap.md).

```
├── app/                 Demo application (App\)
│   ├── Contracts/       TaskRepositoryInterface
│   ├── Controllers/     HomeController, TaskController, TaskApiController
│   ├── Entities/        Task, TaskStatus
│   ├── Exceptions/      TaskNotFoundException
│   ├── Repositories/    PdoTaskRepository
│   └── Services/        TaskService
├── bin/forge            Console entry point
├── bootstrap/app.php    Composition root (container bindings)
├── config/              app.php, database.php
├── database/            schema.sql, init.php (the .sqlite file is git-ignored)
├── docs/                Architecture, ADRs, roadmap, demo script
├── public/              index.php (front controller), css/
├── resources/views/     PHP templates (layouts, tasks, errors)
├── routes/web.php       Route declarations
├── src/Framework/       Framework core (MiniForge\)
└── tests/               Unit/ and Integration/ suites, Fixtures/, Support/
```

## 3. Requirements

* PHP **8.2+** with `pdo_sqlite` (and `mbstring`)
* Composer 2

Developed and verified with PHP 8.5.0 and Composer 2.9.3 on Windows 11;
`composer.lock` is resolved for a PHP 8.2 platform.

## 4. Installation

```bash
git clone <repository-url> miniforge
cd miniforge
composer install
```

## 5. Configuration

Configuration lives in `config/*.php`; environment variables override it:

| Variable | Default | Meaning |
|----------|---------|---------|
| `APP_DEBUG` | `false` | Show exception details on error pages. **Never enable on a public server.** |
| `APP_NAME` | `MiniForge Task Manager` | Title shown on the home page |
| `DB_PATH` | `database/database.sqlite` | SQLite file (or `:memory:`) |

## 6. SQLite initialization

```bash
composer db:init        # same as: php database/init.php
```

Creates the database file if needed and applies `database/schema.sql`. The
schema is idempotent: running it again keeps existing data.

## 7. Starting the server

```bash
php -S localhost:8000 -t public
```

Then open <http://localhost:8000>. To see error details while developing:
`APP_DEBUG=1 php -S localhost:8000 -t public` (bash syntax).

## 8. Routes

Declared in `routes/web.php`:

```php
return static function (Router $router): void {
    $router->get('/tasks', [TaskController::class, 'index']);
    $router->get('/tasks/create', [TaskController::class, 'create']);
    $router->post('/tasks', [TaskController::class, 'store']);
    $router->get('/tasks/{id}', [TaskController::class, 'show']);
    $router->get('/tasks/{id}/edit', [TaskController::class, 'edit']);
    $router->post('/tasks/{id}/update', [TaskController::class, 'update']);
    $router->post('/tasks/{id}/delete', [TaskController::class, 'destroy']);
};
```

Rules:
* `{name}` matches one non-empty path segment and is passed to the action
  as the argument named `$name`.
* Static routes win over dynamic ones (`/tasks/create` vs `/tasks/{id}`);
  among dynamic routes, the first declared wins.
* Registering the same method + pattern twice throws `DuplicateRouteException`.
* No path match → 404; path matches with another method → 405 + `Allow`.
* HTML forms only use `GET` and `POST` (no method spoofing in V1).

## 9. Controllers

Plain classes receiving their dependencies through the constructor. An
action receives the `Request` first, then the route parameters by name, and
must return a `Response`:

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

Each controller is bound in `bootstrap/app.php`:

```php
$container->bind(TaskController::class, static fn (ContainerInterface $c): TaskController => new TaskController(
    $c->get(TaskService::class),
    $c->get(ViewRendererInterface::class),
));
```

## 10. Views

Templates are PHP files in `resources/views`, named `tasks/show` (or `tasks.show`):

```php
<?php $this->layout('layouts/app', ['title' => $task->title()]); ?>
<h1><?= $this->e($task->title()) ?></h1>
<?= $this->insert('tasks/_form', ['old' => $old, 'errors' => $errors, /* ... */]) ?>
```

* `$this->e($value)` escapes for HTML. **Escaping is explicit, not automatic**:
  every dynamic value must go through it.
* `$this->layout(name, data)` wraps the page; the layout prints `$content` raw.
* `$this->insert(name, data)` renders a partial that sees only the data passed.
* View names are restricted to `[A-Za-z0-9_-]` segments, so user input can
  never select a file outside `resources/views`.

## 11. Database (PDO)

```php
$db = Connection::fromConfig(['driver' => 'sqlite', 'path' => ':memory:']);

$db->execute('INSERT INTO tasks (title, status) VALUES (:title, :status)', ['title' => $title, 'status' => 'TODO']);
$id   = $db->lastInsertId();
$task = $db->selectOne('SELECT * FROM tasks WHERE id = ?', [$id]);
$all  = $db->select('SELECT * FROM tasks ORDER BY id DESC');
```

PDO runs in exception mode with real prepared statements; values are always
bound, never concatenated. Errors are wrapped in `DatabaseException`.

## 12. Dependency injection

```php
$container->bind(Foo::class, fn (ContainerInterface $c) => new Foo($c->get(Bar::class))); // new instance each time
$container->singleton(Connection::class, fn ($c) => Connection::fromConfig(/* ... */));  // built lazily, once
$container->instance(Config::class, $config);                                           // pre-built object
$container->singleton(TaskRepositoryInterface::class, fn ($c) => new PdoTaskRepository($c->get(Connection::class)));
```

Bindings are explicit (no reflection auto-wiring in V1). Unknown services
throw `ServiceNotFoundException`; circular dependencies throw `ContainerException`.

## 13. Middleware

```php
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Closure $next): Response
    {
        return $next($request)->withHeader('X-Frame-Options', 'DENY');
    }
}
```

Global middleware is listed in the `Kernel` binding and runs in declaration
order; returning a `Response` without calling `$next` short-circuits the chain.

## 14. Validation

```php
$validator->validate($input, [
    'title'       => 'required|string|max:255',
    'description' => 'string|max:2000',          // optional: skipped when empty
    'status'      => 'required|in:TODO,IN_PROGRESS,DONE',
]); // throws ValidationException with ['title' => ['The title is required.']]
```

`TaskController` catches the exception and re-renders the form with HTTP 422,
the error messages and the submitted values. Rules match the `CHECK`
constraints of `database/schema.sql`.

## 15. Tests and static analysis

```bash
composer test           # PHPUnit: tests/Unit + tests/Integration
composer analyse        # PHPStan level 8 on src, app, bootstrap, config, routes and tests
```

Integration tests boot the real application with an **in-memory SQLite
database**; they never touch `database/database.sqlite`.

## 16. CLI

```bash
php bin/forge              # list commands
php bin/forge route:list   # list routes (no web server or database needed)
```

```
METHOD  PATH                ACTION
GET     /                   App\Controllers\HomeController@index
GET     /tasks              App\Controllers\TaskController@index
GET     /tasks/create       App\Controllers\TaskController@create
POST    /tasks              App\Controllers\TaskController@store
GET     /tasks/{id}         App\Controllers\TaskController@show
...
```

## 17. Demo application

| URL | Page |
|-----|------|
| `GET /` | Home page |
| `GET /tasks` | Task list |
| `GET /tasks/create` · `POST /tasks` | Create form / store |
| `GET /tasks/{id}` | Task details (404 if unknown) |
| `GET /tasks/{id}/edit` · `POST /tasks/{id}/update` | Edit form / update |
| `POST /tasks/{id}/delete` | Delete |
| `GET /api/tasks` · `GET /api/tasks/{id}` | Read-only JSON |

A recording script is in [docs/demo.md](docs/demo.md).

## 18. Limitations and security notes

MiniForge V1 is meant to run **locally** for learning and demonstration.

* **No CSRF protection.** Forms that create, update or delete tasks can be
  triggered by another site if the application is reachable by a victim's
  browser. Do not expose it publicly without adding a CSRF token middleware.
* **No authentication or authorization**: anyone who reaches the server can
  modify every task.
* **No sessions**: success messages use a whitelisted `?notice=` query parameter.
* Escaping in views is **manual** (`$this->e()`).
* `APP_DEBUG=1` reveals exception messages and stack traces: local use only.
* Only SQLite is supported; no migrations (one idempotent schema file).
* Router: no route groups, named routes, per-route middleware, `PUT`/`DELETE`
  method spoofing, or parameter constraints (controllers validate ids themselves).
* Errors are rendered as HTML (the JSON API handles its own 404s).
* The built-in PHP server is for development only.

## 19. What I learned

* A router is mostly normalization + pattern compilation; the subtle parts
  are ambiguity rules (static vs dynamic) and producing a correct 405 with `Allow`.
* Keeping route lookup (Router) and invocation (Dispatcher) separate makes
  both trivial to test.
* Explicit container bindings are verbose but make the object graph and
  laziness obvious (the CLI never opens the database).
* A repository contract lets business rules be tested in milliseconds with
  an in-memory implementation, while the same contract test suite validates
  the PDO adapter against SQLite.
* View security is about two things: escaping output and never letting
  input choose a template file.

## 20. AI assistance

This project was developed with the assistance of an AI coding agent
(**Claude**, by Anthropic, running in Claude Code). The agent wrote code,
tests and documentation under a written engineering contract defined by the
project owner (architecture, scope, micro-commit protocol, no remote
operations). Commits made by the agent carry a `Co-Authored-By` trailer.
Every result reported in the history and documentation comes from commands
actually executed; publication and submission remain the owner's decisions.

## License

MIT, as declared in `composer.json`. A `LICENSE` file naming the copyright
holder should be added by the owner before publication.
