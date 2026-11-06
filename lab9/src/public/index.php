<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);$method=$_SERVER['REQUEST_METHOD']??'GET';
if(str_starts_with($path,'/api/')){
 try {
  $origin=$_SERVER['HTTP_ORIGIN']??'';
  $allowed=['http://localhost:5500','http://127.0.0.1:5500','http://localhost:8000','http://127.0.0.1:8000'];
  if(in_array($origin,$allowed,true)){header("Access-Control-Allow-Origin: $origin");header('Vary: Origin');header('Access-Control-Allow-Headers: Authorization, Content-Type');header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');}
  if($method==='OPTIONS')respond(null,204);
  if($path==='/api/auth/login'){
   if($method!=='POST')respond(['error'=>'Метод не поддерживается'],405,['Allow'=>'POST']);
   $b=body();$email=$b['email']??'';$pass=$b['password']??'';
   if(!is_string($email)||!is_string($pass)||$email===''||$pass==='')fail('Укажите email и пароль',422);
   $st=db()->prepare('SELECT * FROM users WHERE email=:email');$st->execute(['email'=>$email]);$u=$st->fetch();
   if(!$u||!password_verify($pass,$u['password_hash']))fail('Неверный email или пароль',401);
   $token=bin2hex(random_bytes(32));$expiry=(new DateTimeImmutable('+2 hours'))->format(DateTimeInterface::ATOM);
   $st=db()->prepare('INSERT INTO auth_tokens(user_id,token_hash,expires_at) VALUES (:uid,:hash,:expires)');
   $st->execute(['uid'=>$u['id'],'hash'=>hash('sha256',$token),'expires'=>$expiry]);
   respond(['token'=>$token,'expires_at'=>$expiry,'user'=>public_user($u)]);
  }
  $user=auth();
  if($path==='/api/auth/me'&&$method==='GET')respond(public_user($user));
  if($path==='/api/auth/logout'&&$method==='POST'){
   $token=substr($_SERVER['HTTP_AUTHORIZATION'],7);
   $st=db()->prepare('DELETE FROM auth_tokens WHERE token_hash=:hash');$st->execute(['hash'=>hash('sha256',$token)]);respond(null,204);
  }
  if($path==='/api/auth/me'||$path==='/api/auth/logout')respond(['error'=>'Метод не поддерживается'],405,['Allow'=>$path==='/api/auth/me'?'GET':'POST']);
  if(!preg_match('#^/api/(events|venues|categories)(?:/(\d+))?$#',$path,$m))fail('Адрес не найден',404);
  $resource=$m[1];$id=isset($m[2])?(int)$m[2]:null;
  $allowedMethods=$id===null?['GET','POST']:['GET','PUT','PATCH','DELETE'];
  if(!in_array($method,$allowedMethods,true))respond(['error'=>'Метод не поддерживается'],405,['Allow'=>implode(', ',$allowedMethods)]);
  if($method==='GET'){
   if($id!==null){$r=row($resource,$id);if(!$r)fail('Запись не найдена',404);respond(clean_row($r));}
   $where=[];$params=[];
   if($resource==='events'){
    foreach(['status','venue_id','category_id'] as $filter)if(isset($_GET[$filter])&&$_GET[$filter]!==''){$where[]="$filter=:$filter";$params[$filter]=$_GET[$filter];}
    if(isset($_GET['q'])&&$_GET['q']!==''){$where[]='title ILIKE :q';$params['q']='%'.$_GET['q'].'%';}
   }
   $sort=in_array($_GET['sort']??'', ['id','-id','starts_at','-starts_at','title','-title'],true)?$_GET['sort']:'-id';
   $col=ltrim($sort,'-');if($resource!=='events'&&!in_array($col,['id','title'],true))$col='id';
   if($resource!=='events'&&$col==='title')$col='name';
   $dir=str_starts_with($sort,'-')?'DESC':'ASC';
   $page=max(1,min(100000,(int)($_GET['page']??1)));$limit=max(1,min(100,(int)($_GET['limit']??50)));
   $sql="SELECT * FROM $resource".($where?' WHERE '.implode(' AND ',$where):'')." ORDER BY $col $dir LIMIT $limit OFFSET ".(($page-1)*$limit);
   $st=db()->prepare($sql);$st->execute($params);respond(array_map('clean_row',$st->fetchAll()));
  }
  if($method==='POST'||$method==='PUT'||$method==='PATCH'){
   if($resource!=='events')role($user);
   if($method==='POST'&&$id!==null)fail('Адрес не найден',404);
   $old=$id!==null?row($resource,$id):null;if($id!==null&&!$old)fail('Запись не найдена',404);
   $b=body();$data=array_intersect_key($b,array_flip(fields($resource)));
   if($method==='POST'&&$resource==='events'){$data+=['status'=>'planned','ticket_price'=>0,'organizer_id'=>null,'description'=>null];}
   $data=$old?array_merge($old,$data):$data;
   $errors=validate($resource,$data);if($errors)fail('Ошибка проверки данных',422,$errors);
   $write=array_intersect_key($data,array_flip(fields($resource)));
   try{
    if($method==='POST'){
     $cols=array_keys($write);$place=array_map(fn($k)=>':'.$k,$cols);
     $sql="INSERT INTO $resource (".implode(',',$cols).") VALUES (".implode(',',$place).") RETURNING *";
     $st=db()->prepare($sql);$st->execute($write);$created=clean_row($st->fetch());respond($created,201,['Location'=>"/api/$resource/".$created['id']]);
    }
    $sets=array_map(fn($k)=>"$k=:$k",array_keys($write));
    $st=db()->prepare("UPDATE $resource SET ".implode(',',$sets)." WHERE id=:row_id RETURNING *");
    $st->execute($write+['row_id'=>$id]);respond(clean_row($st->fetch()));
   }catch(PDOException $e){db_failure($e);}
  }
  if($method==='DELETE'){
   role($user);if(!row($resource,$id))fail('Запись не найдена',404);
   try{$st=db()->prepare("DELETE FROM $resource WHERE id=:id");$st->execute(['id'=>$id]);respond(null,204);}catch(PDOException $e){db_failure($e);}
  }
 }catch(PDOException $e){
  error_log($e->getMessage());
  if(str_starts_with((string)$e->getCode(),'08')||str_contains(strtolower($e->getMessage()),'connection refused')||str_contains(strtolower($e->getMessage()),'could not connect'))fail('База данных недоступна',503);
  fail('Ошибка базы данных',500);
 }catch(Throwable $e){error_log($e->getMessage());fail('Внутренняя ошибка сервера',500);}
}
if($path==='/'||$path==='/client') {header('Location: /client/');exit;}
http_response_code(404);echo 'Not Found';
