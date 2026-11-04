<div class="hero"><span class="eyebrow">Панель организатора</span><h1>Управление мероприятиями</h1><p>Планируйте события, контролируйте публикации и работайте с площадками в единой системе.</p><a class="button" href="/events">Открыть каталог</a></div>
<h2>Обзор системы</h2><div class="cards">
<?php foreach (['total'=>'Всего мероприятий','draft'=>'Черновики','published'=>'Опубликовано','completed'=>'Завершено','venues'=>'Площадки'] as $key=>$label): ?><div class="card"><span><?= e($label) ?></span><strong><?= e($stats[$key]) ?></strong></div><?php endforeach; ?>
</div>