<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/7
 * Time: 上午11:22
 */
include 'library/init.inc.php';

$operation = 'edit';

$opera = check_action($operation, getPOST('opera'));

if('edit' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $password = getPOST('password');
    $mobile = getPOST('mobile');

    if($password == '')
    {
        $response['msg'] = '请填写新密码';
    }

    if(!is_mobile($mobile))
    {
        $response['msg'] = '手机号码格式不正确';
    } else {
        $mobile = $db->escape($mobile);
    }

    if(!isset($_SESSION['token']) || $_SESSION['token'] != 'verify message code success.')
    {
        $response['msg'] = '请先通过身份验证';
    }

    if($response['msg'] == '')
    {
        $password = md5($password.PASSWORD_END);

        $data = array(
            'password' => $password
        );

        if($db->autoUpdate('member', $data, '`mobile`=\''.$mobile.'\''))
        {
            $response['msg'] = '找回密码成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

$_SESSION['token'] = 'can send message.';

$smarty->display('forgot.phtml');