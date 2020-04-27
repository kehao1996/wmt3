<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/27
 * Time: 14:40
 */


/**
 * 按照概率随机算出奖品
 * @param array $data  // [1 => 0.5,2 => 0.3,3 => 0.2]
 * @return bool|int|string
 * @author liuweiping
 */
function getPrize($data = [])
{
    $r = mt_rand(0, 1000) * 0.001;
    $t_r = 0;//累计求和
    $prize = false;
    foreach ($data as $key => $val) {
        $t_r += $val;
        if ($r <= $t_r) {
            $prize = $key;
            break;
        }
    }
    return $prize;
}

function getSession($key)
{

    if (isset($_SESSION[$key])) {
        return $_SESSION[$key];
    }
    return false;
}

function setSession($key, $val)
{

    $_SESSION[$key] = $val;
    return true;
}