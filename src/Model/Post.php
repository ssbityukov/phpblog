<?php

declare(strict_types=1);

namespace App\Model;

final class Post
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug,
        public readonly string $title,
        public readonly string $description,
        public readonly string $body,
        public readonly ?string $image,
        public readonly int $views,
        public readonly string $publishedAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['slug'],
            (string) $row['title'],
            (string) $row['description'],
            (string) $row['body'],
            $row['image'] === null ? null : (string) $row['image'],
            (int) $row['views'],
            (string) $row['published_at'],
        );
    }

    public function bodyHtml(): string
    {
        return nl2br(htmlspecialchars($this->body, ENT_QUOTES, 'UTF-8'));
    }

    public function publishedDate(): string
    {
        return (new \DateTimeImmutable($this->publishedAt))->format('d.m.Y');
    }
}
