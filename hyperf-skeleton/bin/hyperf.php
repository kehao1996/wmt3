#!/usr/bin/env php
<?php

//error_reporting(0);
ini_set('display_errors', 'off');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

require BASE_PATH . '/vendor/autoload.php';

require BASE_PATH . '/app/Common/function.php';

// Self-called anonymous function that creates its own scope and keep the global namespace clean.
(function () {
    /** @var \Psr\Container\ContainerInterface $container */
    $container = require BASE_PATH . '/config/container.php';

    $application = $container->get(\Hyperf\Contract\ApplicationInterface::class);
    $application->run();
})();
