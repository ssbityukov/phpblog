<?php

declare(strict_types=1);

namespace App\Core;

interface ViewInterface
{
    /** @param array<string, mixed> $vars */
    public function render(string $template, array $vars = []): string;
}
