<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/12
 * Time: 11:27
 */
namespace App\Drive;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;

class Redis extends ApplicationContext{

    public $redis = null;
    public function __construct ($poolName = 'default'){
        if($this->redis == null){
            $this->redis = self::getContainer()->get(RedisFactory::class)->get($poolName);
        }

    }

    public function get($poolName = 'default'){
        return self::getContainer()->get(RedisFactory::class)->get($poolName);
    }
}