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

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function nonStrings(): iterable
    {
        yield 'array' => [['injected']];
        yield 'integer' => [42];
        yield 'boolean' => [true];
    }

    #[DataProvider('nonStrings')]
    public function testIncompatibleTypeIsRejected(mixed $value): void
    {
        self::assertSame(
            ['title' => ['The title must be a string.']],
            $this->validator->errors(['title' => $value], ['title' => 'required|string']),
        );
    }

    public function testStringIsAccepted(): void
    {
        self::assertSame([], $this->validator->errors(['title' => 'text'], ['title' => 'required|string']));
    }

    public function testMultibyteStringIsAccepted(): void
    {
        self::assertSame([], $this->validator->errors(['title' => 'Préparer la démo ✓'], ['title' => 'required|string']));
    }

    public function testStringWithInvalidUtf8IsRejected(): void
    {
        // "Préparer" encoded in Latin-1: the é is the single byte 0xE9.
        self::assertSame(
            ['title' => ['The title must be valid UTF-8 text.']],
            $this->validator->errors(['title' => "Pr\xE9parer"], ['title' => 'required|string']),
        );
    }

    public function testFirstFailingRuleWins(): void
    {
        self::assertSame(
            ['title' => ['The title is required.']],
            $this->validator->errors(['title' => ''], ['title' => 'required|string']),
        );
    }

    public function testEmptyOptionalFieldSkipsOtherRules(): void
    {
        self::assertSame([], $this->validator->errors(['note' => ''], ['note' => 'string']));
        self::assertSame([], $this->validator->errors([], ['note' => 'string']));
    }

    public function testFilledOptionalFieldIsStillChecked(): void
    {
        self::assertSame(
            ['note' => ['The note must be a string.']],
            $this->validator->errors(['note' => ['x']], ['note' => 'string']),
        );
    }

    public function testMaxLengthAcceptsBoundaryValue(): void
    {
        self::assertSame([], $this->validator->errors(['title' => 'abcde'], ['title' => 'max:5']));
    }

    public function testExcessiveLengthIsRejected(): void
    {
        self::assertSame(
            ['title' => ['The title may not be longer than 5 characters.']],
            $this->validator->errors(['title' => 'abcdef'], ['title' => 'required|string|max:5']),
        );
    }

    public function testMaxCountsCharactersNotBytes(): void
    {
        self::assertSame([], $this->validator->errors(['title' => 'ééééé'], ['title' => 'max:5']));
        self::assertArrayHasKey('title', $this->validator->errors(['title' => 'éééééé'], ['title' => 'max:5']));
    }

    public function testMaxRejectsNonStringValues(): void
    {
        self::assertSame(
            ['title' => ['The title must be a string.']],
            $this->validator->errors(['title' => ['a']], ['title' => 'max:5']),
        );
    }

    public function testMaxWithoutValidArgumentIsAProgrammingError(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->errors(['title' => 'x'], ['title' => 'max:abc']);
    }

    public function testInAcceptsListedValues(): void
    {
        self::assertSame([], $this->validator->errors(['status' => 'DONE'], ['status' => 'required|in:TODO,DONE']));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function unlistedValues(): iterable
    {
        yield 'unknown value' => ['ARCHIVED'];
        yield 'different case' => ['done'];
        yield 'array' => [['DONE']];
    }

    #[DataProvider('unlistedValues')]
    public function testInRejectsUnlistedValues(mixed $value): void
    {
        self::assertSame(
            ['status' => ['The selected status is invalid.']],
            $this->validator->errors(['status' => $value], ['status' => 'required|in:TODO,DONE']),
        );
    }

    public function testUnknownRuleIsAProgrammingError(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->validator->validate(['x' => 'y'], ['x' => 'uuid']);
    }
}
