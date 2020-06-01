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
     * 新增2020.6.1
     * SkinDebrisCount //碎片个数
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

        $draw_count = $dbUser->getChanceCount($userid);
        $skin_debris_count = $dbUser->getSkinDebrisCount($userid);

        return [
            'Status' => 200,
            'Data' => [
                'SkinDebrisCount' => empty($skin_debris_count) ? 0 : $skin_debris_count,
                'UserInfo' => $userinfo,
                'DrawCount' => intval($draw_count)
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
                'Prize' => $data['prize'],
                'DrawDayCount' => $data['draw_day_count'],
                'DrawDayNum' => $data['draw_day_num'],
                'SyDrawCount' => $sy_draw_cont,
                'DrawZonNum' =>  intval($_draw_h_count) + intval($_draw_i_count) + intval($_draw_s_count) + $draw_count,
                'Wxh' => $data['wxh'],
                'DrawDesc' => $data['draw_desc'],
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

        $dbUser = new User();

        $draw_price = [];
        $rate = 0;
        foreach ($prize as $k => $_v) {
            if(!empty($_v['Rate'])){
                $_prize_count = $dbUser->returnUserPrizeCount($userid,$k); //奖品数量
                if($_prize_count < $_v['Count']){ //还没达到上限
                    $draw_price[$k] = $_v['Rate'] * 0.01;
                    $rate+=$draw_price[$k];
                }
            }

        }

        if (empty($draw_price)) {
            return [
                'Status' => 201,
                'Msg' => '奖品未配置'
            ];
        }


        $draw_count = $dbUser->returnUserDraw();
        $draw_count = count($draw_count);
        if($draw_count >= $data['draw_day_num']) { //今日抽奖人数已满
            if(!$dbUser->isUserDraw($userid)){
                return [
                    'Status' => 201,
                    'Data' => null,
                    'Msg' => '今日抽奖人数已满,请明天来'
                ];
            }
        }


        $draw_user_count = $dbUser->getChanceCount($userid);//可以抽奖次数
        if(!$draw_user_count){
            return [
                'Status' => 201,
                'Data' => [
                    'count' => $draw_user_count
                ],
                'Msg' => '可抽奖次数不足,请明天来'
            ];
        }
        $dbUser->decChanceCount($userid,1);
        $dbUser->addUserDraw($userid); //增加用户到抽奖池
        $dbUser->setUserDraw($userid,1); //今日抽奖次数 + 1

        $index = getPrize($draw_price,$rate);
        $prize_info = $prize[$index];
        if(!empty($prize_info)){ //中奖
            if($prize_info['Skin_Status'] == 1){
                $count = mt_rand($prize_info['Min'],$prize_info['Max']);
                $dbUser->incSkinDebrisCount($userid,$count);
            }
            $dbUser->setUserPrizeCount($userid,$index,1); //中奖次数+1
            $dbPrizeLog = new PrizeLog();
            $dbPrizeLog->add([
                'userid' => $userid,
                'createtime' => date('Y-m-d H:i:s'),
                'prizeindex' => $index
            ]);
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
     * 获取中奖记录
     * 域名 /user/getPrizeLog
     * <pre>
     * POST
     * </pre>
     *
     * @return string json
     *
     * <pre>
     * Status 200
     * Data :
     * Result: //自己的中奖记录
     * nickname //名称
     * headimg //头像
     * createtime //中将时间
     * prize_info //奖品信息
     *
     * Msg
     * </pre>
     *
     *
     * @RequestMapping(path="getPrizeLog",methods="post,options")
     * @Middleware(JWTAuthMiddleware::class)
     */
    public function getPrizeLog(){
        $parse_data = $this->jwt->getParserData();
        $userid = $parse_data['uid'];
        $pdo = new Pdo();
        $prize_log = $pdo->clear()->select('*')->from('prize_log')->where([
            'userid' => $userid,
            'createtime >=' => date('Y-m-d') .' 00:00:00',
            'createtime <=' => date('Y-m-d') . ' 23:59:59'
        ])->limit(100)->order('id desc')->getAll();

        $dbUser = new User();

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
        foreach($prize_log as $k=>$v){
            $userinfo = $dbUser->get($v['userid']);
            $prize_log[$k]['nickname'] = $userinfo['nickname'];
            $prize_log[$k]['headimg'] = $userinfo['headimg'];
            $prize_log[$k]['prize_info'] = $prize[$v['prizeindex']];
            unset($prize_log[$k]['prize_info']['Rate']);
        }

        return [
            'Status' => 200,
            'Data' => [
                'Result' => $prize_log
            ],
            'Msg' => '获取成功'
        ];
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



}
