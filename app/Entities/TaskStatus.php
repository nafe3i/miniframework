<?php

declare(strict_types=1);

namespace App\Entities;

/**
 * Lifecycle of a task. Values match the CHECK constraint of the tasks table.
 */
enum TaskStatus: string
{
    case TODO = 'TODO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case DONE = 'DONE';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'To do',
            self::IN_PROGRESS => 'In progress',
            self::DONE => 'Done',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status): string => $status->value, self::cases());
    }
}
