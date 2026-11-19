<?php
require dirname(__DIR__).'/tests/http.php';
$base='http://127.0.0.1:8000'; $n=5;
foreach($argv as $arg){
    if(str_starts_with($arg,'--n='))$n=max(1,(int)substr($arg,4));
    if(str_starts_with($arg,'--url='))$base=rtrim(substr($arg,6),'/');
}
function timed(callable $call): array {
    $start=hrtime(true);
    $response=$call();
    return [$response,(hrtime(true)-$start)/1e6,$start];
}
try {
    $login=callApi('POST','/api/auth/login',['email'=>'igor@example.com','password'=>'123456']);
    $token=$login['json']['token']??throw new RuntimeException('Не удалось войти');
    $current=callApi('GET','/api/events/1',null,$token)['json']['status'];
    $samples=[];
    for($i=0;$i<$n;$i++){
        foreach(['sync','async'] as $mode){
            $status=$current==='planned'?'active':'planned';
            $headers=$mode==='sync'?['X-Queue-Mode: sync']:[];
            [$response,$patch]=timed(fn()=>callApi('PATCH','/api/events/1',['status'=>$status],$token,null,$headers));
            if($response['status']!==200 || !str_contains($response['headers'],'X-Job-Id:'))throw new RuntimeException('Ошибка PATCH');
            $current=$status;
            [$report,$post,$start]=timed(fn()=>callApi('POST','/api/reports',null,$token,null,$headers));
            if($report['status']!==($mode==='sync'?201:202))throw new RuntimeException('Не совпал код заказа отчёта');
            $ready=$post;
            if($mode==='async'){
                do {
                    $result=callApi('GET','/api/reports/'.$report['json']['id'],null,$token);
                    $ready=(hrtime(true)-$start)/1e6;
                    $state=$result['json']['status']??'failed';
                    if($state==='failed'||$ready>30000)throw new RuntimeException('Отчёт не сформирован');
                    if($state!=='done')usleep(25000);
                } while($state!=='done');
            }
            $samples[]=['trial'=>$i+1,'mode'=>$mode,'patch_ms'=>$patch,'post_ms'=>$post,'report_ready_ms'=>$ready];
        }
    }
    echo json_encode($samples,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT).PHP_EOL;
} catch(Throwable $error){
    fwrite(STDERR,$error->getMessage().PHP_EOL);
    exit(1);
}
