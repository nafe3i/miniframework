<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

/**
 * Input data broke one or more validation rules.
 *
 * Carries structured errors (field => list of messages) that controllers
 * and views can display next to each field. If it is not caught by a
 * controller it becomes a 422 response.
 */
final class ValidationException extends HttpException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(422, 'The given data was invalid.');
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
