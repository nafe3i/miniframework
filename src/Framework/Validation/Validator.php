<?php

declare(strict_types=1);

namespace MiniForge\Validation;

use InvalidArgumentException;
use MiniForge\Exceptions\ValidationException;

/**
 * Small rule-based validator.
 *
 *     $validator->validate($input, [
 *         'title' => 'required',
 *     ]);
 *
 * Rules are checked in order and the first failing rule of a field produces
 * its error message. Errors are structured as field => list of messages.
 */
final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules field => "rule|rule:arg"
     * @return array<string, mixed> Only the validated fields that are present in $data
     *
     * @throws ValidationException
     */
    public function validate(array $data, array $rules): array
    {
        $errors = $this->errors($data, $rules);
        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return array_intersect_key($data, $rules);
    }

    /**
     * Same checks as validate(), returning the errors instead of throwing.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @return array<string, list<string>>
     */
    public function errors(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $definition) {
            $value = $data[$field] ?? null;

            foreach (explode('|', $definition) as $rule) {
                [$name, $argument] = array_pad(explode(':', $rule, 2), 2, null);

                $message = $this->check($field, $value, $name, $argument);
                if ($message !== null) {
                    $errors[$field][] = $message;
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * @return string|null Error message, or null when the rule passes
     */
    private function check(string $field, mixed $value, string $rule, ?string $argument): ?string
    {
        return match ($rule) {
            'required' => self::isEmpty($value) ? sprintf('The %s is required.', $field) : null,
            default => throw new InvalidArgumentException(sprintf('Unknown validation rule [%s].', $rule)),
        };
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '')
            || $value === [];
    }
}
