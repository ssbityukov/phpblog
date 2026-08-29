<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Core\ViewInterface;

/** Запоминает, что контроллер отдал на рендер, вместо реального Smarty. */
final class SpyView implements ViewInterface
{
    public ?string $template = null;

    /** @var array<string, mixed> */
    public array $vars = [];

    /** @param array<string, mixed> $vars */
    public function render(string $template, array $vars = []): string
    {
        $this->template = $template;
        $this->vars = $vars;

        return 'rendered:' . $template;
    }

    public function var(string $name): mixed
    {
        return $this->vars[$name] ?? null;
    }
}
