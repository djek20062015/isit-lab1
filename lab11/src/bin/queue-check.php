<?php
require dirname(__DIR__) . '/app/bootstrap.php';
putenv('QUEUE_PREFIX=eventapp11:check:' . bin2hex(random_bytes(8)) . ':');
$queue = new Queue('default');
try {
    $raw = Jobs::encode(Jobs::make('demo', ['seconds' => 0]));
    $queue->push($raw);
    printf("LPUSH: ожидают %d\n", $queue->stats()['ready']);
    if ($queue->reserve() !== $raw || $queue->stats()['working'] !== 1) {
        throw new RuntimeException('Резервирование не удалось');
    }
    printf("BLMOVE: ожидают %d, в работе %d\n", $queue->stats()['ready'], $queue->stats()['working']);
    $queue->ack($raw);
    if ($queue->stats()['working'] !== 0) {
        throw new RuntimeException('Подтверждение не прошло');
    }
    echo "LREM: в работе 0\nОчередь работает\n";
} finally {
    $queue->clear();
}
