<?php

declare(strict_types=1);

namespace Tests\Integration\Tasks;

use Tests\Integration\ApplicationTestCase;

final class TaskCreationTest extends ApplicationTestCase
{
    public function testCreateFormIsDisplayedBeforeDynamicShowRoute(): void
    {
        $response = $this->request('GET', '/tasks/create');

        self::assertSame(200, $response->status());
        self::assertStringContainsString('<form class="card form" method="post" action="/tasks"', $response->content());
        self::assertStringContainsString('name="title"', $response->content());
        self::assertStringContainsString('<option value="TODO" selected>', $response->content());
    }

    public function testStoreCreatesTaskAndRedirectsToIt(): void
    {
        $response = $this->request('POST', '/tasks', [
            'title' => 'Prepare demo',
            'description' => 'Record the video',
            'status' => 'IN_PROGRESS',
        ]);

        self::assertSame(303, $response->status());
        self::assertSame('/tasks/1?notice=created', $response->header('Location'));
        self::assertSame(
            ['id' => 1, 'title' => 'Prepare demo', 'description' => 'Record the video', 'status' => 'IN_PROGRESS'],
            $this->db()->selectOne('SELECT * FROM tasks WHERE id = 1'),
        );

        $page = $this->request('GET', '/tasks/1?notice=created');
        self::assertStringContainsString('Task created.', $page->content());
    }

    public function testUnknownNoticeIsIgnored(): void
    {
        $this->request('POST', '/tasks', ['title' => 'A']);

        $page = $this->request('GET', '/tasks/1?notice=%3Cscript%3E');

        self::assertStringNotContainsString('alert--success', $page->content());
    }

    public function testInvalidInputRedisplaysFormWithErrorsAndOldValues(): void
    {
        $response = $this->request('POST', '/tasks', [
            'title' => '   ',
            'description' => 'Keep <this>',
            'status' => 'ARCHIVED',
        ]);

        self::assertSame(422, $response->status());
        self::assertStringContainsString('The title is required.', $response->content());
        self::assertStringContainsString('The selected status is invalid.', $response->content());
        self::assertStringContainsString('Keep &lt;this&gt;</textarea>', $response->content());
        self::assertStringContainsString('aria-invalid="true"', $response->content());
        self::assertSame([], $this->db()->select('SELECT * FROM tasks'));
    }

    public function testArrayInputDoesNotBreakTheForm(): void
    {
        $response = $this->request('POST', '/tasks', ['title' => ['injected']]);

        self::assertSame(422, $response->status());
        self::assertStringContainsString('The title must be a string.', $response->content());
        self::assertSame([], $this->db()->select('SELECT * FROM tasks'));
    }

    public function testUnexpectedFieldsAreIgnored(): void
    {
        $this->request('POST', '/tasks', ['title' => 'Mass assignment', 'id' => '99']);

        self::assertNotNull($this->db()->selectOne('SELECT * FROM tasks WHERE id = 1'));
        self::assertNull($this->db()->selectOne('SELECT * FROM tasks WHERE id = 99'));
    }
}
