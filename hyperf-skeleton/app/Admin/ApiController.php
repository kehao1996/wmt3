<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/27
 * Time: 14:43
 */

namespace App\Admin;


use Hyperf\Di\Annotation\Inject;
use Phper666\JWTAuth\JWT;


class ApiController {



    protected $config_key = 'WMT_XT_CONFIG';
    protected $admin_key = 'WMT_ADMIN_ID';
    protected $mid = 0;

    public function __construct()
    {
        $jwt = new JWT();
        $data = $jwt->getParserData();
        if($data['uid'] > 0){
            $this->mid = $data['uid'];
        }
    }
}