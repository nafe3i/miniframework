<?php

declare(strict_types=1);

namespace Tests\Unit\App;

use App\Contracts\TaskRepositoryInterface;
use Tests\Support\InMemoryTaskRepository;
use Tests\Support\TaskRepositoryContractTestCase;

final class InMemoryTaskRepositoryTest extends TaskRepositoryContractTestCase
{
    protected function repository(): TaskRepositoryInterface
    {
        return $this->repository ??= new InMemoryTaskRepository();
    }

    private ?InMemoryTaskRepository $repository = null;
}
