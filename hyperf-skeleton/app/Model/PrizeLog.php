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

class PrizeLog
{


    private $pdo = null;
    private $redis = null;
    private $key = 'MTW_PRIZELOG_';
    private $_table = 'prize_log';
    private $_field = array(
        'userid' => '', //用户id
        'prizeindex' => '', //奖品下标
        'createtime' => '', //创建时间
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

}