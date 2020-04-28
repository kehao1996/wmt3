<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/13
 * Time: 10:35
 */

namespace App\Drive;
use Hyperf\Di\Annotation\Inject;
use Hyperf\WebSocketClient\ClientFactory;
use Hyperf\WebSocketClient\Frame;

class Ws {

    /**
     * @Inject
     * @var ClientFactory
     */
    protected $clientFactory;

    private $host = null;
    private $autoClose = true;

    public function __construct($host,$autoClose = true){
        $this->host = $host;
        $this->autoClose = $autoClose;
    }

    public function send($data)
    {
        if(is_array($data)){
            $data = json_encode($data);
        }
        // 对端服务的地址，如没有提供 ws:// 或 wss:// 前缀，则默认补充 ws://
        $host = $this->host;
        $clientFactory = new ClientFactory();
        // 通过 ClientFactory 创建 Client 对象，创建出来的对象为短生命周期对象
        $client = $clientFactory->create($host,$this->autoClose);
        // 向 WebSocket 服务端发送消息
        $client->push($data);
        // 获取服务端响应的消息，服务端需要通过 push 向本客户端的 fd 投递消息，才能获取；以下设置超时时间 2s，接收到的数据类型为 Frame 对象。
        /** @var Frame $msg */
//        $msg = $client->recv(2);
//        var_dump($msg->data);
        // 获取文本数据：$res_msg->data
        return true;
    }
}