<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\View;
use PDO;
use Smarty\Exception;

final class HomeController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly View $view,
    ) {
    }

    public function index(Request $request): string
    {
        return $this->view->render('home.tpl', ['title' => 'Блог']);
    }
}
