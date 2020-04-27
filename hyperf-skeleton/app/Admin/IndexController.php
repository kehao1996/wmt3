<?php


/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Admin;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;


/**
 * @Controller(prefix = "admin")
 */
class IndexController
{

    /**
     * 域名: /admin/login
     * POST
     * username //用户名
     * password //密码
     *
     * @return string json
     *
     * <pre>
     * Status 200 //成功
     * Status 201 //失败
     * </pre>
     */

    /**
     * @RequestMapping(path="index", methods="post")
     */
    public function login(RequestInterface $request)
    {
        $username = $request->input('username','');
        $password = $request->input('password','');

        if(empty($username)){
            return [
                'Status' => 201,
                'Msg' => '用户名不能为空'
            ];
        }
        $method = $request->getMethod();

        return [
            'Status' => 200,
            'message' => ''
        ];
    }
}
