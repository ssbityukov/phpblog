<?php

declare(strict_types=1);

namespace App\Core;

use Smarty\Smarty;

final class View implements ViewInterface
{
    private readonly Smarty $smarty;

    public function __construct(string $templateDir, string $compileDir, string $cacheDir)
    {
        $this->smarty = new Smarty();
        $this->smarty->setTemplateDir($templateDir);
        $this->smarty->setCompileDir($compileDir);
        $this->smarty->setCacheDir($cacheDir);
        $this->smarty->escape_html = true;
    }

    /** @param array<string, mixed> $vars */
    public function render(string $template, array $vars = []): string
    {
        foreach ($vars as $name => $value) {
            $this->smarty->assign($name, $value);
        }

        return $this->smarty->fetch($template);
    }
}
