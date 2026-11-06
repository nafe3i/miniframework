<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Entities\Task;
use App\Exceptions\TaskNotFoundException;
use App\Services\TaskService;
use MiniForge\Http\Request;
use MiniForge\Http\Response;

/**
 * Read-only JSON API over the same TaskService as the HTML pages.
 */
final class TaskApiController
{
    public function __construct(private readonly TaskService $tasks)
    {
    }

    public function index(Request $request): Response
    {
        return Response::json([
            'data' => array_map(static fn (Task $task): array => $task->toArray(), $this->tasks->list()),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $taskId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        try {
            if ($taskId === false) {
                throw TaskNotFoundException::withId($id);
            }

            return Response::json(['data' => $this->tasks->get($taskId)->toArray()]);
        } catch (TaskNotFoundException $exception) {
            return Response::json(['error' => $exception->getMessage()], 404);
        }
    }
}
