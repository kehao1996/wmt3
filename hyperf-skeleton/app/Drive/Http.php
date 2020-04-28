<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/12
 * Time: 19:05
 */
namespace App\Drive;

use GuzzleHttp\Client;
use Hyperf\Guzzle\CoroutineHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\RequestException;

class Http {

    public $client;
    public function __construct($base_url = ''){
        $this->client = new Client([
            'base_uri' => $base_url,
            'handler' => HandlerStack::create(new CoroutineHandler()),
            'timeout' => 5,
            'swoole' => [
                'timeout' => 10,
                'socket_buffer_size' => 1024 * 1024 * 2,
            ],
        ]);
    }

    public function posturl($url,$param = [],$token = '',$head_type = 'json'){
        $other = [];
        if(!empty($param)){
            if($head_type == 'multipart'){
                $i = 0;
                foreach($param as $k=>$v){
                    $other['multipart'][$i]['name'] = $k;
                    $other['multipart'][$i]['contents'] = $v;
                    $i++;
                }
            }else{
                $other['form_params'] = $param;
            }

        }
        if(!empty($token)){
            $other['headers']['Authorization'] ='BEARER '. $token;
        }

        $body = false;
        try {
            $response = $this->client->request('POST',$url,$other);
            $body = $response->getBody();
        }catch (RequestException $e){

            if ($e->hasResponse()) {
                $body =  $e->getResponse()->getBody();
            }
            if(empty($body)){
                $body = $e->getMessage();
            }
        }

        return (string)$body;
    }
}