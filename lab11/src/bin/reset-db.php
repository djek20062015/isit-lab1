<?php
require dirname(__DIR__).'/app/bootstrap.php';
try {
 $locks=[];
 foreach(Queue::NAMES as $name){
  $lock=fopen(dirname(__DIR__)."/storage/worker-$name.lock",'c');
  if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Перед сбросом остановить воркеры');
  $locks[]=$lock;
 }
 foreach(Queue::NAMES as $name)(new Queue($name))->clear();
 $pdo=db();
 $pdo->beginTransaction();
 foreach(['schema.sql','seed.sql'] as $file){
  $pdo->exec(file_get_contents(dirname(__DIR__).'/sql/'.$file));
  echo "Выполнен sql/$file\n";
 }
 $pdo->commit();
 foreach(glob(dirname(__DIR__).'/storage/reports/{report-*.csv,tmp-*}',GLOB_BRACE) as $file)unlink($file);
 echo Cache::clear() ? "База данных инициализирована, кеш очищен\n"
                     : "База данных инициализирована, версии кеша обновлены\n";
}catch(Throwable $e){fwrite(STDERR,"Ошибка: ".$e->getMessage()."\n");exit(1);}
