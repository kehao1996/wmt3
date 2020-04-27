<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/4/27
 * Time: 14:43
 */

namespace App\Admin;


class Api {



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
        $userid = $this->session->get('WMT_ADMIN_ID');

        if ($userid) {
            $this->mid = $userid;
        }
    }
}