<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{pattern: string, handler: array{0: string, 1: string}}> */
    private array $routes = [];

    /** @param array{0: string, 1: string} $handler */
    public function add(string $pattern, array $handler): void
    {
        $this->routes[] = ['pattern' => $pattern, 'handler' => $handler];
    }

    /** @return array{handler: array{0: string, 1: string}, params: array<string, string>} */
    public function match(string $path): array
    {
        foreach ($this->routes as $route) {
            $regex = $this->toRegex($route['pattern']);

            if (preg_match($regex, $path, $matches) === 1) {
                return [
                    'handler' => $route['handler'],
                    'params' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY),
                ];
            }
        }

        throw new NotFoundException(sprintf('No route matches "%s".', $path));
    }

    private function toRegex(string $pattern): string
    {
        $regex = '';

        foreach (preg_split('#(\{\w+})#', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [] as $part) {
            if (preg_match('#^\{(\w+)}$#', $part, $matches) === 1) {
                $regex .= '(?P<' . $matches[1] . '>[^/]+)';

                continue;
            }

            $regex .= preg_quote($part, '#');
        }

        return '#^' . $regex . '$#';
    }
}
