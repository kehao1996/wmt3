<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/27
 * Time: 14:43
 */

namespace App\Admin;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

class ApiController {



    /**
     * @Inject()
     * @var \Hyperf\Contract\SessionInterface
     */
    private $session;

    protected $mid = 0;


    /**
     * 构造函数
     *
     * @ignore
     */
    public function __construct()
    {
        $key = 'WMT_ADMIN_ID';
        echo $key;
        if ($this->session->has($key)) {
            $userid = $this->session->get($key);
            if($userid){
                $this->mid = $userid;
            }

        }
    }
}