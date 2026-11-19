<?php
declare(strict_types=1);

final class Cache
{
    private static ?Redis $client = null;
    private static bool $failed = false;
    private static string $last = 'BYPASS';

    private static function options(): array
    {
        static $options;
        return $options ??= (require dirname(__DIR__, 2) . '/config/app.php')['cache'];
    }

    public static function redis(): ?Redis
    {
        $options = self::options();
        if (!$options['enabled'] || self::$failed || !class_exists('Redis')) {
            return null;
        }
        if (self::$client !== null) {
            return self::$client;
        }
        try {
            $client = new Redis();
            if (!$client->connect($options['host'], $options['port'], 0.25, null, 0, 0.25)
                || !$client->setOption(Redis::OPT_PREFIX, $options['prefix'])
                || !$client->ping()) {
                throw new RuntimeException('Redis не подтвердил подключение');
            }
            return self::$client = $client;
        } catch (Throwable $error) {
            self::unavailable($error);
            return null;
        }
    }

    private static function unavailable(Throwable $error): void
    {
        self::$failed = true;
        error_log('Redis: ' . $error->getMessage());
    }

    public static function status(): string
    {
        return self::$last;
    }

    public static function remember(string|Closure $key, int $ttl, callable $loader): mixed
    {
        self::$last = 'BYPASS';
        if (self::options()['debug'] && ($_SERVER['HTTP_X_CACHE_MODE'] ?? '') === 'off') {
            return $loader();
        }
        $client = self::redis();
        if ($client === null) {
            return $loader();
        }
        $key = $key instanceof Closure ? $key() : $key;
        try {
            $raw = $client->get($key);
            if ($raw !== false) {
                $value = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                self::$last = 'HIT';
                return $value;
            }
        } catch (JsonException $error) {
            error_log('Повреждённое значение Redis: ' . $key);
        } catch (Throwable $error) {
            self::unavailable($error);
            return $loader();
        }

        $value = $loader();
        try {
            $json = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $lifetime = $ttl + random_int(0, intdiv($ttl * 15, 100));
            if ($client->setex($key, $lifetime, $json)) {
                self::$last = 'MISS';
            }
        } catch (Throwable $error) {
            self::unavailable($error);
        }
        return $value;
    }

    public static function clear(): bool
    {
        $client = self::redis();
        if ($client === null) {
            return false;
        }
        // SCAN returns full keys. Disable automatic prefixing during deletion.
        try {
            $prefix = self::options()['prefix'];
            $pattern = addcslashes($prefix, '\\*?[]') . '*';
            $client->setOption(Redis::OPT_PREFIX, '');
            $cursor = null;
            do {
                $keys = $client->scan($cursor, $pattern, 100);
                if ($keys) {
                    $client->del($keys);
                }
            } while ((int) $cursor !== 0);
            return true;
        } catch (Throwable $error) {
            self::unavailable($error);
            return false;
        } finally {
            $client->setOption(Redis::OPT_PREFIX, self::options()['prefix']);
        }
    }
}
