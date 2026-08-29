<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Request;
use App\Core\View;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;

final class HomeController
{
    private const POSTS_PER_CATEGORY = 3;

    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly PostRepository $posts,
        private readonly View $view,
    ) {
    }

    public function index(Request $request): string
    {
        return $this->view->render('home.tpl', [
            'title' => 'Блог',
            'categories' => $this->categories->findAllWithPosts(),
            'postsByCategory' => $this->posts->latestByCategories(self::POSTS_PER_CATEGORY),
        ]);
    }
}
