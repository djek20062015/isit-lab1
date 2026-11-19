<?php
declare(strict_types=1);

return [
    'cache' => [
        'enabled' => getenv('CACHE_ENABLED') !== '0',
        'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('REDIS_PORT') ?: 6380),
        'prefix' => getenv('REDIS_PREFIX') ?: 'eventapp11:cache:',
        'debug' => getenv('APP_DEBUG') === '1',
    ],
];
