<?php
declare(strict_types=1);
function db(): PDO {
 static $pdo=null;
 if ($pdo === null) {
  $host=getenv('DB_HOST') ?: '127.0.0.1'; $port=getenv('DB_PORT') ?: '5432';
  $name=getenv('DB_NAME') ?: 'eventapp'; $user=getenv('DB_USER') ?: 'postgres';
  $pass=getenv('DB_PASSWORD') ?: 'secret';
  $pdo=new PDO("pgsql:host=$host;port=$port;dbname=$name",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
 }
 return $pdo;
}
function respond(mixed $data,int $status=200,array $headers=[]): never {
 http_response_code($status);header('Content-Type: application/json; charset=utf-8');
 foreach($headers as $k=>$v)header("$k: $v");
 if($status!==204)echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
 exit;
}
function fail(string $message,int $status,array $errors=[]): never {
 $body=['error'=>$message];if($errors)$body['errors']=$errors;respond($body,$status);
}
function body(): array {
 $type=strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]));
 if($type!=='application/json')fail('Требуется Content-Type: application/json',415);
 $raw=file_get_contents('php://input');$data=json_decode($raw,true);
 if(!is_array($data)||array_is_list($data))fail('Тело запроса должно быть корректным JSON-объектом',400);
 return $data;
}
function public_user(array $u): array {return ['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];}
function auth(): array {
 $header=$_SERVER['HTTP_AUTHORIZATION']??'';
 if(!preg_match('/^Bearer ([a-f0-9]{64})$/i',$header,$m))fail('Требуется авторизация',401);
 $stmt=db()->prepare('SELECT u.* FROM auth_tokens t JOIN users u ON u.id=t.user_id WHERE t.token_hash=:hash AND t.expires_at>now()');
 $stmt->execute(['hash'=>hash('sha256',$m[1])]);$u=$stmt->fetch();if(!$u)fail('Сессия истекла. Войдите снова.',401);
 return $u;
}
function role(array $u,string $required='manager'): void {if($u['role']!==$required)fail('Недостаточно прав для этого действия',403);}
function row(string $table,int $id): ?array {
 $stmt=db()->prepare("SELECT * FROM $table WHERE id=:id");$stmt->execute(['id'=>$id]);return $stmt->fetch()?:null;
}
function clean_row(array $r): array {
 foreach($r as $k=>$v)if(in_array($k,['id','venue_id','category_id','organizer_id','capacity'],true)&&$v!==null)$r[$k]=(int)$v;
 return $r;
}
function fields(string $resource): array {
 return match($resource){
  'events'=>['title','venue_id','category_id','organizer_id','description','status','starts_at','ends_at','ticket_price'],
  'venues'=>['name','address','capacity'],
  'categories'=>['name','description'],default=>[]};
}
function validate(string $resource,array $data): array {
 $e=[];
 if($resource==='events'){
  if(!is_string($data['title']??null)||mb_strlen(trim($data['title']))<3)$e['title']='Название — не короче 3 символов';
  foreach(['venue_id','category_id'] as $k)if(!is_int($data[$k]??null)||$data[$k]<1)$e[$k]='Требуется положительный целый идентификатор';
  if(isset($data['organizer_id'])&&(!is_int($data['organizer_id'])||$data['organizer_id']<1))$e['organizer_id']='Неверный идентификатор';
  if(!in_array($data['status']??'', ['planned','active','completed','cancelled'],true))$e['status']='Допустимо: planned, active, completed, cancelled';
  foreach(['starts_at','ends_at'] as $k)if(!is_string($data[$k]??null)||strtotime($data[$k])===false)$e[$k]='Требуется дата и время ISO 8601';
  if(!isset($e['starts_at'],$e['ends_at'])&&isset($data['starts_at'],$data['ends_at'])&&strtotime($data['ends_at'])<=strtotime($data['starts_at']))$e['ends_at']='Окончание должно быть позже начала';
  if(!is_numeric($data['ticket_price']??null)||(float)$data['ticket_price']<0)$e['ticket_price']='Цена должна быть неотрицательной';
 }elseif($resource==='venues'){
  if(!is_string($data['name']??null)||mb_strlen(trim($data['name']))<3)$e['name']='Название — не короче 3 символов';
  if(!is_string($data['address']??null)||trim($data['address'])==='')$e['address']='Адрес обязателен';
  if(!is_int($data['capacity']??null)||$data['capacity']<1)$e['capacity']='Вместимость — положительное целое число';
 }else{
  if(!is_string($data['name']??null)||mb_strlen(trim($data['name']))<2)$e['name']='Название — не короче 2 символов';
 }
 return $e;
}
function db_failure(PDOException $e): never {
 $code=$e->getCode();
 if($code==='23503')fail('Связанная запись не существует или используется другими записями',409);
 if($code==='23505')fail('Запись с таким значением уже существует',409);
 if($code==='23514'||$code==='23502')fail('Нарушено ограничение данных',422);
 throw $e;
}
