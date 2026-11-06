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

    public function testShowDisplaysOneTaskFromDynamicRoute(): void
    {
        $this->db()->execute('INSERT INTO tasks (title, description) VALUES (?, ?)', ['First', 'Line 1']);
        $this->db()->execute('INSERT INTO tasks (title, description) VALUES (?, ?)', ['Second', "Line A\nLine <B>"]);

        $response = $this->request('GET', '/tasks/2');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('<h1>Second</h1>', $response->content());
        self::assertStringContainsString('Line A<br />', $response->content());
        self::assertStringContainsString('Line &lt;B&gt;', $response->content());
        self::assertStringNotContainsString('First', $response->content());
    }

    public function testShowUnknownTaskReturns404(): void
    {
        $response = $this->request('GET', '/tasks/999');

        self::assertSame(404, $response->status());
        self::assertStringContainsString('Task #999 was not found.', $response->content());
    }

    public function testShowNonNumericIdReturns404(): void
    {
        self::assertSame(404, $this->request('GET', '/tasks/abc')->status());
        self::assertSame(404, $this->request('GET', '/tasks/0')->status());
        self::assertSame(404, $this->request('GET', '/tasks/-1')->status());
    }
}
