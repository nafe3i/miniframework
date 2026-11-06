<?php

declare(strict_types=1);

namespace App\Controllers;

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

    /**
     * @param array<string, mixed> $data
     */
    private function render(string $view, array $data = [], int $status = 200): Response
    {
        return Response::html($this->views->render($view, $data), $status);
    }
}
