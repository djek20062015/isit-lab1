<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Queue.php';
require_once __DIR__ . '/NotificationJob.php';
require_once __DIR__ . '/ReportJob.php';

final class Jobs
{
    public static function make(string $type, array $payload): array
    {
        return [
            'id' => db()->query('SELECT gen_random_uuid()')->fetchColumn(),
            'type' => $type,
            'payload' => $payload,
            'attempt' => 0,
            'created_at' => gmdate(DATE_ATOM),
        ];
    }

    public static function encode(array $job): string
    {
        return json_encode($job, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public static function sync(): bool
    {
        return getenv('QUEUE_MODE') === 'sync'
            || (getenv('APP_DEBUG') === '1' && ($_SERVER['HTTP_X_QUEUE_MODE'] ?? '') === 'sync');
    }

    public static function stage(array $job): void
    {
        $sql = 'INSERT INTO job_outbox(id, queue, body) VALUES (:id, :queue, :body)';
        db()->prepare($sql)->execute([
            'id' => $job['id'],
            'queue' => $job['type'] === 'report' ? 'reports' : 'default',
            'body' => self::encode($job),
        ]);
    }

    public static function publish(string $name, ?string $id = null): int
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $sql = 'SELECT id, body FROM job_outbox WHERE queue=:queue'
                . ($id === null ? '' : ' AND id=:id')
                . ' ORDER BY created_at, id LIMIT 50 FOR UPDATE SKIP LOCKED';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($id === null ? ['queue' => $name] : ['queue' => $name, 'id' => $id]);
            $rows = $stmt->fetchAll();
            $delete = $pdo->prepare('DELETE FROM job_outbox WHERE id=:id');
            $queue = new Queue($name);
            foreach ($rows as $row) {
                $queue->push($row['body']);
                $delete->execute(['id' => $row['id']]);
            }
            $pdo->commit();
            return count($rows);
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
    }

    public static function afterCommit(array $job): void
    {
        if (self::sync()) {
            try {
                self::run($job);
            } catch (Throwable $error) {
                self::terminal($job);
                error_log($error->getMessage());
                throw $error;
            }
            return;
        }
        try {
            self::publish($job['type'] === 'report' ? 'reports' : 'default', $job['id']);
        } catch (Throwable $error) {
            error_log('Задача сохранена в outbox: ' . $error->getMessage());
        }
    }

    public static function run(array $job): void
    {
        match ($job['type']) {
            'notification' => NotificationJob::handle($job),
            'report' => ReportJob::handle($job['payload']['request_id']),
            'demo' => self::demo($job),
            default => throw new InvalidArgumentException('Неизвестный тип задачи'),
        };
    }

    public static function terminal(array $job): void
    {
        if ($job['type'] === 'report') {
            ReportJob::fail($job['payload']['request_id']);
        }
    }

    private static function demo(array $job): void
    {
        self::pause((float) ($job['payload']['seconds'] ?? 0));
        $mode = $job['payload']['mode'] ?? 'ok';
        if ($mode === 'fail' || ($mode === 'flaky' && $job['attempt'] < 2)) {
            throw new RuntimeException('Демонстрационная ошибка');
        }
    }

    public static function pause(float $seconds): void
    {
        $deadline = microtime(true) + $seconds;
        while (($remaining = $deadline - microtime(true)) > 0) {
            usleep((int) min(250000, ceil($remaining * 1000000)));
        }
    }
}
