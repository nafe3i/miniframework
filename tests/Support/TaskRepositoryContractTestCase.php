<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\TaskRepositoryInterface;
use App\Entities\Task;
use App\Entities\TaskStatus;
use PHPUnit\Framework\TestCase;

/**
 * Behaviour every TaskRepositoryInterface implementation must satisfy.
 */
abstract class TaskRepositoryContractTestCase extends TestCase
{
    abstract protected function repository(): TaskRepositoryInterface;

    public function testCreateGeneratesIdentifiersAndPersists(): void
    {
        $repository = $this->repository();

        $first = $repository->create('First', null, TaskStatus::TODO);
        $second = $repository->create('Second', 'Details', TaskStatus::DONE);

        self::assertGreaterThan(0, $first->id());
        self::assertNotSame($first->id(), $second->id());
        self::assertEquals($second, $repository->find($second->id()));
    }

    public function testFindReturnsNullForUnknownId(): void
    {
        self::assertNull($this->repository()->find(999));
    }

    public function testAllListsMostRecentFirst(): void
    {
        $repository = $this->repository();
        self::assertSame([], $repository->all());

        $repository->create('Older', null, TaskStatus::TODO);
        $repository->create('Newer', null, TaskStatus::TODO);

        self::assertSame(['Newer', 'Older'], array_map(static fn (Task $t): string => $t->title(), $repository->all()));
    }

    public function testUpdateReplacesStoredData(): void
    {
        $repository = $this->repository();
        $task = $repository->create('Draft', null, TaskStatus::TODO);

        $updated = new Task($task->id(), 'Final', 'Now with details', TaskStatus::IN_PROGRESS);

        self::assertTrue($repository->update($updated));
        self::assertEquals($updated, $repository->find($task->id()));
    }

    public function testUpdateOfUnknownTaskReturnsFalse(): void
    {
        self::assertFalse($this->repository()->update(new Task(999, 'Ghost', null, TaskStatus::TODO)));
    }

    public function testDeleteRemovesTask(): void
    {
        $repository = $this->repository();
        $task = $repository->create('Temp', null, TaskStatus::TODO);

        self::assertTrue($repository->delete($task->id()));
        self::assertNull($repository->find($task->id()));
        self::assertFalse($repository->delete($task->id()));
    }
}
