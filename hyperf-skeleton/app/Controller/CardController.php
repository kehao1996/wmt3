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

use App\Drive\Pdo;
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
 * @Controller(prefix = "card")
 */
class CardController extends ApiController
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
     * 获取集卡的用户基本信息
     * 域名 /card/getUserInfo
     *
     * @return string json
     *
     * <pre>
     * {
     * Status 200
     * Msg : 获取成功
     * Data :
     * UserInfo  //个人信息
     * DrawCount //自己剩余抽奖次数
     * CardList //自己所拥有的卡片数量  跟后台的card_prize返回一样多了一个Count字段当前拥有的数量
     * HelpCount//当前助力人数
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
        $user_draw = $dbUser->getUserCardDraw($userid);

        $container = ApplicationContext::getContainer();
        $redis = $container->get(RedisFactory::class)->get('default');
        $data = $redis->get($this->config_key);
        $data = unserialize($data);
        $card_prize = $data['card_prize'];
        $card_prize = json_decode($card_prize,true);
        foreach($card_prize as $k=>$v){
            $prize_count = $dbUser->getUserCardCount($userid,$k);
            $card_prize[$k]['Count'] = empty($prize_count) ? 0 : $prize_count;
        }


        $help_count = $dbUser->getUserHelpCount($userid);
        return [
            'Status' => 200,
            'Data' => [
                'HelpCount' => intval($help_count),
                'CardList' => $card_prize,
                'UserInfo' => $userinfo,
                'DrawCount' => intval($user_draw)
            ],
            'Msg' => '获取成功'
        ];
    }


    /**
     * 获取集卡活动基本配置
     * 域名 /card/getConfig
     *
     * @return string json
     *
     * <pre>
     * Status 200
     * Data :
     * DrawZonNum //参与总人数
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


        $_draw_h_count = date('H') * 20000;
        $_draw_i_count = date('i') * 1000;
        $_draw_s_count = date('s') * 10;

        return [
            'Status' => 200,
            'Data' => [
                'DrawZonNum' =>  intval($_draw_h_count) + intval($_draw_i_count) + intval($_draw_s_count) + $draw_count,
                'Wxh' => $data['wxh'],
            ],
            'Msg' => '获取成功'
        ];
    }


    /**
     * 抽奖
     * 域名 /card/draw
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
        $data['card_prize'] = json_decode($data['card_prize'], true);

        $prize = $data['card_prize'];

        $dbUser = new User();

        $draw_price = [];
        $rate = 0;
        foreach ($prize as $k => $_v) {
            if(!empty($_v['Rate'])){
                $draw_price[$k] = $_v['Rate'] * 0.01;
                $rate+=$draw_price[$k];
            }

        }

        if (empty($draw_price)) {
            return [
                'Status' => 201,
                'Msg' => '奖品未配置'
            ];
        }



        $draw_user_count = $dbUser->getUserCardDraw($userid);//可以抽奖次数
        if(!$draw_user_count){
            return [
                'Status' => 201,
                'Data' => [
                    'count' => $draw_user_count
                ],
                'Msg' => '可抽奖次数不足'
            ];
        }

        $dbUser->decUserCardDraw($userid,1);
        $index = getPrize($draw_price,$rate);
        $prize_info = $prize[$index];
        if(!empty($prize_info)){ //中奖
            $dbUser->incUserCardCount($userid,$index,1);
            return [
                'Status' => 200,
                'Data' => [
                    'Index' => intval($index),
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


    /**
     * 增加抽奖机会  /Draw/incChanceCount
     *
     * @RequestMapping(path="incChanceCount",methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function incChanceCount(){
        $parse_data = $this->jwt->getParserData();
        $userid = $parse_data['uid'];
        $dbUser = new User();
        $dbUser->incChanceCount($userid,1);
        return [
            'Status' => 200
        ];
    }


    /**
     * 增加皮肤碎片机会  /Draw/incSkinDebris
     *
     * @RequestMapping(path="incSkinDebris",methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function incSkinDebris(){
        $parse_data = $this->jwt->getParserData();
        $userid = $parse_data['uid'];
        $dbUser = new User();


        $container = ApplicationContext::getContainer();

// 通过 DI 容器获取或直接注入 RedisFactory 类
        $redis = $container->get(RedisFactory::class)->get('default');
        $data = $redis->get($this->config_key);
        $data = unserialize($data);
        $skin_debris_count = empty($data['skin_debris_count']) ? 1 : $data['skin_debris_count'];
        $dbUser->incSkinDebrisCount($userid,$skin_debris_count);

        return [
            'Status' => 200
        ];
    }


    /**
     * 增加视频次数  /Draw/incVideo
     *
     * @RequestMapping(path="incVideo",methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function incVideo(){
        $parse_data = $this->jwt->getParserData();
        $userid = $parse_data['uid'];
        $dbUser = new User();
        $dbUser->incVideoCount($userid,1);

        return [
            'Status' => 200
        ];
    }



}
