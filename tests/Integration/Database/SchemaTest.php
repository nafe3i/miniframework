<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use MiniForge\Database\Connection;
use MiniForge\Exceptions\DatabaseException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SchemaTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = Connection::fromConfig(['path' => ':memory:']);
        $this->db->executeScript((string) file_get_contents(__DIR__ . '/../../../database/schema.sql'));
    }

    public function testCreatesTasksTable(): void
    {
        $columns = array_column($this->db->select('PRAGMA table_info(tasks)'), 'name');

        self::assertSame(['id', 'title', 'description', 'status'], $columns);
    }

    public function testSchemaIsIdempotentAndKeepsData(): void
    {
        $this->db->execute('INSERT INTO tasks (title) VALUES (?)', ['Keep me']);

        $this->db->executeScript((string) file_get_contents(__DIR__ . '/../../../database/schema.sql'));

        self::assertCount(1, $this->db->select('SELECT * FROM tasks'));
    }

    public function testDefaultsStatusToTodoAndGeneratesId(): void
    {
        $this->db->execute('INSERT INTO tasks (title) VALUES (?)', ['New']);

        self::assertSame(
            ['id' => 1, 'title' => 'New', 'description' => null, 'status' => 'TODO'],
            $this->db->selectOne('SELECT * FROM tasks WHERE id = ?', [1]),
        );
    }

    /**
     * @return iterable<string, array{string, string|null, string}>
     */
    public static function invalidRows(): iterable
    {
        yield 'blank title' => ['   ', null, 'TODO'];
        yield 'title too long' => [str_repeat('a', 256), null, 'TODO'];
        yield 'description too long' => ['ok', str_repeat('d', 2001), 'TODO'];
        yield 'unknown status' => ['ok', null, 'ARCHIVED'];
    }

    #[DataProvider('invalidRows')]
    public function testConstraintsRejectInvalidRows(string $title, ?string $description, string $status): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->execute(
            'INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)',
            [$title, $description, $status],
        );
    }

    public function testScriptErrorsAreWrapped(): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->executeScript('CREATE TABLE broken (');
    }
}
