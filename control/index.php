<?php
/**
 * 管理后台登陆页
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-8-5
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $db, $config, $log, $validate_config;

$template = 'index/';

$action = 'login|forget|logout';
$operation = 'login|forget';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'login' : $act;

$opera = check_action($operation, getPOST('opera'));

$error = array();

//登陆
if( 'login' == $opera ) {
    $account = trim(getPOST('account'));
    $password = trim(getPOST('password'));
    $admin = null;

    if('' == $account) {
        $error['account'] = '账号不能为空';
    } else {
        $account = $db->escape($account);

        $checkAccount = 'select `password`,`role_id`,`name` from `'.DB_PREFIX.'platform_admin` where `account`=\''.$account.'\' limit 1';
        $admin = $db->fetchRow($checkAccount);
    }

    if('' == $password) {
        $error['password'] = '密码不能为空';
    } else {
        $password = md5($password.PASSWORD_END);
    }

    $luotest_reaponse = getPOST('luotest_response');
    $api_response = post('https://captcha.luosimao.com/api/site_verify', array('api_key' => $validate_config['api_key'], 'response' => $luotest_reaponse), true);
    $log->record($api_response);
    $api_response = json_decode($api_response, true);

    if($api_response['error'] != 0) {
        $error['validate'] = '验证失败';
    }

    if(count($error) == 0) {
        if ($admin) {
            if ($password == $admin['password']) {
                $getRole = 'SELECT `purview` FROM `' . DB_PREFIX . 'platform_role` WHERE `id`=' . $admin['role_id'] . ' LIMIT 1';
                if ($role = $db->fetchRow($getRole)) {
                    $_SESSION['purview'] = $role['purview'];
                    $_SESSION['admin_name'] = $admin['name'];
                    $_SESSION['admin_account'] = $account;

                    show_system_message('登录成功', array(array('alt' => '进入管理后台', 'link' => 'main.php')));
                    exit;
                } else {
                    $error['account'] = '账号错误';
                }
            } else {
                $error['password'] = '密码错误';
            }
        } else {
            $error['account'] = '账号不存在';
        }
    }
}

//忘记密码
if( 'forget' == $opera ) {

}

//登陆，默认
if( 'login' == $act ) {
    //如果已登陆
    if( check_admin_login() ) {
        redirect('main.php');
    }
}
//忘记密码
if( 'forget' == $act ) {
    //如果已登陆
    if( check_admin_login() ) {
        redirect('self.php');
    }
}

//注销
if( 'logout' == $act ) {
    unset($_SESSION['purview']);
    unset($_SESSION['name']);
    unset($_SESSION['account']);

    redirect('index.php');
}

assign('error', $error);
assign('validate_config', $validate_config);

assign('pageTitle', $config['site_name'].'-管理后台');
$template .= $act.'.phtml';
$smarty->display($template);

