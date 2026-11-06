<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\TaskRepositoryInterface;
use App\Entities\Task;
use App\Entities\TaskStatus;

/**
 * Array-backed repository used to test business logic without a database.
 */
final class InMemoryTaskRepository implements TaskRepositoryInterface
{
    /** @var array<int, Task> */
    private array $tasks = [];

    private int $nextId = 1;

    public function all(): array
    {
        return array_values(array_reverse($this->tasks, true));
    }

    public function find(int $id): ?Task
    {
        return $this->tasks[$id] ?? null;
    }

    public function create(string $title, ?string $description, TaskStatus $status): Task
    {
        $task = new Task($this->nextId++, $title, $description, $status);
        $this->tasks[$task->id()] = $task;

        return $task;
    }

    public function update(Task $task): bool
    {
        if (!isset($this->tasks[$task->id()])) {
            return false;
        }

        $this->tasks[$task->id()] = $task;

        return true;
    }

    public function delete(int $id): bool
    {
        if (!isset($this->tasks[$id])) {
            return false;
        }

        unset($this->tasks[$id]);

        return true;
    }
}
