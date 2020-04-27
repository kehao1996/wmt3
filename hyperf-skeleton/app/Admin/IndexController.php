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
use Hyperf\Di\Annotation\Inject;

/**
 * @Controller(prefix = "admin")
 */
class IndexController
{

    /**
     * @Inject()
     * @var \Hyperf\Contract\SessionInterface
     */
    private $session;

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
     * @RequestMapping(path="login", methods="post")
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

        if($username != 'admin' && $password != 'admin100'){
            return [
                'Status' => 201,
                'Msg'=> '账号密码错误'
            ];
        }

        $this->session->set('WMT_ADMIN_ID',1);
        $sessionid = $this->session->getId();

        return [
            'Status' => 200,
            'Msg' => '登录成功',
            'Phpesessid' => $sessionid
        ];
    }

    /**
     * @RequestMapping(path="test", methods="post")
     */
    public function test(RequestInterface $request){
        if($this->mid == 0){
            return [
                'Status' => 403,
                'Msg' => '未登入'
            ];
        }

       return [
           'Status' => 200,
           'Msg' => 'ok'
       ];
    }
}
