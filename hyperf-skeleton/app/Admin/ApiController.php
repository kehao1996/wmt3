<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/27
 * Time: 14:43
 */

namespace App\Admin;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

class ApiController {


    
    protected $mid = 0;



    public function __construct()
    {
        var_dump( getSession('WMT_ADMIN_ID'));

    }
}