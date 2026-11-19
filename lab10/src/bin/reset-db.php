<?php
require dirname(__DIR__).'/app/bootstrap.php';
try {
 $pdo=db();
 $pdo->beginTransaction();
 foreach(['schema.sql','seed.sql'] as $file){
  $pdo->exec(file_get_contents(dirname(__DIR__).'/sql/'.$file));
  echo "Выполнен sql/$file\n";
 }
 $pdo->commit();
 echo Cache::clear() ? "База данных инициализирована, кеш очищен\n"
                     : "База данных инициализирована, версии кеша обновлены\n";
}catch(Throwable $e){fwrite(STDERR,"Ошибка: ".$e->getMessage()."\n");exit(1);}
