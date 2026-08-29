<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\NotFoundException;
use App\Core\Request;
use App\Core\Router;
use App\Core\View;

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
$config = Config::fromFile($root . '/.env');
$database = new Database($config);
$view = new View(
    $root . '/templates',
    $root . '/var/smarty/compile',
    $root . '/var/smarty/cache',
);

$router = new Router();
$router->add('/', [App\Controller\HomeController::class, 'index']);

$request = Request::fromGlobals();

try {
    $match = $router->match($request->path());
    [$class, $method] = $match['handler'];

    $controller = new $class($database->pdo(), $view);
    echo $controller->$method($request, ...array_values($match['params']));
} catch (NotFoundException) {
    http_response_code(404);
    echo $view->render('404.tpl');
} catch (Throwable $error) {
    http_response_code(500);
    error_log((string) $error);

    if ($config->get('APP_DEBUG') === '1') {
        echo '<pre>' . htmlspecialchars((string) $error, ENT_QUOTES) . '</pre>';
    } else {
        echo 'Внутренняя ошибка сервера.';
    }
}
