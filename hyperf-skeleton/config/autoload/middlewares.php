<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

return [
    // 这里的 http 对应默认的 server name，如您需要在其它 server 上使用 Session，需要对应的配置全局中间件
    'http' => [
        \Hyperf\Session\Middleware\SessionMiddleware::class,
        \App\Middleware\CorsMiddleware::class,
        \Phper666\JWTAuth\Middleware\JWTAuthMiddleware::class,
    ],
];