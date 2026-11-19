<?php
require dirname(__DIR__).'/app/bootstrap.php';
try{$start=microtime(true);db()->exec(file_get_contents(dirname(__DIR__).'/sql/bigdata.sql'));printf("Загрузка завершена за %.2f с; мероприятий: %d\n",microtime(true)-$start,(int)db()->query('SELECT count(*) FROM events')->fetchColumn());}
catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
