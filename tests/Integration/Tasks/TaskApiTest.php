<?php

declare(strict_types=1);

namespace Tests\Integration\Tasks;

use Tests\Integration\ApplicationTestCase;

final class TaskApiTest extends ApplicationTestCase
{
    public function testListsTasksAsValidJson(): void
    {
        $this->db()->execute(
            'INSERT INTO tasks (title, description, status) VALUES (?, ?, ?), (?, ?, ?)',
            ['Older', null, 'DONE', 'Newer <b>', 'Details', 'TODO'],
        );

        $response = $this->request('GET', '/api/tasks');

        self::assertSame(200, $response->status());
        self::assertSame('application/json; charset=UTF-8', $response->header('Content-Type'));
        self::assertJson($response->content());
        self::assertSame(
            ['data' => [
                ['id' => 2, 'title' => 'Newer <b>', 'description' => 'Details', 'status' => 'TODO'],
                ['id' => 1, 'title' => 'Older', 'description' => null, 'status' => 'DONE'],
            ]],
            json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public function testEmptyListIsAnEmptyJsonArray(): void
    {
        self::assertSame('{"data":[]}', $this->request('GET', '/api/tasks')->content());
    }

    public function testShowsOneTask(): void
    {
        $this->db()->execute('INSERT INTO tasks (title) VALUES (?)', ['Solo']);

        $response = $this->request('GET', '/api/tasks/1');

        self::assertSame(200, $response->status());
        self::assertSame(
            ['data' => ['id' => 1, 'title' => 'Solo', 'description' => null, 'status' => 'TODO']],
            json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR),
        );
    }

    public function testUnknownTaskIsAJson404(): void
    {
        foreach (['/api/tasks/42', '/api/tasks/abc'] as $uri) {
            $response = $this->request('GET', $uri);

            self::assertSame(404, $response->status(), $uri);
            self::assertSame('application/json; charset=UTF-8', $response->header('Content-Type'));
            self::assertArrayHasKey('error', json_decode($response->content(), true, flags: JSON_THROW_ON_ERROR));
        }
    }

    public function testApiIsReadOnly(): void
    {
        self::assertSame(405, $this->request('POST', '/api/tasks', ['title' => 'x'])->status());
    }
}
