<?php

/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2017/12/8
 * Time: 上午10:31
 */
if(!interface_exists('WxResponse')) {
    include 'WxResponse.interf.php';
}

if(!function_exists('send_customer_message')) {
    include 'wechat.inc.php';
}

class WxActivityResponse implements WxResponse
{
    var $public_account;
    var $openid;
    var $content;
    private $db;

    public function __construct($public_account, $openid, $content) {
        global $db;

        $this->public_account = $public_account;
        $this->openid = $openid;
        $this->content = $content;
        $this->db = $db;
    }

    public function run() {

    }

    public function send() {
    }
}