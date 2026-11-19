<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

$name = 'default';
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--queue=')) {
        $name = substr($arg, 8);
    }
}
$stop = false;
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    $signal = static function () use (&$stop): void { $stop = true; };
    pcntl_signal(SIGTERM, $signal);
    pcntl_signal(SIGINT, $signal);
}

try {
    $queue = new Queue($name);
    $lock = fopen(dirname(__DIR__) . "/storage/worker-$name.lock", 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
        throw new RuntimeException("Воркер очереди $name уже работает");
    }
    printf("Воркер %s · PID %d · возвращено в очередь: %d\n", $name, getmypid(), $queue->recover());

    while (!$stop) {
        Jobs::publish($name);
        $queue->promote();
        $raw = $queue->reserve();
        if ($raw === null) {
            if (in_array('--until-empty', $argv, true) && $queue->stats()['delayed'] === 0) {
                break;
            }
            continue;
        }
        $job = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        $start = hrtime(true);
        printf("%s · %s · попытка %d\n", $job['id'], $job['type'], $job['attempt'] + 1);
        try {
            Jobs::run($job);
        } catch (Throwable $error) {
            $job['attempt']++;
            $job['last_error'] = $error->getMessage();
            $terminal = $job['attempt'] >= 3;
            if ($terminal) {
                Jobs::terminal($job);
            }
            $queue->fail($raw, $job, $terminal);
            printf("%s · %s · %s\n", $job['id'], $error->getMessage(),
                $terminal ? 'мёртвая очередь' : 'повтор через ' . 2 ** $job['attempt'] . ' с');
            continue;
        }
        $queue->ack($raw);
        printf("%s · готово за %.3f с\n", $job['id'], (hrtime(true) - $start) / 1e9);
    }
    echo "Воркер завершён\n";
} catch (Throwable $error) {
    if (!$stop) {
        fwrite(STDERR, $error->getMessage() . PHP_EOL);
        exit(1);
    }
}
