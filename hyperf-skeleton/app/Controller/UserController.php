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

use App\Model\User;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Redis\RedisFactory;
use EasyWeChat\Factory;
use \Phper666\JWTAuth\JWT;
use \Phper666\JWTAuth\Middleware\JWTAuthMiddleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\Middleware;


/**
 * @Controller(prefix = "user")
 */
class UserController extends ApiController
{

    /**
     * @Inject()
     * @var JWT
     */
    private $jwt;

    /**
     * @Inject()
     * @var \Hyperf\Contract\SessionInterface
     */
    private $session;


    /**
     * 登录
     * 域名: /user/login
     * POST
     * @param string js_code //小程序code
     *
     * @return string json
     *
     * <pre>
     * Status 200 //成功
     * Status 201 //失败
     *
     * @RequestMapping(path="login", methods="post,options")
     */
    public function login(RequestInterface $request)
    {

        $js_code = $request->input('js_code', '');
        if (empty($js_code)) {
            return [
                'Status' => 201,
                'Msg' => '参数异常 js_code'
            ];
        }

        try {

            $config = [
                'app_id' => 'wx395497f7015b6ef2',
                'secret' => 'f317b9fcc7e42ccc4587ae281b72d386',

                // 下面为可选项
                // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
                'response_type' => 'array',
                'log' => [
                    'level' => 'debug',
                    'file' => __DIR__ . '/wechat.log',
                ],
            ];

            $app = Factory::miniProgram($config);
            $result = $app->auth->session($js_code);

            if (empty($result['openid'])) {
                return [
                    'Status' => 201,
                    'Msg' => 'openid 异常'
                ];
            }

            $openid = $result['openid'];
            $dbUser = new User();
            $userid = $dbUser->getidByOpenid($openid);
            if (!$userid) { //不存在添加
                $data = [
                    'openid' => $openid,
                    'createtime' => date('Y-m-d H:i:s')
                ];
                $status = $dbUser->add($data);
                if(!$status){
                    return [
                        'Status' => 201,
                        'Msg' => '添加失败'
                    ];
                }
            }

            $userid = $dbUser->getidByOpenid($openid);
            if($userid){
                $userData = [
                    'uid' => $userid,
                    'openid' => $openid
                ];

                $token = $this->jwt->setScene('default')->getToken($userData);
                $data = [
                    'Status' => 200,
                    'Msg' => '登录成功',
                    'Data' => [
                        'token' => $token,
                        'exp' => $this->jwt->getTTL(),
                    ]
                ];
                return $data;
            }

            return [
                'Status' => 201,
                'Msg' => 'error'
            ];


        } catch (\Exception $e) {
            return [
                'Status' => 201,
                'Msg' => $e->getMessage()
            ];
        }
    }


    /**
     * 获取token
     *
     * <pre>
     * POST
     * openid
     * </pre>
     *
     * @RequestMapping(path="getToken",methods="post,options")
     */
    public function getToken(RequestInterface $request){
        $openid = $request->input('openid','');
        $dbUser = new User();
        $userid = $dbUser->getidByOpenid($openid);
        if($userid){
            $userData = [
                'uid' => $userid,
                'openid' => $openid
            ];

            $token = $this->jwt->setScene('default')->getToken($userData);
            $data = [
                'Status' => 200,
                'Msg' => '登录成功',
                'Data' => [
                    'token' => $token,
                    'exp' => $this->jwt->getTTL(),
                ]
            ];
            return $data;
        }
    }


    /**
     * 修改个人信息
     * 域名:/user/updateInfo
     * headimg //头像
     * nickname //名称
     * sex //性别
     *
     * @RequestMapping(path="updateInfo", methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function updateInfo(RequestInterface $request){

        $parser_data = $this->jwt->getParserData();
        $userid = $parser_data['uid'];

        $data['headimg'] = $request->input('headimg','');
        $data['nickname'] = $request->input('nickname','');
        $data['sex'] = $request->input('sex',0);

        $dbUser = new User();
        $dbUser->edit($data,$userid);

        return [
            'Status' => 200,
            'Msg' => 'ok'
        ];
    }


    /**
     * 获取用户信息
     * 域名 /user/getUserInfo
     *
     * @return string json
     *
     * <pre>
     * {
     * Status 200
     * Msg : 获取成功
     * Data : 个人信息
     * }
     * </pre>
     *
     *
     * @RequestMapping(path="getUserInfo",methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function getUserInfo(){
        $pare_data = $this->jwt->getParserData();
        $userid = $pare_data['uid'];
        $dbUser = new User();
        $userinfo = $dbUser->get($userid);

        return [
            'Status' => 200,
            'Data' => $userinfo,
            'Msg' => '获取成功'
        ];
    }


    /**
     * 获取基本配置
     * 域名 /user/getConfig
     *
     * @RequestMapping(path="getConfig", methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function getConfig(RequestInterface $request)
    {

        $container = ApplicationContext::getContainer();

// 通过 DI 容器获取或直接注入 RedisFactory 类
        $redis = $container->get(RedisFactory::class)->get('default');
        $data = $redis->get($this->config_key);
        if (!$data) {
            return [
                'Status' => 200,
                'Data' => [

                ]
            ];
        }

        $data = unserialize($data);
        $data['prize'] = json_decode($data['prize'], true);


        return [
            'Status' => 200,
            'Data' => $data
        ];
    }


    /**
     * 抽奖
     * 域名 /user/draw
     *
     * @return string json
     *
     * <pre>
     * Status 200 异常201
     * Index  0 //中将下标
     * Prize [] //奖品信息
     * </pre>
     *
     * @RequestMapping(path="draw", methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function draw()
    {

        $parser_data = $this->jwt->getParserData();
        $userid = $parser_data['uid'];

        $container = ApplicationContext::getContainer();

// 通过 DI 容器获取或直接注入 RedisFactory 类
        $redis = $container->get(RedisFactory::class)->get('default');
        $data = $redis->get($this->config_key);
        if (!$data) {
            return [
                'Status' => 201,
                'Msg' => '未配置参数'
            ];
        }

        $data = unserialize($data);
        $data['prize'] = json_decode($data['prize'], true);

        $prize = $data['prize'];


        $draw_price = [];
        foreach ($prize as $k => $_v) {
            $draw_price[$k] = $_v['Rate'] * 0.01;
        }

        if (empty($draw_price)) {
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
