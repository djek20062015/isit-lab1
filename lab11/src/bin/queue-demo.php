<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$mode = in_array('--fail', $argv, true) ? 'fail' : (in_array('--flaky', $argv, true) ? 'flaky' : 'ok');
$slow = in_array('--slow', $argv, true);
$count = max(1, (int) ($argv[1] ?? 3));
$queue = new Queue('default');
for ($i = 0; $i < $count; $i++) {
    $job = Jobs::make('demo', ['mode' => $mode, 'seconds' => $slow ? 20 : 1]);
    $queue->push(Jobs::encode($job));
    echo $job['id'] . PHP_EOL;
}
