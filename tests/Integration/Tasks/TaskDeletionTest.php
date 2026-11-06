<?php

declare(strict_types=1);

namespace Tests\Integration\Tasks;

use Tests\Integration\ApplicationTestCase;

final class TaskDeletionTest extends ApplicationTestCase
{
    public function testDeleteRemovesTaskAndRedirectsToList(): void
    {
        $this->db()->execute('INSERT INTO tasks (title) VALUES (?), (?)', ['Keep', 'Remove']);

        $response = $this->request('POST', '/tasks/2/delete');

        self::assertSame(303, $response->status());
        self::assertSame('/tasks?notice=deleted', $response->header('Location'));
        self::assertSame([['title' => 'Keep']], $this->db()->select('SELECT title FROM tasks'));

        $list = $this->request('GET', '/tasks?notice=deleted');
        self::assertStringContainsString('Task deleted.', $list->content());
        self::assertStringNotContainsString('Remove', $list->content());
    }

    public function testDeletedTaskIsNoLongerReachable(): void
    {
        $this->db()->execute('INSERT INTO tasks (title) VALUES (?)', ['Gone soon']);
        $this->request('POST', '/tasks/1/delete');

        self::assertSame(404, $this->request('GET', '/tasks/1')->status());
    }

    public function testDeletingUnknownTaskReturns404(): void
    {
        self::assertSame(404, $this->request('POST', '/tasks/7/delete')->status());
    }

    public function testDeleteIsNotReachableWithGet(): void
    {
        $this->db()->execute('INSERT INTO tasks (title) VALUES (?)', ['Safe from GET']);

        self::assertSame(405, $this->request('GET', '/tasks/1/delete')->status());
        self::assertNotNull($this->db()->selectOne('SELECT id FROM tasks WHERE id = 1'));
    }

    public function testShowPageOffersDeleteForm(): void
    {
        $this->db()->execute('INSERT INTO tasks (title) VALUES (?)', ['Deletable']);

        self::assertStringContainsString(
            'action="/tasks/1/delete"',
            $this->request('GET', '/tasks/1')->content(),
        );
    }
}
