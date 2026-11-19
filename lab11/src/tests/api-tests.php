<?php
require dirname(__DIR__).'/app/bootstrap.php';
require __DIR__.'/http.php';
$base=rtrim($argv[1]??'http://127.0.0.1:8000','/');
$pass=$fail=0;
function work(string $queue): void {
    $command=[PHP_BINARY, ...phpFlags(), dirname(__DIR__).'/bin/worker.php', "--queue=$queue", '--until-empty'];
    $proc=proc_open($command, [1=>STDOUT, 2=>STDERR], $pipes);
    if (!is_resource($proc) || proc_close($proc)!==0) throw new RuntimeException('Воркер завершился с ошибкой');
}
function phpFlags(): array {
    $flags=getenv('LAB11_PHP_ARGS');
    return $flags===false ? [] : json_decode($flags,true,512,JSON_THROW_ON_ERROR);
}
function login(string $name): string {
    return callApi('POST','/api/auth/login',['email'=>"$name@example.com",'password'=>'123456'])['json']['token'];
}
function headerOf(array $response,string $name): ?string {
    preg_match('/^'.preg_quote($name,'/').':\s*([^\r\n]+)\r?$/im',$response['headers'],$match);
    return $match[1]??null;
}
try {
    $editor=login('igor'); $manager=login('anna'); $viewer=login('maria');
    $default=new Queue('default'); $reports=new Queue('reports');
    check('Обе очереди пусты после сброса',$default->stats()['ready']===0 && $reports->stats()['ready']===0);
    $getNotes=fn(string $token)=>callApi('GET','/api/notifications',null,$token)['json'];
    code('Уведомления требуют входа',401,callApi('GET','/api/notifications'));
    code('Отчёт требует входа',401,callApi('POST','/api/reports'));
    $noJob=callApi('PATCH','/api/events/1',['description'=>'Без смены статуса'],$editor);
    check('Редактура без смены статуса не создаёт задачу',$noJob['status']===200 && headerOf($noJob,'X-Job-Id')===null);
    check('Редактура оставляет очередь пустой',$default->stats()['ready']===0);
    $change=callApi('PATCH','/api/events/1',['status'=>'completed'],$editor);
    code('Статус меняется сразу',200,$change);
    $id=headerOf($change,'X-Job-Id');
    check('Ответ содержит UUID задачи',is_string($id)&&preg_match('/^[a-f0-9-]{36}$/',$id)===1);
    check('Задача ожидает при выключенном воркере',$default->stats()['ready']===1);
    $raw=$default->inspect('ready')[0];
    check('В JSON очереди тот же ID',json_decode($raw,true)['id']===$id);
    check('Уведомления ждут воркера',$getNotes($manager)===[]);
    work('default');
    $notes=$getNotes($manager);
    check('Руководитель получил уведомление',count($notes)===1 && $notes[0]['job_id']===$id);
    check('Уведомление содержит название и новый статус',str_contains($notes[0]['message'],'Форум цифровых технологий')
        && str_contains($notes[0]['message'],'completed'));
    check('Редактор не получатель',$getNotes($editor)===[]);
    check('Подтверждение удаляет резерв',$default->stats()['working']===0);
    $default->push($raw); work('default');
    check('Повтор уведомления не даёт дубля',count($getNotes($manager))===1);
    $sync=callApi('PATCH','/api/events/1',['status'=>'active'],$editor,null,['X-Queue-Mode: sync']);
    check('Sync обрабатывает уведомление до ответа',$sync['status']===200 && count($getNotes($manager))===2
        && $default->stats()['ready']===0);
    $byManager=callApi('PATCH','/api/events/1',['status'=>'planned'],$manager);
    code('Смена статуса руководителем',200,$byManager);
    work('default');
    check('Руководитель не уведомляется о своём изменении',count($getNotes($manager))===2);

    $request=callApi('POST','/api/reports',null,$editor);
    code('Асинхронный экспорт отвечает 202',202,$request);
    $url=headerOf($request,'Location');
    check('Location указывает на заявку',$url==='/api/reports/'.$request['json']['id']);
    check('Экспорт направляется в отдельную очередь',$reports->stats()['ready']===1 && $default->stats()['ready']===0);
    $export=$reports->inspect('ready')[0];
    check('Заявка в статусе queued',callApi('GET',$url,null,$editor)['json']['status']==='queued');
    code('Скачивание до готовности',409,callApi('GET',$url.'/download',null,$editor));
    code('Чужая заявка закрыта',403,callApi('GET',$url,null,$viewer));
    code('Руководитель видит заявку',200,callApi('GET',$url,null,$manager));
    code('Неизвестная заявка',404,callApi('GET','/api/reports/999999',null,$manager));
    work('reports');
    $report=callApi('GET',$url,null,$editor)['json'];
    check('Отчёт done и содержит семь строк',$report['status']==='done' && $report['rows_count']===7);
    $csv=callApi('GET',$report['download_url'],null,$editor);
    code('Скачивание отчёта',200,$csv);
    check('CSV имеет BOM, заголовок и семь записей',str_starts_with($csv['body'],"\xEF\xBB\xBF")
        && str_contains($csv['body'],'Форум цифровых технологий') && substr_count($csv['body'],"\r\n")===8);
    code('Чужой CSV закрыт',403,callApi('GET',$report['download_url'],null,$viewer));
    $reports->push($export); work('reports');
    check('Повтор не меняет опубликованный CSV',$csv['body']===callApi('GET',$report['download_url'],null,$editor)['body']);

    $failing=Jobs::make('demo',['mode'=>'fail','seconds'=>0]);
    $flaky=Jobs::make('demo',['mode'=>'flaky','seconds'=>0]);
    $default->push(Jobs::encode($failing)); $default->push(Jobs::encode($flaky));
    work('default');
    $dead=json_decode($default->inspect('dead')[0],true);
    check('После трёх попыток постоянный сбой в dead',$dead['id']===$failing['id'] && $dead['attempt']===3
        && str_contains($dead['last_error'],'Демонстрационная ошибка'));
    check('Временный сбой преодолён и отложенных задач нет',$default->stats()===['ready'=>0,'working'=>0,'delayed'=>0,'dead'=>1]);
    $raw=Jobs::encode(Jobs::make('demo',['seconds'=>0]));
    $default->push($raw); $default->reserve();
    check('Неподтверждённая задача остаётся в working',$default->stats()['working']===1);
    work('default');
    check('Новый воркер восстанавливает и подтверждает задачу',$default->stats()['working']===0 && $default->stats()['ready']===0);
    printf("\nИтого: %d PASS, %d FAIL\n",$pass,$fail);
    exit($fail?1:0);
} catch (Throwable $error) {
    fwrite(STDERR,$error->getMessage().PHP_EOL);
    exit(2);
}
