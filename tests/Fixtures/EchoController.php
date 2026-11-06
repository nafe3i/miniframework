<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use MiniForge\Http\Request;
use MiniForge\Http\Response;

/**
 * Test controller echoing what it receives, with an injected dependency.
 */
final class EchoController
{
    public function __construct(private readonly string $prefix = '')
    {
    }

    public function index(Request $request): Response
    {
        return new Response($this->prefix . $request->method() . ' ' . $request->path());
    }

    public function show(Request $request, string $id): Response
    {
        return new Response($this->prefix . 'show ' . $id);
    }

    public function nested(Request $request, string $task, string $project): Response
    {
        return new Response("project {$project}, task {$task}");
    }

    public function invalid(Request $request): string
    {
        return 'not a response';
    }

    private function hidden(Request $request): Response
    {
        return new Response('hidden');
    }
}
