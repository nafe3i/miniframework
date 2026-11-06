# Demo script (≈ 5 minutes)

A step-by-step path to record a short video of MiniForge PHP. Commands are
run from the project root. Nothing here has been recorded yet: this is the
script for the recording.

## 0. Preparation (before recording)

```bash
composer install
composer db:init
php -S localhost:8000 -t public
```

Open a second terminal for the CLI/test steps. Optionally start from an empty
database: delete `database/database.sqlite` and run `composer db:init` again.

## 1. The framework in one sentence (30 s)

Show `docs/architecture.md` (lifecycle diagram): Front Controller → Kernel →
Middleware → Router → Dispatcher → Controller → View/Response.

## 2. Routes and the CLI (30 s)

```bash
php bin/forge route:list
```

Point out the dynamic routes (`/tasks/{id}`) and that the command runs
without a web server or database. Open `routes/web.php` to show the declarations.

## 3. Task Manager CRUD (2 min)

1. <http://localhost:8000/> — home page rendered by `HomeController` in a layout.
2. **Tasks** → empty list.
3. **+ New task** → submit an empty form → validation errors (HTTP 422), values kept.
4. Create *"Prepare the demo"* with a description containing `<b>html</b>` → redirected to the
   detail page (`/tasks/1`, a **dynamic route**) with "Task created."; the HTML is displayed
   escaped, not interpreted.
5. **Edit** → change the status to *In progress* → "Task updated.".
6. Create a second task, then **Delete** it → "Task deleted.".
7. Visit <http://localhost:8000/tasks/999> → styled 404 page.
8. Visit <http://localhost:8000/api/tasks> → JSON output of the same data.

## 4. Under the hood (1 min)

* `app/Controllers/TaskController.php` — thin controller, constructor injection.
* `resources/views/tasks/show.php` — `$this->layout()`, `$this->e()`.
* `app/Repositories/PdoTaskRepository.php` — prepared statements through `Connection`.
* `bootstrap/app.php` — the composition root with explicit container bindings.

## 5. Quality (1 min)

```bash
composer test
composer analyse
git log --oneline | head -30
```

Show the test count, PHPStan "No errors", and the micro-commit history
(`git log --oneline --reverse` tells the story from `chore(composer)` to the CLI).
