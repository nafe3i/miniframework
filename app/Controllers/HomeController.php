<?php

declare(strict_types=1);

namespace App\Controllers;

use MiniForge\Contracts\ViewRendererInterface;
use MiniForge\Http\Request;
use MiniForge\Http\Response;

final class HomeController
{
    public function __construct(
        private readonly ViewRendererInterface $views,
        private readonly string $appName,
    ) {
    }

    public function index(Request $request): Response
    {
        return Response::html($this->views->render('home', [
            'appName' => $this->appName,
        ]));
    }
}
