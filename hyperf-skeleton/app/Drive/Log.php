<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/12
 * Time: 16:37
 */
namespace App\Drive;
use Hyperf\Logger\Logger;
use Hyperf\Utils\ApplicationContext;


class Log {


    public static function get(string $name = 'app')
    {
        return ApplicationContext::getContainer()->get(\Hyperf\Logger\LoggerFactory::class)->get($name);
    }
}