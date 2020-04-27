#!/usr/bin/env php
<?php

//error_reporting(0);
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

require BASE_PATH . '/vendor/autoload.php';

require BASE_PATH . '/app/Common/function.php';

header('Access-Control-Allow-Origin: *');
// 响应类型

// 带 cookie 的跨域访问
header('Access-Control-Allow-Credentials: true');
// 响应头设置
header('Access-Control-Allow-Headers:x-requested-with,Content-Type,X-CSRF-Token');


if (isset($_REQUEST['phpsessid']) && !empty($_REQUEST['phpsessid'])) {
    ini_set('session.gc_maxlifetime', "3600"); // 秒
    session_id($_REQUEST['phpsessid']);
    session_start();
}

// Self-called anonymous function that creates its own scope and keep the global namespace clean.
(function () {
    /** @var \Psr\Container\ContainerInterface $container */
    $container = require BASE_PATH . '/config/container.php';

    $application = $container->get(\Hyperf\Contract\ApplicationInterface::class);
    $application->run();
})();
