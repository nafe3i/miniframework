<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Entities\TaskStatus;
use App\Exceptions\TaskNotFoundException;
use App\Services\TaskService;
use MiniForge\Contracts\ViewRendererInterface;
use MiniForge\Exceptions\ValidationException;
use MiniForge\Http\Request;
use MiniForge\Http\Response;

/**
 * Translates HTTP requests into TaskService calls and renders the results.
 * No SQL and no business rules here.
 */
final class TaskController
{
    private const FIELDS = ['title', 'description', 'status'];

    /** Whitelisted flash-like notices, selected by the "notice" query parameter. */
    private const NOTICES = [
        'created' => 'Task created.',
        'updated' => 'Task updated.',
        'deleted' => 'Task deleted.',
    ];

    public function __construct(
        private readonly TaskService $tasks,
        private readonly ViewRendererInterface $views,
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->render('tasks/index', [
            'tasks' => $this->tasks->list(),
            'notice' => $this->notice($request),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $task = $this->tasks->get($this->taskId($id));

        return $this->render('tasks/show', [
            'task' => $task,
            'notice' => $this->notice($request),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->form('tasks/create', ['status' => TaskStatus::TODO->value]);
    }

    public function store(Request $request): Response
    {
        try {
            $task = $this->tasks->create($request->only(self::FIELDS));
        } catch (ValidationException $exception) {
            return $this->form('tasks/create', $this->old($request), $exception->errors(), 422);
        }

        return Response::redirect('/tasks/' . $task->id() . '?notice=created');
    }

    public function edit(Request $request, string $id): Response
    {
        $task = $this->tasks->get($this->taskId($id));

        return $this->form('tasks/edit', [
            'title' => $task->title(),
            'description' => $task->description() ?? '',
            'status' => $task->status()->value,
        ], extra: ['task' => $task]);
    }

    public function update(Request $request, string $id): Response
    {
        $task = $this->tasks->get($this->taskId($id));

        try {
            $this->tasks->update($task->id(), $request->only(self::FIELDS));
        } catch (ValidationException $exception) {
            return $this->form('tasks/edit', $this->old($request), $exception->errors(), 422, ['task' => $task]);
        }

        return Response::redirect('/tasks/' . $task->id() . '?notice=updated');
    }

    public function destroy(Request $request, string $id): Response
    {
        $this->tasks->delete($this->taskId($id));

        return Response::redirect('/tasks?notice=deleted');
    }

    /**
     * Route parameters are strings: anything that is not a positive integer
     * cannot be a task identifier, hence a 404.
     */
    private function taskId(string $id): int
    {
        $value = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $value === false ? throw TaskNotFoundException::withId($id) : $value;
    }

    /**
     * @param array<string, string> $old Values to prefill the fields with
     * @param array<string, list<string>> $errors Validation errors per field
     * @param array<string, mixed> $extra
     */
    private function form(string $view, array $old, array $errors = [], int $status = 200, array $extra = []): Response
    {
        return $this->render($view, [
            ...$extra,
            'old' => $old,
            'errors' => $errors,
            'statuses' => TaskStatus::cases(),
        ], $status);
    }

    /**
     * Submitted values to redisplay after a validation failure (non-string
     * values, e.g. "title[]=x", are dropped).
     *
     * @return array<string, string>
     */
    private function old(Request $request): array
    {
        return array_map(
            static fn (mixed $value): string => is_string($value) ? $value : '',
            $request->only(self::FIELDS),
        );
    }

    private function notice(Request $request): ?string
    {
        $key = $request->query('notice');

        return is_string($key) ? (self::NOTICES[$key] ?? null) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $view, array $data = [], int $status = 200): Response
    {
        return Response::html($this->views->render($view, $data), $status);
    }
}
