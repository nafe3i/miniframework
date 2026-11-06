<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TaskRepositoryInterface;
use App\Entities\Task;
use App\Entities\TaskStatus;
use App\Exceptions\TaskNotFoundException;
use MiniForge\Exceptions\ValidationException;
use MiniForge\Validation\Validator;

/**
 * Application service holding the task business rules.
 *
 * It only knows the repository CONTRACT, never PDO or SQL.
 */
final class TaskService
{
    public function __construct(
        private readonly TaskRepositoryInterface $tasks,
        private readonly Validator $validator,
    ) {
    }

    /**
     * @return list<Task>
     */
    public function list(): array
    {
        return $this->tasks->all();
    }

    /**
     * @throws TaskNotFoundException
     */
    public function get(int $id): Task
    {
        return $this->tasks->find($id) ?? throw TaskNotFoundException::withId($id);
    }

    /**
     * @param array<string, mixed> $input Raw form input (title, description, status)
     *
     * @throws ValidationException
     */
    public function create(array $input): Task
    {
        $data = $this->validate($input);

        return $this->tasks->create($data['title'], $data['description'], $data['status']);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @throws TaskNotFoundException
     * @throws ValidationException
     */
    public function update(int $id, array $input): Task
    {
        $this->get($id);
        $data = $this->validate($input);

        $task = new Task($id, $data['title'], $data['description'], $data['status']);
        if (!$this->tasks->update($task)) {
            throw TaskNotFoundException::withId($id);
        }

        return $task;
    }

    /**
     * @throws TaskNotFoundException
     */
    public function delete(int $id): void
    {
        if (!$this->tasks->delete($id)) {
            throw TaskNotFoundException::withId($id);
        }
    }

    /**
     * Normalizes and validates raw input. Rules mirror database/schema.sql.
     *
     * @param array<string, mixed> $input
     * @return array{title: string, description: ?string, status: TaskStatus}
     *
     * @throws ValidationException
     */
    private function validate(array $input): array
    {
        // Normalize first so the length rules apply to what will be stored.
        $data = [
            'title' => self::trimmed($input['title'] ?? null),
            'description' => self::trimmed($input['description'] ?? null),
            'status' => $input['status'] ?? TaskStatus::TODO->value,
        ];

        $this->validator->validate($data, [
            'title' => 'required|string|max:' . Task::TITLE_MAX_LENGTH,
            'description' => 'string|max:' . Task::DESCRIPTION_MAX_LENGTH,
            'status' => 'required|in:' . implode(',', TaskStatus::values()),
        ]);

        /** @var array{title: string, description: ?string, status: string} $data */
        return [
            'title' => $data['title'],
            'description' => $data['description'] === '' ? null : $data['description'],
            'status' => TaskStatus::from($data['status']),
        ];
    }

    private static function trimmed(mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }
}
