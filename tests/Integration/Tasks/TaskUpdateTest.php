<?php

declare(strict_types=1);

namespace Tests\Integration\Tasks;

use Tests\Integration\ApplicationTestCase;

final class TaskUpdateTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->db()->execute(
            'INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)',
            ['Original "title"', null, 'TODO'],
        );
    }

    public function testEditFormIsPrefilledWithEscapedValues(): void
    {
        $response = $this->request('GET', '/tasks/1/edit');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('action="/tasks/1/update"', $response->content());
        self::assertStringContainsString('value="Original &quot;title&quot;"', $response->content());
        self::assertStringContainsString('<option value="TODO" selected>', $response->content());
    }

    public function testUpdateSavesChangesAndRedirects(): void
    {
        $response = $this->request('POST', '/tasks/1/update', [
            'title' => 'Renamed',
            'description' => 'Now described',
            'status' => 'DONE',
        ]);

        self::assertSame(303, $response->status());
        self::assertSame('/tasks/1?notice=updated', $response->header('Location'));
        self::assertSame(
            ['id' => 1, 'title' => 'Renamed', 'description' => 'Now described', 'status' => 'DONE'],
            $this->db()->selectOne('SELECT * FROM tasks WHERE id = 1'),
        );
        self::assertStringContainsString('Task updated.', $this->request('GET', '/tasks/1?notice=updated')->content());
    }

    public function testInvalidUpdateRedisplaysFormAndKeepsStoredData(): void
    {
        $response = $this->request('POST', '/tasks/1/update', [
            'title' => str_repeat('x', 256),
            'status' => 'DONE',
        ]);

        self::assertSame(422, $response->status());
        self::assertStringContainsString('may not be longer than 255 characters', $response->content());
        self::assertStringContainsString('<option value="DONE" selected>', $response->content());
        self::assertSame('Original "title"', $this->db()->selectOne('SELECT title FROM tasks WHERE id = 1')['title'] ?? null);
    }

    public function testEditAndUpdateOfUnknownTaskReturn404(): void
    {
        self::assertSame(404, $this->request('GET', '/tasks/42/edit')->status());
        self::assertSame(404, $this->request('POST', '/tasks/42/update', ['title' => 'x'])->status());
    }

    public function testUpdateRequiresPost(): void
    {
        $response = $this->request('GET', '/tasks/1/update');

        self::assertSame(405, $response->status());
        self::assertSame('POST', $response->header('Allow'));
    }
}
