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

use App\Drive\Pdo;
use App\Model\User;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Redis\RedisFactory;
use \Phper666\JWTAuth\JWT;
use \Phper666\JWTAuth\Middleware\JWTAuthMiddleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\Middleware;
use App\Middleware\CorsMiddleware;



/**
 * @Controller(prefix = "admin")
 */
class IndexController extends ApiController
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

        $userData = [
            'uid' => 1, // 如果使用单点登录，必须存在配置文件中的sso_key的值，一般设置为用户的id
            'username' => 'admin',
        ];
        // 使用默认场景登录
        $token = $this->jwt->setScene('default')->getToken($userData);

        $data = [
            'Status' => 200,
            'msg' => '登录成功',
            'Data' => [
                'token' => $token,
                'exp' => $this->jwt->getTTL(),
            ]
        ];

        return $data;

    }

    /**
     * @RequestMapping(path="test", methods="get,post,options")
     */
    public function test(RequestInterface $request){
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
     *
     *
     *  share_status //1开启0关闭  开启后用户抽奖机会用完提示分享 0 关闭后不能分享
     *  draw_day_count //每天抽奖次数
     *  draw_day_num //每天抽奖人数
     *  draw_desc //抽奖描述
     *
     *
     *
     *  新增 2020.5.15
     *  other_config //json 字符 前端随意发挥 返回接口会解析json返回数组给前段
     *  新增2020.6.1
     *  prize json格式中新增 Skin_Status //是否是皮肤碎片1是皮肤碎片0不是皮肤碎片 //Min 最小值 //Max 最大值
     *  新增2020.6.18
     *  card_prize //集卡奖品 自定义格式：[{"Name": "奖品1","Rate": 0.1,"Desc": "奖品描述","Icon": "图片"},{"Name": "奖品2","Rate": 0.9,"Desc": "奖品描述","Icon": "图片"}]
     *
     *
     */

    /**
     * @RequestMapping(path="setConfig", methods="post,options")
     * @Middlewares({
     *     @Middleware(JWTAuthMiddleware::class)
     * })
     */
    public function setConfig(RequestInterface $request){
        $data['wxh'] = $request->input('wxh','');
        $data['prize'] = $request->input('prize','');
        $data['share_status'] = $request->input('share_status',0);
        $data['draw_day_count'] = $request->input('draw_day_count',0);
        $data['draw_day_num'] = $request->input('draw_day_num',0);
        $data['draw_desc'] = $request->input('draw_desc','');
        $data['other_config'] = $request->input('other_config','');
        $data['skin_debris_count'] = $request->input('skin_debris_count',1);
        $data['yq_skin_cunt'] = $request->input('yq_skin_cunt',1);
        $data['card_prize'] = $request->input('card_prize','');

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
     * 域名:/admin/getConfig
     */

    /**
     * @RequestMapping(path="getConfig", methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function getConfig(RequestInterface $request){

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
         $data['other_config'] = json_decode($data['other_config'],true);
         $data['card_prize'] = json_decode($data['card_prize'],true);


        return [
            'Status' => 200,
            'Data' => $data
        ];
    }

    /**
     * 域名 /admin/loginout
     *
     * 退出
     *
     */

    /**
     * @RequestMapping(path="loginOut", methods="get,post,options")
     */
    public function loginOut(){
        $this->jwt->logout();
    }




    public function checkLogin(){
        $data = [
            'Status' => 200,
            'Msg' => 'success',
            'Data' => $this->jwt->getParserData()
        ];
        return $data;

    }

    /**
     * 获取所有用户信息 /admin/getUserList
     * <pre>
     * POST
     * pageindex //页码大小
     * pagesize //页尺寸
     * </pre>
     *
     * @RequestMapping(path="getUserList", methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function getUserList(RequestInterface $request)
    {
        $pageindex = $request->input('pageindex',1);
        $pagesize = $request->input('pagesize',20);

        $pdo = new Pdo();
        $total = $pdo->clear()->select('count(id)')->from('user')->getValue();
        $list = $pdo->clear()->select('*')->from('user')->limit(($pageindex - 1) * $pagesize,$pagesize)->getAll();

        $data = [
            'Status' => 200,
            'Msg' => 'success',
            'Data' => [
                'Total' => $total,
                'List' => $list
            ]
        ];
        return $data;
    }

    /**
     * 增加集卡抽奖机会 /admin/addCardDarw
     * <pre>
     * POST
     * userid //用户主键id
     * count //抽奖机会
     * </pre>
     *
     * @RequestMapping(path="addCardDarw", methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function addCardDarw(RequestInterface $request){
        $userid = $request->input('userid',0);
        $count = $request->input('count',0);

        $dbUser = new User();
        $dbUser->incUserCardDraw($userid,$count);
        return [
            'Status' => 200,
            'Msg' => '增加成功',
            'Data' => []
        ];
    }

}
