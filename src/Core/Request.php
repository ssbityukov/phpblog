<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @param array<string, string> $query */
    public function __construct(
        private readonly string $uri,
        private readonly array $query,
        private readonly string $method = 'GET',
    ) {
    }

    public static function fromGlobals(): self
    {
        $query = [];

        foreach ($_GET as $key => $value) {
            if (is_scalar($value)) {
                $query[(string) $key] = (string) $value;
            }
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        return new self(
            is_string($uri) ? $uri : '/',
            $query,
            is_string($method) ? $method : 'GET',
        );
    }

    public function method(): string
    {
        return strtoupper($this->method);
    }

    public function path(): string
    {
        $path = parse_url($this->uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    public function query(string $key, string $default = ''): string
    {
        return $this->query[$key] ?? $default;
    }

    public function queryInt(string $key, int $default): int
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) && ctype_digit($value) ? (int) $value : $default;
    }
}
