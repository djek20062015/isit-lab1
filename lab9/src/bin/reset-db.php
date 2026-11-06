<?php
require dirname(__DIR__).'/app/bootstrap.php';
try {
 $pdo=db();
 foreach(['schema.sql','seed.sql'] as $file){
  $pdo->exec(file_get_contents(dirname(__DIR__).'/sql/'.$file));
  echo "Выполнен sql/$file\n";
 }
 echo "База данных инициализирована\n";
}catch(Throwable $e){fwrite(STDERR,"Ошибка: ".$e->getMessage()."\n");exit(1);}
