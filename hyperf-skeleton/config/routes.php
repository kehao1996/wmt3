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

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

Router::addRoute(['GET', 'POST', 'Options'], '/admin/login', 'App\Admin\IndexController@login');

Router::addGroup('/admin', function () {
    Router::addRoute(['GET', 'POST', 'Options'], '/checkLogin', 'App\Admin\IndexController@checkLogin');
}, [
    'middleware' => [
        Phper666\JWTAuth\Middleware\JWTAuthMiddleware::class
    ]
]);

