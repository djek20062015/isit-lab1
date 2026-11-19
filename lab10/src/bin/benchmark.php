<?php
/** Сравнение запросов через HTTP. Требуются запущенные PHP-сервер, PostgreSQL и Redis. */
require dirname(__DIR__).'/app/bootstrap.php';
$base=rtrim($argv[1]??'http://localhost:8000','/');
$iterations=max(30,(int)($argv[2]??30));
function request(string $method,string $url,?string $token=null,?array $payload=null,array $headers=[]): array {
 $head=['Accept: application/json'];if($token)$head[]='Authorization: Bearer '.$token;
 if($payload!==null)$head[]='Content-Type: application/json';
 foreach($headers as $h)$head[]=$h;
 $ctx=stream_context_create(['http'=>['method'=>$method,'header'=>implode("\r\n",$head),'content'=>$payload===null?'':json_encode($payload),'ignore_errors'=>true,'timeout'=>30]]);
 $start=microtime(true);$body=@file_get_contents($url,false,$ctx);$ms=(microtime(true)-$start)*1000;
 if($body===false)throw new RuntimeException('HTTP недоступен: '.$url);
 $status=(int)(preg_match('/HTTP\/\S+ (\d+)/',$http_response_header[0]??'',$m)?$m[1]:0);
 return ['ms'=>$ms,'status'=>$status,'json'=>json_decode($body,true),'headers'=>$http_response_header];
}
function sample(string $url,string $token,int $count,string $expected): array {
 $times=[];
 for($i=0;$i<$count;$i++){
  $headers=$expected==='BYPASS'?['X-Cache-Mode: off']:[];
  $response=request('GET',$url,$token,null,$headers);
  $cache=array_values(array_filter($response['headers'],fn($header)=>stripos($header,'X-Cache:')===0));
  $state=trim(substr($cache[0]??'',8));
  if($response['status']!==200||$state!==$expected){
   throw new RuntimeException("$url: ожидались HTTP 200 и $expected, получены {$response['status']} и $state");
  }
  $times[]=$response['ms'];
 }
 sort($times);
 return [
  'avg'=>array_sum($times)/count($times),
  'p95'=>$times[(int)ceil(count($times)*0.95)-1],
  'rps'=>1000*count($times)/array_sum($times),
 ];
}
try {
 $total=(int)db()->query('SELECT count(*) FROM events')->fetchColumn();
 if($total<100000)throw new RuntimeException('Требуется не менее 100 000 мероприятий: php bin/load-bigdata.php');
 if(!Cache::clear())throw new RuntimeException('Не удалось очистить кеш приложения перед измерением');
 $login=request('POST',$base.'/api/auth/login',null,['email'=>'anna@example.com','password'=>'123456']);
 if($login['status']!==200)throw new RuntimeException('Вход: '.$login['status']);
 $token=$login['json']['token'];
 $cases=[
  'Список площадок'=>'/api/venues',
  'Карточка мероприятия'=>'/api/events/1',
  'Последние мероприятия'=>'/api/events?sort=-id&limit=100',
  'Поиск по названию'=>'/api/events?q='.rawurlencode('технологии').'&sort=title',
  'Сводка по площадкам'=>'/api/reports/summary',
  'Фильтр по статусу'=>'/api/events?status=planned&limit=100',
  'Глубокая страница'=>'/api/events?sort=-id&page=500&limit=100',
  'Сортировка по названию'=>'/api/events?sort=title&limit=100',
 ];
 $results=[];
 foreach($cases as $label=>$path){
  $url=$base.$path;
  sample($url,$token,2,'BYPASS');
  $off=sample($url,$token,$iterations,'BYPASS');
  $cold=sample($url,$token,1,'MISS')['avg'];
  $warm=sample($url,$token,$iterations,'HIT');
  $results[]=['scenario'=>$label,'off'=>$off,'hit'=>$warm,'miss_ms'=>$cold,'speedup'=>$off['avg']/$warm['avg']];
 }
 request('POST',$base.'/api/auth/logout',$token);
 if(in_array('--json',$argv,true)){
  echo json_encode(['rows'=>$total,'requests_per_mode'=>$iterations,'results'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
  exit;
 }
 printf("Мероприятий: %d; запросов на режим: %d\n",$total,$iterations);
 echo "Сценарий | Среднее без / с, мс | p95 без / с, мс | RPS без / с | MISS, мс | Ускорение\n";
 foreach($results as $r){
  printf("%s | %.2f / %.2f | %.2f / %.2f | %.1f / %.1f | %.2f | %.2f×\n",
   $r['scenario'],$r['off']['avg'],$r['hit']['avg'],$r['off']['p95'],$r['hit']['p95'],
   $r['off']['rps'],$r['hit']['rps'],$r['miss_ms'],$r['speedup']);
 }
 echo "Все сценарии: первый запрос с кешем — MISS, остальные — HIT.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
