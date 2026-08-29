<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{if $title}{$title} — {/if}Блог</title>
{if $description}    <meta name="description" content="{$description}">
{/if}{if $canonical}    <link rel="canonical" href="{$canonical}">
{/if}    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="header">
    <div class="container">
        <a class="header__logo" href="/">Блог</a>
    </div>
</header>

<main class="container">
    {block name="content"}{/block}
</main>

<footer class="footer">
    <div class="container">Тестовый проект на чистом PHP</div>
</footer>
</body>
</html>
