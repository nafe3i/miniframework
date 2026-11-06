<?php

declare(strict_types=1);

namespace Tests\Integration\Database;

use MiniForge\Database\Connection;
use MiniForge\Exceptions\DatabaseException;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        $this->db = Connection::fromConfig(['path' => ':memory:']);
        $this->db->execute('CREATE TABLE notes (id INTEGER PRIMARY KEY AUTOINCREMENT, body TEXT NOT NULL, rank INTEGER)');
    }

    public function testInsertsWithPositionalParametersAndReturnsGeneratedId(): void
    {
        $affected = $this->db->execute('INSERT INTO notes (body, rank) VALUES (?, ?)', ['first', 1]);

        self::assertSame(1, $affected);
        self::assertSame(1, $this->db->lastInsertId());
    }

    public function testSelectsWithNamedParameters(): void
    {
        $this->db->execute('INSERT INTO notes (body, rank) VALUES (:body, :rank)', ['body' => 'a', 'rank' => 1]);
        $this->db->execute('INSERT INTO notes (body, rank) VALUES (:body, :rank)', ['body' => 'b', 'rank' => 2]);

        $rows = $this->db->select('SELECT body, rank FROM notes WHERE rank >= :min ORDER BY rank', ['min' => 1]);

        self::assertSame([['body' => 'a', 'rank' => 1], ['body' => 'b', 'rank' => 2]], $rows);
    }

    public function testSelectOneReturnsRowOrNull(): void
    {
        $this->db->execute('INSERT INTO notes (body) VALUES (?)', ['only']);

        self::assertSame(['body' => 'only'], $this->db->selectOne('SELECT body FROM notes WHERE id = ?', [1]));
        self::assertNull($this->db->selectOne('SELECT body FROM notes WHERE id = ?', [99]));
    }

    public function testBindsNullValues(): void
    {
        $this->db->execute('INSERT INTO notes (body, rank) VALUES (?, ?)', ['x', null]);

        self::assertNull($this->db->selectOne('SELECT rank FROM notes')['rank'] ?? null);
    }

    public function testExecuteReturnsAffectedRowCount(): void
    {
        $this->db->execute('INSERT INTO notes (body) VALUES (?)', ['a']);
        $this->db->execute('INSERT INTO notes (body) VALUES (?)', ['b']);

        self::assertSame(2, $this->db->execute('UPDATE notes SET rank = ?', [5]));
        self::assertSame(0, $this->db->execute('DELETE FROM notes WHERE id = ?', [42]));
    }

    public function testInjectionAttemptIsTreatedAsPlainData(): void
    {
        $this->db->execute('INSERT INTO notes (body) VALUES (?)', ['safe']);
        $malicious = "x'); DROP TABLE notes; --";

        $this->db->execute('INSERT INTO notes (body) VALUES (?)', [$malicious]);

        self::assertCount(2, $this->db->select('SELECT * FROM notes'));
        self::assertSame(['body' => $malicious], $this->db->selectOne('SELECT body FROM notes WHERE id = ?', [2]));
    }

    public function testInvalidQueryRaisesDatabaseException(): void
    {
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('no such table');

        $this->db->select('SELECT * FROM missing_table');
    }

    public function testConstraintViolationRaisesDatabaseException(): void
    {
        $this->expectException(DatabaseException::class);

        $this->db->execute('INSERT INTO notes (body) VALUES (?)', [null]);
    }
}
