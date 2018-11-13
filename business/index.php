<?php
/**
 * 商户管理后台登陆页
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-8-19
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $config, $db, $validate_config, $smarty;

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

    if('' == $account) {
        $error['account'] = '账号不能为空';
    } else {
        $account = $db->escape(htmlspecialchars($account));
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
        if (preg_match('#@#', $account)) {
            $checkAccount = 'SELECT `password`,`role_id`,`business_account` FROM ' . $db->table('admin') . ' WHERE `account`=\'' . $account . '\' LIMIT 1';
            $admin = $db->fetchRow($checkAccount);
            if ($admin) {
                if ($password == $admin['password']) {

                    $get_purview = 'SELECT purview FROM ' . $db->table('role') . ' WHERE id = ' . $admin['role_id'] . ' LIMIT 1';
                    $purview = $db->fetchOne($get_purview);

                    $get_business = 'SELECT `shop_name`,`status` FROM ' . $db->table('business') . ' WHERE `business_account`=\'' . $admin['business_account'] . '\' LIMIT 1';
                    $business = $db->fetchRow($get_business);

                    if ($business['status'] != 2) {
                        $error['account'] = '帐号不存在或被冻结';
                    } else {
                        $_SESSION['business_shop_name'] = $business['shop_name'];
                        $_SESSION['business_account'] = $admin['business_account'];
                        $_SESSION['business_purview'] = $purview;
                        $_SESSION['business_admin'] = $account;
                        show_system_message('登录成功', array(array('alt' => '进入管理后台', 'link' => 'main.php')));
                        exit;
                    }
                } else {
                    $error['password'] = '帐号或密码错误';
                }
            } else {
                $error['account'] = '帐号不存在或被冻结';
            }

        } else {
            $checkAccount = 'SELECT `password`,`shop_name`,`status` FROM ' . $db->table('business') . ' WHERE `business_account`=\'' . $account . '\' LIMIT 1';
            $business = $db->fetchRow($checkAccount);

            if ($business) {
                if ($business['status'] != 2) {
                    $error['account'] = '帐号不存在或被冻结';
                } else {
                    if ($password == $business['password']) {
                        global $purview;
                        $_SESSION['business_shop_name'] = $business['shop_name'];
                        $_SESSION['business_account'] = $account;
                        $_SESSION['business_purview'] = json_encode($purview);
                        $_SESSION['business_admin'] = $account;

                        show_system_message('登录成功', array(array('alt' => '进入管理后台', 'link' => 'main.php')));
                        exit;

                    } else {
                        $error['password'] = '帐号或密码错误';
                    }
                }
            } else {
                $error['account'] = '账号不存在';
            }
        }
    }
}

//忘记密码
if( 'forget' == $opera ) {

}

//登陆，默认
if( 'login' == $act ) {
    //如果已登陆
    if( isset($_SESSION['business_account']) ) {
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
    unset($_SESSION['business_shop_name']);
    unset($_SESSION['business_account']);
    unset($_SESSION['business_purview']);

    redirect('index.php');
}

assign('validate_config', $validate_config);
assign('error', $error);

assign('pageTitle', '商户管理后台');
$template .= $act.'.phtml';
$smarty->display($template);

