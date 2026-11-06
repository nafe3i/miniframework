<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\TaskNotFoundException;
use App\Services\TaskService;
use MiniForge\Contracts\ViewRendererInterface;
use MiniForge\Http\Request;
use MiniForge\Http\Response;

/**
 * Translates HTTP requests into TaskService calls and renders the results.
 * No SQL and no business rules here.
 */
final class TaskController
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly ViewRendererInterface $views,
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->render('tasks/index', [
            'tasks' => $this->tasks->list(),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $task = $this->tasks->get($this->taskId($id));

        return $this->render('tasks/show', ['task' => $task]);
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
     * @param array<string, mixed> $data
     */
    private function render(string $view, array $data = [], int $status = 200): Response
    {
        return Response::html($this->views->render($view, $data), $status);
    }
}
