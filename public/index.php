<?php

declare(strict_types=1);

use App\Controller\CategoryController;
use App\Controller\HomeController;
use App\Controller\PostController;
use App\Core\Config;
use App\Core\Database;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\Router;
use App\Core\Seo;
use App\Core\View;
use App\Repository\CategoryRepository;
use App\Repository\PostRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$config = Config::fromFile($root . '/.env');
$database = new Database($config);
$view = new View(
    $root . '/templates',
    $root . '/var/smarty/compile',
    $root . '/var/smarty/cache',
);

$seo = new Seo($config->get('APP_URL', 'http://localhost:8080'));

$router = new Router();
$router->add('/', [HomeController::class, 'index']);
$router->add('/category/{slug}', [CategoryController::class, 'show']);
$router->add('/post/{slug}', [PostController::class, 'show']);

$request = Request::fromGlobals();

try {
    $match = $router->match($request->path());
    [$class, $method] = $match['handler'];

    $pdo = $database->pdo();

    $controller = match ($class) {
        HomeController::class => new HomeController(
            new CategoryRepository($pdo),
            new PostRepository($pdo),
            $view,
            $seo,
        ),
        CategoryController::class => new CategoryController(
            new CategoryRepository($pdo),
            new PostRepository($pdo),
            $view,
            $seo,
        ),
        PostController::class => new PostController(
            new CategoryRepository($pdo),
            new PostRepository($pdo),
            $view,
            $seo,
        ),
        default => throw new LogicException(sprintf('No wiring for controller "%s".', $class)),
    };

    echo $controller->$method($request, ...array_values($match['params']));
} catch (NotFoundException) {
    http_response_code(404);
    echo $view->render('404.tpl', ['title' => 'Страница не найдена']);
} catch (Throwable $error) {
    http_response_code(500);
    error_log((string) $error);

    if ($config->get('APP_DEBUG') === '1') {
        echo '<pre>' . htmlspecialchars((string) $error, ENT_QUOTES) . '</pre>';
    } else {
        echo 'Внутренняя ошибка сервера.';
    }
}
