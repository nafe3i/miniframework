# MiniForge PHP — Architecture

## 1. Three separated parts

```
┌──────────────────────────────────────────────────────────────────────┐
│ Bootstrap & Infrastructure                                           │
│  public/index.php   bootstrap/app.php   routes/web.php   config/*    │
│  bin/forge          database/schema.sql database/init.php  tests/    │
│  (composition root: the only place choosing concrete classes)        │
└───────────────┬───────────────────────────────────┬──────────────────┘
                │ wires                             │ wires
                ▼                                   ▼
┌───────────────────────────────┐     ┌────────────────────────────────┐
│ Demo Application  (App\)      │     │ Framework Core  (MiniForge\)   │
│  Controllers  ─────────────────────▶ │  Http, Routing, Container,     │
│  Services                     │uses │  View, Database, Middleware,   │
│  Contracts (repository)       │     │  Validation, Console,          │
│  Repositories (PDO adapter)   │     │  Exceptions, Core (Config)     │
│  Entities                     │     │                                │
└───────────────────────────────┘     └────────────────────────────────┘
          App depends on the framework's public API.
          The framework NEVER references App\ classes.
```

## 2. Request lifecycle

```
HTTP request
   │
   ▼
public/index.php ── Request::fromGlobals()
   │
   ▼
Http\Kernel::handle(Request)
   │
   ├─▶ Middleware\Pipeline  (global middleware, in declaration order)
   │      └─ SecurityHeadersMiddleware ─┐ (may short-circuit)
   │                                    ▼
   ├─▶ Routing\Router::match()  ── RouteMatch(route, {params})
   │        404 NotFoundHttpException / 405 MethodNotAllowedHttpException (+ Allow)
   │
   ├─▶ Routing\Dispatcher::dispatch()
   │        controller = Container::get(ControllerClass)   (constructor injection)
   │        response   = controller->action(Request, ...named route params)
   │        must return Http\Response
   │
   ├─▶ any exception ─▶ Exceptions\ExceptionHandler::render() ─▶ error Response
   │        (HttpException → its status; others → generic 500, details only in debug)
   │
   ▼
Response::send()   (status line, headers, body)
```

## 3. Task Manager layers

```
TaskController ──▶ TaskService ──▶ TaskRepositoryInterface ◀── PdoTaskRepository ──▶ Connection (PDO) ──▶ SQLite
      │                 │                                     ▲
      │                 └─▶ Validation\Validator               └── InMemoryTaskRepository (tests only)
      └─▶ ViewRendererInterface ◀── PhpViewRenderer ──▶ resources/views/*.php
```

* The controller translates HTTP into service calls and chooses a view; it
  never runs SQL.
* The service holds the business rules and only knows the repository
  contract (Dependency Inversion), so it is unit-tested with an in-memory
  repository.
* `PdoTaskRepository` is an adapter from the contract to SQL; every value
  is bound through a prepared statement.
* Views receive plain data (entities) and never reach repositories.

## 4. Allowed dependencies

| From | May depend on |
|------|---------------|
| Framework core | PHP + other framework classes only |
| Controllers | Services, `ViewRendererInterface`, `Http\*` |
| Services | Repository contracts, entities, `Validator`, framework exceptions |
| Repositories | `Database\Connection`, entities, repository contract |
| Views | Data passed to them, `$this` template helpers |
| `bootstrap/app.php` | Everything (composition root) |

## 5. Contracts (interfaces) and why each exists

| Interface | Reason |
|-----------|--------|
| `Contracts\ContainerInterface` | The Dispatcher and factories need *resolution only*, not registration. |
| `Contracts\ViewRendererInterface` | Controllers do not depend on the template engine (testable, swappable). |
| `Contracts\MiddlewareInterface` | Polymorphic steps of the pipeline (Chain of Responsibility). |
| `Contracts\CommandInterface` | Polymorphic console commands (Command pattern). |
| `App\Contracts\TaskRepositoryInterface` | Business rules independent from storage; in-memory tests. |

Concrete, stable classes (`Router`, `Request`, `Response`, `Connection`,
`Validator`, `Config`) intentionally have no interface (YAGNI).

## 6. Design patterns in use

* **Front Controller** — `public/index.php`.
* **MVC** — controllers, PHP views, entities/services.
* **Dependency Injection** — constructor injection, explicit container bindings.
* **Repository + Adapter** — `TaskRepositoryInterface` / `PdoTaskRepository`.
* **Chain of Responsibility** — middleware `Pipeline`.
* **Command** — `bin/forge` commands.

## 7. Namespaces

| Namespace | Directory |
|-----------|-----------|
| `MiniForge\` | `src/Framework/` |
| `App\` | `app/` |
| `Tests\` | `tests/` |
