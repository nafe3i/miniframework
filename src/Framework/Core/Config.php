<?php

declare(strict_types=1);

namespace MiniForge\Core;

use InvalidArgumentException;

/**
 * Read-only configuration repository with dot-notation access.
 *
 *     $config->get('database.path');
 */
final class Config
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(private readonly array $items = [])
    {
    }

    /**
     * Loads every "*.php" file of a directory; each file returns an array and
     * becomes the top-level key named after the file ("app.php" => "app").
     */
    public static function fromDirectory(string $directory): self
    {
        if (!is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('Configuration directory [%s] does not exist.', $directory));
        }

        $items = [];
        foreach (glob(rtrim($directory, '/\\') . '/*.php') ?: [] as $file) {
            $values = require $file;
            if (!is_array($values)) {
                throw new InvalidArgumentException(sprintf('Configuration file [%s] must return an array.', basename($file)));
            }
            $items[basename($file, '.php')] = $values;
        }

        return new self($items);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function has(string $key): bool
    {
        $missing = new \stdClass();

        return $this->get($key, $missing) !== $missing;
    }
}
