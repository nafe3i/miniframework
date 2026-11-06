<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Contracts\TaskRepositoryInterface;
use App\Entities\Task;
use App\Entities\TaskStatus;
use App\Repositories\PdoTaskRepository;
use MiniForge\Database\Connection;
use Tests\Support\Database;
use Tests\Support\TaskRepositoryContractTestCase;

final class PdoTaskRepositoryTest extends TaskRepositoryContractTestCase
{
    private Connection $db;
    private PdoTaskRepository $tasks;

    protected function setUp(): void
    {
        $this->db = Database::fresh();
        $this->tasks = new PdoTaskRepository($this->db);
    }

    protected function repository(): TaskRepositoryInterface
    {
        return $this->tasks;
    }

    public function testReadsRowsInsertedDirectlyInDatabase(): void
    {
        $this->db->execute(
            'INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)',
            ['Seeded', 'From SQL', 'IN_PROGRESS'],
        );

        $task = $this->tasks->find(1);

        self::assertEquals(new Task(1, 'Seeded', 'From SQL', TaskStatus::IN_PROGRESS), $task);
    }

    public function testWritesRowsReadableWithPlainSql(): void
    {
        $task = $this->tasks->create('Persisted', null, TaskStatus::DONE);

        self::assertSame(
            ['id' => $task->id(), 'title' => 'Persisted', 'description' => null, 'status' => 'DONE'],
            $this->db->selectOne('SELECT * FROM tasks WHERE id = ?', [$task->id()]),
        );
    }

    public function testUpdateWithUnchangedValuesStillReportsSuccess(): void
    {
        $task = $this->tasks->create('Same', null, TaskStatus::TODO);

        self::assertTrue($this->tasks->update($task));
    }

    public function testStoresHostileStringsVerbatim(): void
    {
        $title = "Robert'); DROP TABLE tasks;--";

        $task = $this->tasks->create($title, '<script>alert(1)</script>', TaskStatus::TODO);

        self::assertSame($title, $this->tasks->find($task->id())?->title());
        self::assertCount(1, $this->tasks->all());
    }
}
