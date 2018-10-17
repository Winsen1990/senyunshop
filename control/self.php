<?php
/**
 * 帐号管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-10-26
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'self/';
assign('subTitle', '帐号管理');

$action = 'view|password';
$operation = 'edit|password';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//======================================================================
if( 'edit' == $opera ) {
    $response = array('error' => 1, 'msg' => '', 'errmsg' => array());

    $email = trim(getPOST('email'));
    $name = trim(getPOST('name'));
    $mobile = trim(getPOST('mobile'));
    $sex = trim(getPOST('sex'));

    if( '' == $email ) {
        $response['errmsg']['email'] = '请输入邮箱地址';
    }

    if( '' == $name ) {
        $response['errmsg']['name'] = '请输入邮箱地址';
    }

    if( '' == $mobile ) {
        $response['errmsg']['mobile'] = '请输入手机号码';
    }

    if( count($response['errmsg']) ) {
        echo json_encode($response);
        exit;
    }


    if( !is_email($email) ) {
        $response['errmsg']['email'] = '请输入正确的邮箱地址';
    }

    if( !is_mobile($mobile) ) {
        $response['errmsg']['mobile'] = '请输入正确的手机号码';
    }

    if( count($response['errmsg']) ) {
        echo json_encode($response);
        exit;
    }

    $email = $db->escape($email);
    $mobile = $db->escape($mobile);
    $name = $db->escape($name);
    $sex = $sex == 'M' ? 'M' : 'F';

    $check_email = 'select * from '.$db->table('platform_admin').' where email = \''.$email.'\'';
    $check_email .= ' and account <> \''.$_SESSION['admin_account'].'\'';
//        echo $check_email;exit;

    if( $db->fetchRow($check_email) ) {
        $response['errmsg']['email'] = $email.'已被使用';
    }

    $check_mobile = 'select * from '.$db->table('platform_admin').' where mobile = \''.$mobile.'\'';
    $check_mobile .= ' and account <> \''.$_SESSION['admin_account'].'\'';

    if( $db->fetchRow($check_mobile) ) {
        $response['errmsg']['mobile'] = $mobile.'已被使用';
    }

    if( count($response['errmsg']) ) {
        echo json_encode($response);
        exit;
    }

    $data = array(
        'email' => $email,
        'mobile' => $mobile,
        'name' => $name,
        'sex' => $sex,
    );

    if( $db->autoUpdate('platform_admin', $data, 'account = \''.$_SESSION['admin_account'].'\'') ) {
        $response['error'] = 0;
        $response['msg'] = '修改资料成功';
        $_SESSION['name'] = $name;
    } else {
        $response['msg'] = '系统繁忙，请稍后重试';
    }
    echo json_encode($response);
    exit;
}

if( 'password' == $opera ) {
    $response = array('error' => 1, 'msg' => '', 'errmsg' => array());

    $password = trim(getPOST('password'));
    $new_password = trim(getPOST('new_password'));
    $confirm = trim(getPOST('confirm'));

    if( '' == $password ) {
        $response['errmsg']['password'] = '请输入原密码';
    }

    if( '' == $new_password ) {
        $response['errmsg']['new_password'] = '请输入新密码';
    } elseif( strlen($new_password) < 6 || strlen($new_password) > 16 ) {
        $response['errmsg']['new_password'] = '密码长度6～16个字符';
    }

    if( $new_password != $confirm ) {
        $response['errmsg']['confirm'] = '两次输入密码不一致';

    }

    if( count($response['errmsg']) ) {
        echo json_encode($response);
        exit;
    }

    $table = 'platform_admin';
    $where = ' `account` = \''.$_SESSION['admin_account'].'\'';

    $get_password = 'select `password` from '.$db->table($table).' where';
    $get_password .= $where;
    $get_password .= ' limit 1';

    $old_password = $db->fetchOne($get_password);
    if( md5($password.PASSWORD_END) != $old_password ) {
        $response['msg'] = '原密码不正确';
        echo json_encode($response);
        exit;
    }

    $data = array(
        'password' => md5($new_password.PASSWORD_END),
    );

    if( $db->autoUpdate($table, $data, $where) ) {
        $response['msg'] = '修改密码成功';
    } else {
        $response['msg'] = '系统繁忙，请稍后重试';
    }
    echo json_encode($response);
    exit;

}

//======================================================================

if( 'view' == $act ) {

    $get_admin = 'select `name`, `email`, `mobile`, `sex` from '.$db->table('platform_admin').' where `account` = \''.$_SESSION['admin_account'].'\'';
    $get_admin .= ' and `account` = \''.$_SESSION['admin_account'].'\' limit 1';
    $admin = $db->fetchRow($get_admin);
    assign('admin', $admin);
}

if( 'password' == $act ) {

}

$template .= $act.'.phtml';
$smarty->display($template);



