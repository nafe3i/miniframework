<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\TaskRepositoryInterface;
use App\Entities\Task;
use App\Entities\TaskStatus;
use MiniForge\Database\Connection;
use UnexpectedValueException;

/**
 * Adapter between the TaskRepositoryInterface contract and the SQL database.
 *
 * Every value goes through a prepared statement parameter.
 */
final class PdoTaskRepository implements TaskRepositoryInterface
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function all(): array
    {
        $rows = $this->db->select('SELECT id, title, description, status FROM tasks ORDER BY id DESC');

        return array_map($this->hydrate(...), $rows);
    }

    public function find(int $id): ?Task
    {
        $row = $this->db->selectOne('SELECT id, title, description, status FROM tasks WHERE id = :id', ['id' => $id]);

        return $row === null ? null : $this->hydrate($row);
    }

    public function create(string $title, ?string $description, TaskStatus $status): Task
    {
        $this->db->execute(
            'INSERT INTO tasks (title, description, status) VALUES (:title, :description, :status)',
            ['title' => $title, 'description' => $description, 'status' => $status->value],
        );

        return new Task($this->db->lastInsertId(), $title, $description, $status);
    }

    public function update(Task $task): bool
    {
        if ($this->find($task->id()) === null) {
            return false;
        }

        // Row count is not used: SQLite reports 0 when values are unchanged.
        $this->db->execute(
            'UPDATE tasks SET title = :title, description = :description, status = :status WHERE id = :id',
            [
                'id' => $task->id(),
                'title' => $task->title(),
                'description' => $task->description(),
                'status' => $task->status()->value,
            ],
        );

        return true;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute('DELETE FROM tasks WHERE id = :id', ['id' => $id]) > 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Task
    {
        $status = TaskStatus::tryFrom((string) ($row['status'] ?? ''));
        if ($status === null) {
            throw new UnexpectedValueException(sprintf('Task #%s has an unknown status.', (string) ($row['id'] ?? '?')));
        }

        return new Task(
            (int) $row['id'],
            (string) $row['title'],
            isset($row['description']) ? (string) $row['description'] : null,
            $status,
        );
    }
}
