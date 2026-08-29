<?php

declare(strict_types=1);

namespace App\Core;

final class Seo
{
    private readonly string $baseUrl;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function canonical(string $path, int $page = 1): string
    {
        $url = $this->baseUrl . $path;

        return $page > 1 ? $url . '?page=' . $page : $url;
    }

    public function description(string $text, int $limit = 160): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($text)));

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        $cut = mb_substr($text, 0, $limit);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false) {
            $cut = mb_substr($cut, 0, $lastSpace);
        }

        return rtrim($cut, " ,.;:—-") . '…';
    }
}
