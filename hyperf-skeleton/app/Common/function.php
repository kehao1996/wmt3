<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/27
 * Time: 14:40
 */


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