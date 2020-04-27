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
use Hyperf\Utils\ApplicationContext;
use Hyperf\Redis\RedisFactory;
/**
 * @Controller(prefix = "admin")
 */
class IndexController extends ApiController
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

        $this->session->set($this->admin_key,1);
        $sessionid = $this->session->getId();

        return [
            'Status' => 200,
            'Msg' => '登录成功',
            'Phpesessid' => $sessionid
        ];
    }

    /**
     * @RequestMapping(path="test", methods="get,post")
     */
    public function test(RequestInterface $request){
        if(!$this->session->get($this->admin_key)){
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

    /**
     *  域名 /admin/setConfig
     *
     *
     *  wxh //客服微信号
     *  prize //奖品 自定义格式：[{"Name": "奖品1","Rate": 0.1,"Desc": "奖品描述","Icon": "图片"},{"Name": "奖品2","Rate": 0.9,"Desc": "奖品描述","Icon": "图片"}]
     *  share_status //1开启0关闭  开启后用户抽奖机会用完提示分享 0 关闭后不能分享
     */

    /**
     * @RequestMapping(path="setConfig", methods="post")
     */
    public function setConfig(RequestInterface $request){
        if(!$this->session->get($this->admin_key)){
            return [
                'Status' => 403,
                'Msg' => '未登入'
            ];
        }

        $data['wxh'] = $request->input('wxh','');
        $data['prize'] = $request->input('prize','');
        $data['share_status'] = $request->input('share_status',0);



        $container = ApplicationContext::getContainer();

// 通过 DI 容器获取或直接注入 RedisFactory 类
        $redis = $container->get(RedisFactory::class)->get('default');

        $data = serialize($data);
        $redis->set($this->config_key,$data,864000); //缓存10天

        return [
            'Status' => 200,
            'Msg' => 'ok'
        ];
    }

    /**
     * @RequestMapping(path="getConfig", methods="post")
     */
    public function getConfig(RequestInterface $request){
        if(!$this->session->get($this->admin_key)){
            return [
                'Status' => 403,
                'Msg' => '未登入'
            ];
        }

        $container = ApplicationContext::getContainer();

// 通过 DI 容器获取或直接注入 RedisFactory 类
        $redis = $container->get(RedisFactory::class)->get('default');
        $data = $redis->get($this->config_key);
        if(!$data){
            return [
                'Status' => 200,
                'Data' => [

                ]
            ];
        }

         $data = unserialize($data);
         $data['prize'] = json_decode($data['prize'],true);


        return [
            'Status' => 200,
            'Data' => $data
        ];
    }


}
