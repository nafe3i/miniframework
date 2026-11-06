<?php

declare(strict_types=1);

namespace Tests\Unit\App;

use App\Entities\TaskStatus;
use App\Exceptions\TaskNotFoundException;
use App\Services\TaskService;
use MiniForge\Exceptions\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemoryTaskRepository;

/**
 * Business rules tested against the in-memory repository: no database needed.
 */
final class TaskServiceTest extends TestCase
{
    private InMemoryTaskRepository $repository;
    private TaskService $service;

    protected function setUp(): void
    {
        $this->repository = new InMemoryTaskRepository();
        $this->service = new TaskService($this->repository);
    }

    public function testCreatesTaskWithNormalizedInput(): void
    {
        $task = $this->service->create(['title' => '  Ship V1  ', 'description' => '   ', 'status' => 'IN_PROGRESS']);

        self::assertSame('Ship V1', $task->title());
        self::assertNull($task->description(), 'A blank description is stored as null.');
        self::assertSame(TaskStatus::IN_PROGRESS, $task->status());
        self::assertEquals($task, $this->repository->find($task->id()));
    }

    public function testStatusDefaultsToTodo(): void
    {
        self::assertSame(TaskStatus::TODO, $this->service->create(['title' => 'x'])->status());
    }

    public function testListsTasks(): void
    {
        $this->service->create(['title' => 'a']);
        $this->service->create(['title' => 'b']);

        self::assertCount(2, $this->service->list());
    }

    public function testGetUnknownTaskThrowsNotFound(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $this->service->get(42);
    }

    public function testUpdatesTask(): void
    {
        $task = $this->service->create(['title' => 'Old']);

        $updated = $this->service->update($task->id(), ['title' => 'New', 'description' => 'More', 'status' => 'DONE']);

        self::assertSame('New', $updated->title());
        self::assertEquals($updated, $this->service->get($task->id()));
    }

    public function testUpdateOfUnknownTaskThrowsNotFoundBeforeValidating(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $this->service->update(42, []);
    }

    public function testDeletesTask(): void
    {
        $task = $this->service->create(['title' => 'Temp']);

        $this->service->delete($task->id());

        self::assertSame([], $this->service->list());
    }

    public function testDeleteOfUnknownTaskThrowsNotFound(): void
    {
        $this->expectException(TaskNotFoundException::class);

        $this->service->delete(42);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidInput(): iterable
    {
        yield 'missing title' => [[], 'title'];
        yield 'blank title' => [['title' => '   '], 'title'];
        yield 'title not a string' => [['title' => ['array']], 'title'];
        yield 'title too long' => [['title' => str_repeat('é', 256)], 'title'];
        yield 'description not a string' => [['title' => 'ok', 'description' => ['x']], 'description'];
        yield 'description too long' => [['title' => 'ok', 'description' => str_repeat('d', 2001)], 'description'];
        yield 'unknown status' => [['title' => 'ok', 'status' => 'ARCHIVED'], 'status'];
    }

    /**
     * @param array<string, mixed> $input
     */
    #[DataProvider('invalidInput')]
    public function testRejectsInvalidInputWithFieldErrors(array $input, string $field): void
    {
        try {
            $this->service->create($input);
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey($field, $exception->errors());
            self::assertSame(422, $exception->statusCode());
        }

        self::assertSame([], $this->repository->all(), 'Nothing is stored when validation fails.');
    }

    public function testAcceptsMaximumLengths(): void
    {
        $task = $this->service->create([
            'title' => str_repeat('é', 255),
            'description' => str_repeat('d', 2000),
        ]);

        self::assertSame(255, mb_strlen($task->title()));
    }
}
