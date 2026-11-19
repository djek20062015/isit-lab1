<?php
declare(strict_types=1);

final class Queue
{
    public const NAMES = ['default', 'reports'];

    public function __construct(private readonly string $name)
    {
        if (!in_array($name, self::NAMES, true)) {
            throw new InvalidArgumentException('Неизвестная очередь');
        }
    }

    private function redis(): Redis
    {
        static $client;
        if ($client === null) {
            $options = (require dirname(__DIR__, 2) . '/config/app.php')['cache'];
            $connection = new Redis();
            if (!$connection->connect($options['host'], $options['port'], 0.25, null, 0, 3.0)) {
                throw new RuntimeException('Redis недоступен');
            }
            $client = $connection;
        }
        return $client;
    }

    private function key(string $list): string
    {
        $prefix = getenv('QUEUE_PREFIX') ?: 'eventapp11:queue:';
        return "{$prefix}{$this->name}:$list";
    }

    public function push(string $raw): void
    {
        if ($this->redis()->lPush($this->key('ready'), $raw) === false) {
            throw new RuntimeException('Задача не принята Redis');
        }
    }

    public function reserve(): ?string
    {
        $raw = $this->redis()->blMove($this->key('ready'), $this->key('working'), 'RIGHT', 'LEFT', 1);
        return is_string($raw) ? $raw : null;
    }

    public function ack(string $raw): void
    {
        $this->redis()->lRem($this->key('working'), $raw, 1);
    }

    public function fail(string $raw, array $job, bool $terminal): void
    {
        $updated = json_encode($job, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $destination = $terminal ? 'dead' : 'delayed';
        $script = <<<'LUA'
        if redis.call('LREM', KEYS[1], 1, ARGV[1]) == 0 then return 0 end
        if ARGV[3] == 'dead' then
            return redis.call('LPUSH', KEYS[2], ARGV[2])
        end
        return redis.call('ZADD', KEYS[2], ARGV[4], ARGV[2])
        LUA;
        $delay = 2 ** $job['attempt'];
        $result = $this->redis()->eval($script, [
            $this->key('working'), $this->key($destination), $raw, $updated, $destination, microtime(true) + $delay,
        ], 2);
        if ($result === false) {
            throw new RuntimeException('Не удалось сохранить результат попытки');
        }
    }

    public function promote(): void
    {
        $script = <<<'LUA'
        local due = redis.call('ZRANGEBYSCORE', KEYS[1], '-inf', ARGV[1], 'LIMIT', 0, 100)
        for _, job in ipairs(due) do
            redis.call('LPUSH', KEYS[2], job)
            redis.call('ZREM', KEYS[1], job)
        end
        return #due
        LUA;
        $this->redis()->eval($script, [$this->key('delayed'), $this->key('ready'), microtime(true)], 2);
    }

    public function recover(): int
    {
        $count = 0;
        while (is_string($this->redis()->lMove($this->key('working'), $this->key('ready'), 'LEFT', 'RIGHT'))) {
            $count++;
        }
        return $count;
    }

    public function stats(): array
    {
        $redis = $this->redis();
        return [
            'ready' => $redis->lLen($this->key('ready')),
            'working' => $redis->lLen($this->key('working')),
            'delayed' => $redis->zCard($this->key('delayed')),
            'dead' => $redis->lLen($this->key('dead')),
        ];
    }

    public function inspect(string $list): array
    {
        if (!in_array($list, ['ready', 'working', 'dead'], true)) {
            throw new InvalidArgumentException('Неизвестный список');
        }
        return $this->redis()->lRange($this->key($list), 0, 9);
    }

    public function clear(): void
    {
        $this->redis()->del(array_map($this->key(...), ['ready', 'working', 'delayed', 'dead']));
    }
}
