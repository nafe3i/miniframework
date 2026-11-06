<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Entities\Task;
use App\Entities\TaskStatus;

/**
 * Persistence contract for tasks.
 *
 * The business layer (TaskService) depends on this interface only, so the
 * storage can be swapped (PDO in production, in-memory in tests).
 */
interface TaskRepositoryInterface
{
    /**
     * @return list<Task> Most recently created first
     */
    public function all(): array;

    public function find(int $id): ?Task;

    /**
     * Persists a new task; the storage generates its identifier.
     */
    public function create(string $title, ?string $description, TaskStatus $status): Task;

    /**
     * @return bool False when no task has this identifier
     */
    public function update(Task $task): bool;

    /**
     * @return bool False when no task has this identifier
     */
    public function delete(int $id): bool;
}
