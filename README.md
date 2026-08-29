# PHP Blog

Блог на чистом PHP 8.1+ с MySQL и Smarty. Без фреймворка: свой роутер, репозитории на PDO, шаблоны Smarty, стили на SCSS.

## Стек

- PHP 8.3 (fpm-alpine), расширения `pdo_mysql`, `gd`
- MySQL 8.0
- Nginx
- Smarty 5, SCSS (sass через Node)
- PHPUnit 10

## Быстрый старт

```bash
cp .env.example .env          # заполнить пароли
docker compose up -d --build
docker compose exec php php bin/console migrate
docker compose exec php php bin/console seed
```

`up` сам ставит зависимости: php-контейнер делает `composer install`, если нет `vendor/autoload.php`, node-контейнер собирает `public/assets/css/app.css`, если его нет, и выходит.

Сайт: http://localhost:8080

## Команды

```bash
docker compose exec php php bin/console migrate            # накатить новые миграции
docker compose exec php php bin/console migrate:status     # что применено, что нет
docker compose exec php php bin/console migrate:fresh      # снести все таблицы и накатить заново
docker compose exec php php bin/console seed [--fresh]     # 60 постов, 6 категорий, обложки через GD
docker compose exec php vendor/bin/phpunit                 # тесты
```

`migrate:fresh` спрашивает имя базы для подтверждения; в неинтерактивной оболочке требует `--force`.

Пересборка стилей при разработке: `docker compose run --rm node npm run watch`. Разовая сборка: `docker compose run --rm node npm run build`.

## Структура

```
bin/console          CLI: миграции и сиды
database/            Migrator, Seeder, миграции (.sql)
public/index.php     фронт-контроллер: роуты, DI, обработка 404/500
src/Core/            Router, Request, Config, Database, View, Paginator, Seo, ErrorPage
src/Repository/      выборки постов и категорий на PDO
src/Model/           Post, Category
templates/           Smarty-шаблоны
scss/                исходники стилей
tests/               PHPUnit: Core, Repository, Controller, View
```

## Маршруты

| Путь | Описание |
|------|----------|
| `/` | главная: по 3 свежих поста в каждой категории |
| `/category/{slug}` | листинг категории, `?page=N`, `?sort=date\|views` |
| `/post/{slug}` | пост |

## Тесты

База для тестов — `blog_test`, её создаёт init-скрипт MySQL при первом старте контейнера. Схему накатывает `tests/bootstrap.php`, каждый тест идёт в транзакции с откатом. Имя базы обязано оканчиваться на `_test` — иначе бутстрап откажется работать.

## Конфигурация

Всё через `.env` (см. `.env.example`): доступы к базе, `APP_URL` (используется для canonical), `APP_PORT`, `APP_DEBUG` (при `1` вместо страницы 500 показывается трейс).
