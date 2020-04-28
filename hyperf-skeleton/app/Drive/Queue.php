<?php

/**
 * 队列控制类
 * @author 苏宁 <snsnsky@gmail.com>
 * $Id$
 */
namespace App\Drive;

class Queue
{

    /**
     * 向队列中添加一条数据
     *
     * @param string       $name    队列名
     * @param string|array $data    队列数据
     * @return boolean
     */
    public static function add($name, $data)
    {
        if (empty($name)) {
            return false;
        }
        $data = is_string($data) ? trim($data) : serialize($data);
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        $result = $redis->rPush($name, $data);

        if (!$result) {
            $logs = [
                'status' => 'fail',
                'name' => $name,
                'data' => $data,
                'time' => date("Y-m-d H:i:s")
            ];
//            runtime_log("Queue/{$name}_Fail", $logs);

            return false;
        }

        return true;
    }

    public static function del($name)
    {
        if (empty($name)) {
            return false;
        }
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        $redis->del($name);

        return true;
    }

    public static function getLen($name)
    {
        if (empty($name)) {
            return false;
        }
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        $result = $redis->lLen($name);
        return $result;
    }

    public static function get($name)
    {
        if (empty($name)) {
            return false;
        }
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        $result = $redis->lGet($name, 0);
        if (!$result) {
            return false;
        }
        return $result;
    }

    public static function getAll($name)
    {
        if (empty($name)) {
            return false;
        }
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        $result = $redis->lRange($name, 0, -1);
        if (!$result) {
            return false;
        }
        return $result;
    }

    public static function removeItem($name, $value)
    {
        if (empty($name)) {
            return false;
        }
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        $count = $redis->lRem($name, $value, 1);
        return $count;
    }

    /**
     * 向队列中添加多条数据
     *
     * @param string       $name    队列名
     * @param string|array $data    队列数据
     * @return boolean
     */
    public static function addMulti($name, $data)
    {
        if (empty($name)) {
            return false;
        }
        $data = is_string($data) ? [trim($data)] : (array)($data);
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        foreach ($data as $k => $val) {
            $data[$k] = is_string($val) ? trim($val) : serialize($val);
        }
        array_unshift($data, $name);
        $result = call_user_func_array([$redis, 'rPush'], $data);
        if (!$result) {
            $logs = [
                'status' => 'fail',
                'name' => $name,
                'data' => $data,
                'time' => date("Y-m-d H:i:s")
            ];
//            runtime_log("Queue/{$name}_Fail", $logs);

            return false;
        }

        return true;
    }

    /**
     * 向队列中添加多条数据［实际是按单条进行添加］
     *
     * @param string $name    队列名
     * @param array $data     队列数据数组
     * @return boolean
     */
    public static function addMultiByForeach($name, array $data)
    {
        if (empty($name) || empty($data)) {
            return false;
        }
        $pured = [];
        $result = false;
        foreach ($data as $item) {
            $pured[] = is_string($item) ? trim($item) : serialize($item);
        }
        $chunks = array_chunk($pured, 100);
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        foreach ($chunks as $chunk) {
            array_unshift($chunk, $name);

            $result |= call_user_func_array([$redis, 'rPush'], $chunk);
        }
        if (!$result) {
            $logs = [
                'status' => 'fail',
                'name' => $name,
                'data' => $data,
                'time' => date("Y-m-d H:i:s")
            ];
//            runtime_log("Queue/{$name}_Fail", $logs);

            return false;
        }

        return true;
    }

    /**
     * 取队列状态
     *
     * @param string       $name    队列名
     * @return array
     */
    public static function getQueueStatus($name)
    {
        if (empty($name)) {
            return false;
        }
        static $redis = null;
        if ($redis === null) {
            $_redis = new Redis();
            $redis = $_redis->redis;
        }
        $result = [];
        $result['unread'] = $redis->lLen($name);

        return json_encode($result);
    }
}
