<?php


/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Redis\RedisFactory;
use EasyWeChat\Factory;


/**
 * @Controller(prefix = "user")
 */
class UserController extends  ApiController
{

    /**
     * @Inject()
     * @var \Hyperf\Contract\SessionInterface
     */
    private $session;

    /**
     * 域名: /user/login
     * POST
     * jscode //用户名
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

        $js_code = $request->input('js_code','');
        if(empty($js_code)){
            return [
                'Status' => 201,
                'Msg' => '参数异常 js_code'
            ];
        }

        try{


        $config = [
            'app_id' => 'wx395497f7015b6ef2 ',
            'secret' => 'f317b9fcc7e42ccc4587ae281b72d386',

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => __DIR__.'/wechat.log',
            ],
        ];

        $app = Factory::miniProgram($config);
        $result = $app->auth->session($js_code);
        }catch (\Exception $e){
            return [
                'Status' => 201,
                'Msg' => $e->getMessage()
            ];
        }

        return [
            'Status' => 200,
            'Result' => $result
        ];




        $username = $request->input('jscode','');

        if(empty($username)){
            return [
                'Status' => 201,
                'Msg' => '用户名不能为空'
            ];
        }



        $this->session->set($this->user_key,1);
        $sessionid = $this->session->getId();

        return [
            'Status' => 200,
            'Msg' => '登录成功',
            'Phpesessid' => $sessionid
        ];
    }



    /**
     * 域名:/user/getConfig
     */

    /**
     * @RequestMapping(path="getConfig", methods="post")
     */
    public function getConfig(RequestInterface $request){
        if(!$this->session->get($this->user_key)){
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


    /**
     * @RequestMapping(path="draw", methods="post,options")
     */
    public function draw(){
//        if(!$this->session->get($this->user_key)){
//            return [
//                'Status' => 403,
//                'Msg' => '未登入'
//            ];
//        }


        $container = ApplicationContext::getContainer();

// 通过 DI 容器获取或直接注入 RedisFactory 类
        $redis = $container->get(RedisFactory::class)->get('default');
        $data = $redis->get($this->config_key);
        if(!$data){
            return [
                'Status' => 201,
                'Msg' => '未配置参数'
            ];
        }

        $data = unserialize($data);
        $data['prize'] = json_decode($data['prize'],true);

        $prize = $data['prize'];


        $draw_price = [];
        foreach($prize as $k=>$_v ){
            $draw_price[$k] = $_v['Rate'] * 0.01;
        }

        if(empty($draw_price)){
            return [
                'Status' => 201,
                'Msg' => '奖品未配置'
            ];
        }

        $index = getPrize($draw_price);

        return [
            'Status' => 200,
            'Index' => $index,
            'Prize' => $prize[$index]
        ];
    }


}
