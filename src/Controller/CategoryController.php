<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\NotFoundException;
use App\Core\Paginator;
use App\Core\Request;
use App\Core\Seo;
use App\Core\View;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;

final class CategoryController
{
    private const PER_PAGE = 10;

    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly PostRepository $posts,
        private readonly View $view,
        private readonly Seo $seo,
    ) {
    }

    public function show(Request $request, string $slug): string
    {
        $category = $this->categories->findBySlug($slug);

        if ($category === null) {
            throw new NotFoundException(sprintf('Category "%s" not found.', $slug));
        }

        $sort = $request->query('sort', 'date');

        if (!array_key_exists($sort, PostRepository::SORTS)) {
            $sort = 'date';
        }

        $paginator = new Paginator(
            $this->posts->countByCategory($category->id),
            self::PER_PAGE,
            $request->queryInt('page', 1),
        );

        return $this->view->render('category.tpl', [
            'title' => $category->name,
            'description' => $this->seo->description($category->description),
            'canonical' => $this->seo->canonical('/category/' . $category->slug, $paginator->page()),
            'category' => $category,
            'sort' => $sort,
            'paginator' => $paginator,
            'posts' => $this->posts->findByCategory($category->id, $sort, $paginator->perPage(), $paginator->offset()),
        ]);
    }
}
