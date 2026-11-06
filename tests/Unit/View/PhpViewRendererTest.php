<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use InvalidArgumentException;
use MiniForge\Contracts\ViewRendererInterface;
use MiniForge\Exceptions\ViewNotFoundException;
use MiniForge\View\PhpViewRenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PhpViewRendererTest extends TestCase
{
    private PhpViewRenderer $views;

    protected function setUp(): void
    {
        $this->views = new PhpViewRenderer(__DIR__ . '/../../Fixtures/views');
    }

    public function testImplementsViewContract(): void
    {
        self::assertInstanceOf(ViewRendererInterface::class, $this->views);
    }

    public function testRendersTemplateToString(): void
    {
        self::assertSame("<h1>Static page</h1>\n", $this->views->render('hello'));
    }

    public function testResolvesNestedNamesWithSlashOrDot(): void
    {
        self::assertSame("<p>Nested page</p>\n", $this->views->render('pages/about'));
        self::assertSame("<p>Nested page</p>\n", $this->views->render('pages.about'));
    }

    public function testMissingViewThrows(): void
    {
        $this->expectException(ViewNotFoundException::class);
        $this->expectExceptionMessage('View [missing] not found.');

        $this->views->render('missing');
    }

    public function testExistsReportsAvailability(): void
    {
        self::assertTrue($this->views->exists('hello'));
        self::assertFalse($this->views->exists('missing'));
        self::assertFalse($this->views->exists('../outside'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function maliciousNames(): iterable
    {
        yield 'parent traversal' => ['../outside'];
        yield 'dot traversal' => ['..outside'];
        yield 'absolute path' => ['/etc/passwd'];
        yield 'windows path' => ['C:\\Windows\\win.ini'];
        yield 'stream wrapper' => ['php://filter/resource=hello'];
        yield 'null byte' => ["hello\0.txt"];
        yield 'explicit extension' => ['hello.php.'];
        yield 'empty' => [''];
    }

    #[DataProvider('maliciousNames')]
    public function testRejectsNamesEscapingTheViewDirectory(string $name): void
    {
        $this->expectException(ViewNotFoundException::class);

        $this->views->render($name);
    }

    public function testTemplateErrorsPropagateWithoutLeakingOutputBuffers(): void
    {
        $level = ob_get_level();

        try {
            $this->views->render('failing');
            self::fail('Expected the template exception to propagate.');
        } catch (RuntimeException $exception) {
            self::assertSame('template failure', $exception->getMessage());
        }

        self::assertSame($level, ob_get_level());
    }

    public function testRejectsMissingBaseDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PhpViewRenderer(__DIR__ . '/does-not-exist');
    }
}
