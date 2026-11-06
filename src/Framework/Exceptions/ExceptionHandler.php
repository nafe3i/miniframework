<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

use Closure;
use MiniForge\Contracts\ViewRendererInterface;
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
    private const ERROR_VIEW = 'errors/default';

    /** @var Closure(Throwable): void */
    private readonly Closure $reporter;

    /**
     * @param Closure(Throwable): void|null $reporter Receives unexpected errors (defaults to error_log)
     * @param ViewRendererInterface|null $views When given, error pages use the "errors/default" view if it exists
     */
    public function __construct(
        private readonly bool $debug = false,
        ?Closure $reporter = null,
        private readonly ?ViewRendererInterface $views = null,
    ) {
        $this->reporter = $reporter ?? static function (Throwable $e): void {
            error_log(sprintf('[MiniForge] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
        };
    }

    public function render(Throwable $exception): Response
    {
        if ($exception instanceof HttpException) {
            $response = Response::html(
                $this->page($exception->statusCode(), $this->title($exception->statusCode()), $exception->getMessage(), $exception),
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

        return Response::html($this->page(500, 'Internal Server Error', $details, $exception), 500);
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

    private function page(int $status, string $title, string $message, Throwable $exception): string
    {
        if ($this->views !== null && !$exception instanceof ViewNotFoundException) {
            try {
                if ($this->views->exists(self::ERROR_VIEW)) {
                    return $this->views->render(self::ERROR_VIEW, [
                        'status' => $status,
                        'title' => $title,
                        'message' => $message,
                    ]);
                }
            } catch (Throwable $viewFailure) {
                // The error page itself failed: report it and use the built-in page.
                ($this->reporter)($viewFailure);
            }
        }

        $e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>'
            . $status . ' ' . $e($title)
            . '</title></head><body><h1>' . $status . ' ' . $e($title) . '</h1><pre>'
            . $e($message)
            . '</pre></body></html>';
    }
}
