<?php
require dirname(__DIR__).'/app/bootstrap.php';
$r=Cache::redis();if(!$r){fwrite(STDERR,"Redis недоступен (приложение продолжит работу без кеша)\n");exit(1);}
$info=$r->info('server');echo 'Redis отвечает: PONG, версия '.($info['redis_version']??'неизвестна')."\n";
