<?php
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
