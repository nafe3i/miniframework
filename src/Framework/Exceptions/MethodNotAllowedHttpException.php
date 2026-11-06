<?php

declare(strict_types=1);

namespace MiniForge\Exceptions;

/**
 * 405 — the path exists but does not accept the request method.
 *
 * Carries the mandatory "Allow" header listing the accepted methods.
 */
final class MethodNotAllowedHttpException extends HttpException
{
    /** @var list<string> */
    private readonly array $allowedMethods;

    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(array $allowedMethods, string $message = 'Method Not Allowed')
    {
        $methods = array_values(array_unique(array_map('strtoupper', $allowedMethods)));
        sort($methods);
        $this->allowedMethods = $methods;

        parent::__construct(405, $message, ['Allow' => implode(', ', $methods)]);
    }

    /**
     * @return list<string>
     */
    public function allowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
