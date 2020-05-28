<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/13
 * Time: 11:32
 */

namespace App\Model;

use App\Drive\Pdo;
use App\Drive\Redis;
use PhpCsFixer\Fixer\Basic\BracesFixer;

class User
{


    private $pdo = null;
    private $redis = null;
    private $key = 'MTW_USER_';
    private $_table = 'user';
    private $_field = array(
        'nickname' => '', //名称
        'headimg' => '', //头像
        'sex' => 1,  //性别
        'status' => 1, //状态
        'createtime' => '', //创建时间
        'openid' => '' //openid
    );

    private function checkField($data = []): array
    {
        $source = [];

        foreach ($this->_field as $k => $v) {
            if (!is_null($data[$k])) {
                $source[$k] = $data[$k];
            }
        }

        return $source;
    }

    public function __construct()
    {
        $pdo = new Pdo();
        $_redis = new Redis();
        $this->redis = $_redis->redis;
        $this->pdo = $pdo;
    }


    /**
     * 获取单个信息
     */
    public function get($id)
    {

        $key = $this->key . 'INFO:' . $id;

        $list = $this->redis->hGetAll($key);
        if (!$list) {
//            $pdo = new Pdo();
            $list = $this->pdo->clear()->select('*')->from($this->_table)->where([
                'id' => $id
            ])->getOne();
            if ($list) {
                $this->redis->hMset($key, $list);
                $this->redis->expire($key, 864000); //保存10天
            }
        }
        return $list;
    }

    /**
     * 根据openid 获取id
     */
    public function getidByOpenid($openid = '')
    {
        $key = $this->key . 'Openid:' . $openid;
        $id = $this->redis->get($key);
        if (!$id) {
            $id = $this->pdo->clear()->select('id')->from($this->_table)->where([
                'openid' => $openid
            ])->getValue();
            if ($id) {
                $this->redis->set($key, $id, 86400);
            }
        }
        return $id;
    }

    /**
     * 添加用户
     */
    public function add($data): bool
    {
        $source = $this->checkField($data);

        $status = $this->pdo->clear()->insert($this->_table, $source);
        if ($status) {
            return true;
        }
        return false;
    }

    /**
     * 修改用户
     */
    public function edit($data = [], $id = 0): bool
    {

        if (empty($id)) {
            return false;
        }

        $source = $this->checkField($data);
        $status = $this->pdo->clear()->update($this->_table, $source, [
            'id' => $id
        ]);
        if ($status) {
            return true;
        }

        return false;
    }

    /**
     * 删除用户
     */
    public function del($id = 0): bool
    {
        if (empty($id)) {
            return false;
        }

        $status = $this->pdo->clear()->delete($this->_table, [
            'id' => $id
        ]);
        if ($status) {
            return true;
        }
        return false;
    }

    /**
     * 获取当前用户当前抽奖次数
     */
    public function getUserDraw($userid){
        $key = $this->key . 'DrawCount:' . $userid .':' . date('Y-m-d');
        if(!$this->redis->exists($key)){
            return 0;
        }

        return $this->redis->get($key);
    }

    /**
     * 设置当前用户抽奖次数
     */
    public function setUserDraw($userid,$count = 1){
        $count = intval($count);
        $key = $this->key . 'DrawCount:' . $userid .':' . date('Y-m-d');
        $this->redis->incrBy($key,$count);
        $this->redis->expire($key,86400);
        return true;
    }

    /**
     * 减去用户抽奖次数
     */
    public function decUserDraw($userid,$count = 1){
        $count = intval($count);
        $key = $this->key . 'DrawCount:' . $userid .':' . date('Y-m-d');
        $this->redis->decrBy($key,$count);
        $this->redis->expire($key,86400);
        return true;
    }

    /**
     * 获取当前用户当前抽奖次数
     */
    public function getUserYqCount($userid){
        $key = $this->key . 'UserYqCount:' . $userid .':' . date('Y-m-d');
        if(!$this->redis->exists($key)){
            return 0;
        }

        return $this->redis->get($key);
    }

    /**
     * 设置当前用户抽奖次数
     */
    public function setUserYqCount($userid,$count = 1){
        $count = intval($count);
        $key = $this->key . 'UserYqCount:' . $userid .':' . date('Y-m-d');
        $this->redis->incrBy($key,$count);
        $this->redis->expire($key,86400);
        return true;
    }

    /**
     * 设置当前活动抽奖人数
     *
     */
    public function addUserDraw($userid){
        $key = $this->key .'UserDrawList:' . date('Y-m-d');
        $this->redis->sAdd($key,$userid);
        $this->redis->expire($key,86400);
        return true;
    }

    /**
     * 获取当前抽奖人数
     */
    public function returnUserDraw(){
        $key = $this->key .'UserDrawList:' . date('Y-m-d');
        return $this->redis->sMembers($key);
    }

    /**
     * 判断自己今天是否抽奖了
     */
    public function isUserDraw($userid){
        $key = $this->key .'UserDrawList:' . date('Y-m-d');
        if($this->redis->sIsMember($key,$userid)){
            return true;
        }
        return false;
    }

    /**
     * 设置某个用户中奖次数
     *
     * <pre>
     * POST
     *
     * </pre>
     */

    public function setUserPrizeCount($userid,$prizeid,$count = 1){
        $count = intval($count);
        $key = $this->key . 'UserPrizeCount:' . $userid . ':' . $prizeid  . ':' . date('Y-m-d');
        $this->redis->incrBy($key,$count);
        $this->redis->expire($key,86400);
        return true;
    }

    public function returnUserPrizeCount($userid,$prizeid){
        $key = $this->key . 'UserPrizeCount:' . $userid . ':' . $prizeid . ':' . date('Y-m-d');;
        if(!$this->redis->exists($key)){
            return 0;
        }
        return $this->redis->get($key);
    }


    /**
     * 用户机会
     */

    public function incChanceCount($userid,$count = 1){
        $count = intval($count);
        $key = $this->key . 'UserChanceCount:' . $userid .':' . date('Y-m-d');
        $this->redis->incrBy($key,$count);
        $this->redis->expire($key,86400);
        return true;
    }

    /**
     * 减去用户机会
     *
     * <pre>
     *
     * </pre>
     */

    public function decChanceCount($userid,$count = 1){
        $count = intval($count);
        $key = $this->key . 'UserChanceCount:' . $userid .':' . date('Y-m-d');
        $this->redis->decrBy($key,$count);
        $this->redis->expire($key,86400);
        return true;
    }

    public function isChanceCount($userid){
        $key = $this->key . 'UserChanceCount:' . $userid .':' . date('Y-m-d');
        if($this->redis->exists($key)){
            return true;
        }
        return false;
    }

    public function getChanceCount($userid){
        $key = $this->key . 'UserChanceCount:' . $userid .':' . date('Y-m-d');
        return $this->redis->get($key);
    }

}