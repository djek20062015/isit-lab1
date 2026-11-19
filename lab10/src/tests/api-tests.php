<?php
// Проверки HTTP API выполняются на отдельном локальном сервере после reset-db.php.
require dirname(__DIR__).'/app/bootstrap.php';
$base=rtrim($argv[1]??'http://localhost:8000','/');$pass=0;$fail=0;
function callApi(string $method,string $path,?array $data=null,?string $token=null,?string $raw=null,array $extra=[]): array {
 global $base;
 $headers=['Accept: application/json'];if($token)$headers[]='Authorization: Bearer '.$token;
 $body=$raw??($data!==null?json_encode($data,JSON_UNESCAPED_UNICODE):null);
 if($body!==null)$headers[]='Content-Type: application/json';
 $headers=array_merge($headers,$extra);
 $ch=curl_init($base.$path);curl_setopt_array($ch,[CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>8,CURLOPT_HEADER=>true]);
 if($body!==null)curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
 $result=curl_exec($ch);if($result===false)throw new RuntimeException(curl_error($ch));
 $status=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$size=curl_getinfo($ch,CURLINFO_HEADER_SIZE);$head=substr($result,0,$size);$body=substr($result,$size);curl_close($ch);
 return ['status'=>$status,'json'=>json_decode($body,true),'body'=>$body,'headers'=>$head];
}
function check(string $name,bool $ok):void{global $pass,$fail;if($ok){$pass++;echo "PASS $name\n";}else{$fail++;echo "FAIL $name\n";}}
function code(string $name,int $expected,array $r):void{check($name,$r['status']===$expected);}
try{
 code('Нет токена',401,callApi('GET','/api/events'));
 code('Неверный пароль',401,callApi('POST','/api/auth/login',['email'=>'anna@example.com','password'=>'wrong']));
 $m=callApi('POST','/api/auth/login',['email'=>'anna@example.com','password'=>'123456']);code('Вход руководителя',200,$m);$mt=$m['json']['token']??'';
 check('Токен 64 символа',strlen($mt)===64);check('Нет password_hash при входе',!isset($m['json']['user']['password_hash']));
 code('Кто я',200,callApi('GET','/api/auth/me',null,$mt));
 foreach(['events','venues','categories'] as $res){$r=callApi('GET',"/api/$res",null,$mt);code("Список $res",200,$r);check("Список $res — массив",is_array($r['json'])&&array_is_list($r['json']));}
 code('Отсутствующая запись',404,callApi('GET','/api/events/999999',null,$mt));
 code('Недопустимый метод',405,callApi('DELETE','/api/events',null,$mt));
 code('Невалидный JSON',400,callApi('POST','/api/events',null,$mt,'{broken'));
 code('Ошибка полей',422,callApi('POST','/api/events',['title'=>'x'], $mt));
 $editor=callApi('POST','/api/auth/login',['email'=>'igor@example.com','password'=>'123456']);$et=$editor['json']['token']??'';
 code('Вход редактора',200,$editor);
 code('Редактор не удаляет',403,callApi('DELETE','/api/events/1',null,$et));
 code('Редактор не создаёт площадки',403,callApi('POST','/api/venues',['name'=>'Новая площадка','address'=>'Адрес','capacity'=>50],$et));
 $viewer=callApi('POST','/api/auth/login',['email'=>'maria@example.com','password'=>'123456']);$vt=$viewer['json']['token']??'';
 code('Наблюдатель не создаёт мероприятия',403,callApi('POST','/api/events',['title'=>'Проверка'], $vt));
 $payload=['title'=>'Тестовое мероприятие','venue_id'=>1,'category_id'=>1,'starts_at'=>'2027-07-01T10:00:00+03:00','ends_at'=>'2027-07-01T12:00:00+03:00','status'=>'planned','ticket_price'=>0];
 $created=callApi('POST','/api/events',$payload,$mt);code('Создание мероприятия',201,$created);$id=$created['json']['id']??0;
 check('Заголовок Location',str_contains($created['headers'],"/api/events/$id"));
 code('Чтение созданного',200,callApi('GET',"/api/events/$id",null,$mt));
 code('Изменение PATCH',200,callApi('PATCH',"/api/events/$id",['status'=>'completed'],$et));
 code('Изменение PUT',200,callApi('PUT',"/api/events/$id",['description'=>'Обновлено'],$mt));
 code('Фильтр статуса',200,callApi('GET','/api/events?status=completed',null,$mt));
 code('Поиск',200,callApi('GET','/api/events?q='.rawurlencode('Тестовое'),null,$mt));
 code('Удаление мероприятия',204,callApi('DELETE',"/api/events/$id",null,$mt));
 code('Повторное удаление',404,callApi('DELETE',"/api/events/$id",null,$mt));
 $v=callApi('POST','/api/venues',['name'=>'Тестовый зал','address'=>'Тестовый адрес','capacity'=>20],$mt);code('Создание площадки',201,$v);$vid=$v['json']['id']??0;
 code('Изменение площадки',200,callApi('PATCH',"/api/venues/$vid",['capacity'=>30],$mt));
 code('Удаление площадки',204,callApi('DELETE',"/api/venues/$vid",null,$mt));
 $c=callApi('POST','/api/categories',['name'=>'Тестовая категория'], $mt);code('Создание категории',201,$c);$cid=$c['json']['id']??0;
 code('Изменение категории',200,callApi('PATCH',"/api/categories/$cid",['description'=>'Описание'], $mt));
 code('Удаление категории',204,callApi('DELETE',"/api/categories/$cid",null,$mt));
 code('Защита связанной площадки',409,callApi('DELETE','/api/venues/1',null,$mt));
 // Отдельный сценарий запускается на тестовом стеке с APP_DEBUG=1.
 check('Redis доступен тестам',Cache::redis()!==null);
 check('Очистка кеша приложения',Cache::clear());
 $get=fn(string $path)=>callApi('GET',$path,null,$mt);
 $state=fn(array $r,string $status)=>str_contains($r['headers'],"X-Cache: $status");
 $report='/api/reports/summary';
 $list='/api/events?status=planned';
 $item='/api/events/1';
 foreach([$report,$list,$item,'/api/venues','/api/categories'] as $path){
  $cold=$get($path);$warm=$get($path);
  check("$path: 200 и MISS",$cold['status']===200&&$state($cold,'MISS'));
  check("$path: HIT и идентичные данные",$state($warm,'HIT')&&$warm['json']===$cold['json']);
 }
 $r=Cache::redis();
 $revision=cache_revision('report');
 $ttl=$r ? $r->ttl("report:summary:v$revision") : -2;
 check('TTL отчёта в диапазоне 1–138 с',$ttl>0&&$ttl<=138);
 $other=$get('/api/events?status=active');
 check('Фильтры дают отдельные ключи и разные данные',$state($other,'MISS')&&count($other['json']??[])>0
  &&array_column($other['json']??[],'status')===array_fill(0,count($other['json']??[]),'active'));
 $off=callApi('GET',$report,null,$mt,null,['X-Cache-Mode: off']);
 check('Принудительный BYPASS возвращает те же данные',$state($off,'BYPASS')&&$off['json']===$get($report)['json']);

 $beforeReport=$get($report)['json'];
 $createdForCache=callApi('POST','/api/events',$payload,$mt);
 code('POST для проверки кеша',201,$createdForCache);
 $cacheId=$createdForCache['json']['id']??0;
 $afterPost=$get($list);
 check('POST инвалидирует список',$state($afterPost,'MISS')&&in_array($cacheId,array_column($afterPost['json'],'id'),true));
 $afterReport=$get($report);
 check('POST инвалидирует отчёт',$state($afterReport,'MISS')&&$afterReport['json']!==$beforeReport);
 $card="/api/events/$cacheId";
 $get($card);
 code('PATCH для проверки кеша',200,callApi('PATCH',$card,['status'=>'completed','description'=>'Проверка инвалидации'],$mt));
 $afterPatch=$get($card);
 check('PATCH инвалидирует карточку',$state($afterPatch,'MISS')&&($afterPatch['json']['description']??'')==='Проверка инвалидации');
 $afterPatchList=$get($list);
 check('PATCH инвалидирует список',$state($afterPatchList,'MISS')&&!in_array($cacheId,array_column($afterPatchList['json'],'id'),true));
 $get($report);
 code('DELETE для проверки кеша',204,callApi('DELETE',$card,null,$mt));
 code('Удалённая карточка недоступна',404,$get($card));
 $afterDelete=$get($report);
 check('DELETE инвалидирует отчёт',$state($afterDelete,'MISS')&&$afterDelete['json']===$beforeReport);
 $get('/api/venues');$get($report);
 code('PATCH площадки',200,callApi('PATCH','/api/venues/1',['capacity'=>351],$mt));
 check('PATCH площадки инвалидирует справочник и отчёт',$state($get('/api/venues'),'MISS')&&$state($get($report),'MISS'));
 $get('/api/categories');
 code('PATCH категории',200,callApi('PATCH','/api/categories/1',['description'=>'Проверка кеша'],$mt));
 check('PATCH категории инвалидирует только её справочник',$state($get('/api/categories'),'MISS')&&$state($get($report),'HIT'));
 code('Выход',204,callApi('POST','/api/auth/logout',null,$mt));
 code('Токен отозван',401,callApi('GET','/api/events',null,$mt));
 check('Ответ без password_hash',!str_contains(json_encode($created),'password_hash'));
 echo "\nИтого: $pass PASS, $fail FAIL\n";exit($fail?1:0);
}catch(Throwable $e){fwrite(STDERR,'Тестирование прервано: '.$e->getMessage()."\n");exit(2);}
