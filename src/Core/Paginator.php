<?php

declare(strict_types=1);

namespace App\Core;

final class Paginator
{
    private readonly int $page;
    private readonly int $lastPage;

    public function __construct(
        private readonly int $total,
        private readonly int $perPage,
        int $page,
    ) {
        if ($this->perPage < 1) {
            throw new \InvalidArgumentException(
                sprintf('Per page must be positive, got %d.', $this->perPage),
            );
        }

        $this->lastPage = max(1, (int) ceil($this->total / $this->perPage));
        $this->page = min(max(1, $page), $this->lastPage);
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function lastPage(): int
    {
        return $this->lastPage;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    public function hasPrevious(): bool
    {
        return $this->page > 1;
    }

    public function hasNext(): bool
    {
        return $this->page < $this->lastPage;
    }

    /** @return list<int> */
    public function pages(): array
    {
        return range(1, $this->lastPage);
    }
}
