<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use InvalidArgumentException;
use MiniForge\Exceptions\ValidationException;
use MiniForge\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidDataIsReturnedRestrictedToRuleFields(): void
    {
        $validated = $this->validator->validate(
            ['title' => 'Ok', 'is_admin' => '1'],
            ['title' => 'required'],
        );

        self::assertSame(['title' => 'Ok'], $validated);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function missingValues(): iterable
    {
        yield 'absent' => [[]];
        yield 'null' => [['title' => null]];
        yield 'empty string' => [['title' => '']];
        yield 'whitespace only' => [['title' => "  \t\n"]];
        yield 'empty array' => [['title' => []]];
    }

    /**
     * @param array<string, mixed> $data
     */
    #[DataProvider('missingValues')]
    public function testRequiredFieldMissingProducesStructuredError(array $data): void
    {
        try {
            $this->validator->validate($data, ['title' => 'required']);
            self::fail('Expected a ValidationException.');
        } catch (ValidationException $exception) {
            self::assertSame(['title' => ['The title is required.']], $exception->errors());
        }
    }

    public function testZeroIsAPresentValue(): void
    {
        self::assertSame(['count' => '0'], $this->validator->validate(['count' => '0'], ['count' => 'required']));
    }

    public function testCollectsErrorsForEveryField(): void
    {
        $errors = $this->validator->errors([], ['title' => 'required', 'status' => 'required']);

        self::assertSame(['title', 'status'], array_keys($errors));
    }

    public function testUnknownRuleIsAProgrammingError(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate(['x' => 'y'], ['x' => 'uuid']);
    }
}
