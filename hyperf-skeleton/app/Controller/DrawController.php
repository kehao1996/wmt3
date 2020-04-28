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

use App\Model\PrizeLog;
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
 * @Controller(prefix = "draw")
 */
class DrawController extends ApiController
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
     * 获取抽奖的用户基本信息
     * 域名 /draw/getUserInfo
     *
     * @return string json
     *
     * <pre>
     * {
     * Status 200
     * Msg : 获取成功
     * Data :
     * UserInfo  //个人信息
     * DrawCount //自己今天剩余抽奖次数
     *
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
        $user_draw = $dbUser->getUserDraw($userid);



        $container = ApplicationContext::getContainer();
        $redis = $container->get(RedisFactory::class)->get('default');
        $data = $redis->get($this->config_key);
        $data = unserialize($data);
        $draw_day_count = !empty($data['draw_day_count']) ?$data['draw_day_count'] : 0;


        //每天抽奖次数 - 已经抽奖次数 = 还剩抽奖次数
        $draw_count = $draw_day_count - $user_draw;
        $draw_count = !empty($draw_count) ? $draw_count : 0;


        return [
            'Status' => 200,
            'Data' => [
                'UserInfo' => $userinfo,
                'DrawCount' => $draw_count
            ],
            'Msg' => '获取成功'
        ];
    }


    /**
     * 获取抽奖活动基本配置
     * 域名 /draw/getConfig
     *
     * @return string json
     *
     * <pre>
     * Status 200
     * Data :
     * Prize //奖品信息
     * DrawDayCount //每日抽奖次数
     * DrawDayNum //每日抽奖人数
     * SyDrawCount //剩余可抽奖人数
     *
     * Msg
     * </pre>
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

        $dbUser = new User();
        $user_list = $dbUser->returnUserDraw();
        $draw_count = count($user_list);

        $sy_draw_cont = $data['draw_day_num'] - $draw_count;
        $sy_draw_cont = empty($sy_draw_cont) ? 0 : $sy_draw_cont;


        return [
            'Status' => 200,
            'Data' => [
                'Prize' => $data['prize'],
                'DrawDayCount' => $data['draw_day_count'],
                'DrawDayNum' => $data['draw_day_num'],
                'SyDrawCount' => $sy_draw_cont
            ],
            'Msg' => '获取成功'
        ];
    }


    /**
     * 抽奖
     * 域名 /draw/draw
     *
     * @return string json
     *
     * <pre>
     * Status 200 异常201
     * Data:
     * Index  0 //中将下标
     * Prize [] //奖品信息
     *
     * Msg
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

        $dbUser = new User();
        $draw_count = $dbUser->returnUserDraw();
        if($draw_count >= $data['draw_day_num']) { //今日抽奖人数已满
            if(!$dbUser->isUserDraw($userid)){
                return [
                    'Status' => 201,
                    'Data' => null,
                    'Msg' => '今日抽奖人数已满,请明天来'
                ];
            }
        }


        $draw_user_count = $dbUser->getUserDraw($userid);
        $k_draw_count = $data['draw_day_count'] - $draw_user_count ; //可以抽奖次数
        if(!$k_draw_count){
            return [
                'Status' => 201,
                'Data' => [
                    'count' => $k_draw_count
                ],
                'Msg' => '可抽奖次数不足,请明天来'
            ];
        }

        $dbUser->addUserDraw($userid); //增加用户到抽奖池
        $dbUser->setUserDraw($userid,1); //今日抽奖次数 + 1

        $index = getPrize($draw_price);

        $prize_info = $prize[$index];
        if(!empty($prize_info)){ //中奖
            $dbPrizeLog = new PrizeLog();
            $dbPrizeLog->add([
                'userid' => $userid,
                'createtime' => date('Y-m-d H:i:s'),
                'prizeindex' => $index
            ]);
            return [
                'Status' => 200,
                'Data' => [
                    'Index' => $index,
                    'Prize' => $prize[$index]
                ],
                'Msg' => '获取成功'
            ];
        }else{
            return [
                'Status' => 201,
                'Data' => [

                ],
                'Msg' => '异常'
            ];
        }
    }


}
