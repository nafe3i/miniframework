<?php

declare(strict_types=1);

namespace MiniForge\Validation;

use InvalidArgumentException;
use MiniForge\Exceptions\ValidationException;

/**
 * Small rule-based validator.
 *
 *     $validator->validate($input, [
 *         'title' => 'required|string|max:255',
 *         'note'  => 'string|max:2000',   // optional: skipped when empty
 *         'state' => 'required|in:OPEN,CLOSED',
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
            $fieldRules = explode('|', $definition);

            // An optional field left empty is valid: its other rules do not apply.
            if (!in_array('required', $fieldRules, true) && self::isEmpty($value)) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                $parts = explode(':', $rule, 2);
                $name = $parts[0];
                $argument = $parts[1] ?? null;

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
            'string' => is_string($value) ? null : sprintf('The %s must be a string.', $field),
            'max' => $this->checkMax($field, $value, $argument),
            'in' => in_array($value, explode(',', (string) $argument), true)
                ? null
                : sprintf('The selected %s is invalid.', $field),
            default => throw new InvalidArgumentException(sprintf('Unknown validation rule [%s].', $rule)),
        };
    }

    /**
     * max:N — at most N characters (multibyte aware, as SQLite length()).
     */
    private function checkMax(string $field, mixed $value, ?string $argument): ?string
    {
        if ($argument === null || !ctype_digit($argument)) {
            throw new InvalidArgumentException(sprintf('The max rule of [%s] needs a non-negative integer argument.', $field));
        }

        if (!is_string($value)) {
            return sprintf('The %s must be a string.', $field);
        }

        return mb_strlen($value) > (int) $argument
            ? sprintf('The %s may not be longer than %d characters.', $field, (int) $argument)
            : null;
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '')
            || $value === [];
    }
}
