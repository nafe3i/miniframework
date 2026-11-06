<?php

declare(strict_types=1);

namespace Tests\Integration\Tasks;

use Tests\Integration\ApplicationTestCase;

/**
 * Task Manager pages: Request -> Kernel -> TaskController -> TaskService
 * -> PdoTaskRepository -> SQLite -> View -> Response.
 */
final class TaskPagesTest extends ApplicationTestCase
{
    public function testEmptyListShowsEmptyState(): void
    {
        $response = $this->request('GET', '/tasks');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('No tasks yet.', $response->content());
    }

    public function testListShowsStoredTasksEscaped(): void
    {
        $this->db()->execute(
            'INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)',
            ['<b>Bold</b> task', 'Some details', 'IN_PROGRESS'],
        );

        $html = $this->request('GET', '/tasks')->content();

        self::assertStringContainsString('&lt;b&gt;Bold&lt;/b&gt; task', $html);
        self::assertStringNotContainsString('<b>Bold</b>', $html);
        self::assertStringContainsString('href="/tasks/1"', $html);
        self::assertStringContainsString('In progress', $html);
        self::assertStringContainsString('Some details', $html);
    }
}
