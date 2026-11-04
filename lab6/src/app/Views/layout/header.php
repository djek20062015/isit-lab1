<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e(config('name')) ?></title><link rel="stylesheet" href="/css/style.css"></head><body>
<header><div class="container nav"><a class="brand" href="/">◈ EventManager</a><nav><a href="/">Главная</a><a href="/events">Мероприятия</a><a href="/venues">Площадки</a><a class="button small" href="/events/create">+ Создать</a></nav></div></header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): ?><div class="alert success"><?= e($_SESSION['flash']) ?></div><?php unset($_SESSION['flash']); endif; ?>
