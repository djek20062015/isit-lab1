<?php
require dirname(__DIR__).'/app/bootstrap.php';
if (($redis=Cache::redis())!==null) $redis->del("demo:slow");
for($i=1;$i<=3;$i++){
 $start=microtime(true);
 $value=Cache::remember('demo:slow',30,function(){usleep(700000);return ['value'=>'example'];});
 printf("Вызов %d: %s, %.2f мс, значение %s\n",$i,Cache::status(),(microtime(true)-$start)*1000,json_encode($value));
}
