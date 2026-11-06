<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TaskRepositoryInterface;
use App\Entities\Task;
use App\Entities\TaskStatus;
use App\Exceptions\TaskNotFoundException;
use MiniForge\Exceptions\ValidationException;

/**
 * Application service holding the task business rules.
 *
 * It only knows the repository CONTRACT, never PDO or SQL.
 */
final class TaskService
{
    public function __construct(private readonly TaskRepositoryInterface $tasks)
    {
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
        $errors = [];

        $title = $input['title'] ?? null;
        if (!is_string($title) || trim($title) === '') {
            $errors['title'][] = 'The title is required.';
        } elseif (mb_strlen(trim($title)) > Task::TITLE_MAX_LENGTH) {
            $errors['title'][] = sprintf('The title may not be longer than %d characters.', Task::TITLE_MAX_LENGTH);
        }

        $description = $input['description'] ?? null;
        if ($description !== null && !is_string($description)) {
            $errors['description'][] = 'The description must be text.';
        } elseif (is_string($description) && mb_strlen(trim($description)) > Task::DESCRIPTION_MAX_LENGTH) {
            $errors['description'][] = sprintf('The description may not be longer than %d characters.', Task::DESCRIPTION_MAX_LENGTH);
        }

        $status = $input['status'] ?? TaskStatus::TODO->value;
        if (!is_string($status) || TaskStatus::tryFrom($status) === null) {
            $errors['status'][] = 'The selected status is invalid.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var string $title */
        /** @var string $status */
        $description = is_string($description) ? trim($description) : '';

        return [
            'title' => trim($title),
            'description' => $description === '' ? null : $description,
            'status' => TaskStatus::from($status),
        ];
    }
}
