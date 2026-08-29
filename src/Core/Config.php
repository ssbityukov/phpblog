<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    private const TRUE_VALUES = ['1', 'true', 'on', 'yes'];

    /** @param array<string, string> $values */
    private function __construct(private readonly array $values)
    {
    }

    public static function load(string $path): self
    {
        return new self(is_readable($path) ? self::parse($path) : []);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $fromEnv = getenv($key);

        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        return $this->values[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if ($value === null || $value === '') {
            return $default;
        }

        return in_array(strtolower($value), self::TRUE_VALUES, true);
    }

    /** @return array<string, string> */
    private static function parse(string $path): array
    {
        $values = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = self::normalize(trim($value));
        }

        return $values;
    }

    /** Снимает кавычки; у некавыченного значения отрезает inline-комментарий. */
    private static function normalize(string $value): string
    {
        foreach (['"', "'"] as $quote) {
            if (strlen($value) >= 2 && str_starts_with($value, $quote) && str_ends_with($value, $quote)) {
                return substr($value, 1, -1);
            }
        }

        return rtrim((string) preg_replace('/\s+#.*$/', '', $value));
    }
}
