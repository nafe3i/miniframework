<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use Closure;
use MiniForge\Http\Response;
use Throwable;

/**
 * Converts any exception escaping the HTTP pipeline into a Response.
 *
 * - HttpException: its status, headers and (client-safe) message are used.
 * - Anything else: a generic 500. Internal details (message, class, trace)
 *   are only shown when debug mode is explicitly enabled.
 */
final class ExceptionHandler
{
    /** @var Closure(Throwable): void */
    private readonly Closure $reporter;

    /**
     * @param Closure(Throwable): void|null $reporter Receives unexpected errors (defaults to error_log)
     */
    public function __construct(
        private readonly bool $debug = false,
        ?Closure $reporter = null,
    ) {
        $this->reporter = $reporter ?? static function (Throwable $e): void {
            error_log(sprintf('[MiniForge] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
        };
    }

    public function render(Throwable $exception): Response
    {
        if ($exception instanceof HttpException) {
            $response = Response::html(
                $this->page($exception->statusCode(), $this->title($exception->statusCode()), $exception->getMessage()),
                $exception->statusCode(),
            );

            foreach ($exception->headers() as $name => $value) {
                $response = $response->withHeader($name, $value);
            }

            return $response;
        }

        ($this->reporter)($exception);

        $details = $this->debug
            ? sprintf("%s: %s\n\n%s", $exception::class, $exception->getMessage(), $exception->getTraceAsString())
            : 'Something went wrong on our side.';

        return Response::html($this->page(500, 'Internal Server Error', $details), 500);
    }

    private function title(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Content',
            default => $status >= 500 ? 'Server Error' : 'Error',
        };
    }

    private function page(int $status, string $title, string $message): string
    {
        $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>'
            . $status . ' ' . $e($title)
            . '</title></head><body><h1>' . $status . ' ' . $e($title) . '</h1><pre>'
            . $e($message)
            . '</pre></body></html>';
    }
}
