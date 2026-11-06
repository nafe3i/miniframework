<?php

declare(strict_types=1);

namespace Tests\Unit\App;

use App\Entities\Task;
use App\Entities\TaskStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TaskTest extends TestCase
{
    public function testExposesItsData(): void
    {
        $task = new Task(3, 'Write README', null, TaskStatus::IN_PROGRESS);

        self::assertSame(3, $task->id());
        self::assertSame('Write README', $task->title());
        self::assertNull($task->description());
        self::assertSame(TaskStatus::IN_PROGRESS, $task->status());
    }

    public function testRejectsNonPositiveIdentifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Task(0, 'x', null, TaskStatus::TODO);
    }

    public function testStatusValuesMatchDatabaseConstraint(): void
    {
        self::assertSame(['TODO', 'IN_PROGRESS', 'DONE'], TaskStatus::values());
        self::assertSame('In progress', TaskStatus::IN_PROGRESS->label());
        self::assertSame(TaskStatus::DONE, TaskStatus::from('DONE'));
    }
}
