<?php
/**
 * 首页
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:11
 */
include 'library/init.inc.php';
global $config, $smarty, $log;

if(is_weixin() && (!isset($_SESSION['openid']) ||  $_SESSION['openid'] == '')) {
    $script_name = str_replace(ROOT_PATH, '', $_SERVER['HTTP_REFERER']);
    $current_url = $script_name;
    $log->record('grant wechat login: '.$current_url);
    $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$config['appid'].'&redirect_uri='.$current_url.'&response_type=code&scope=snsapi_userinfo&state=2048#wechat_redirect';
    redirect($url);
    exit;
}


$smarty->display('login.phtml');