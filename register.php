<?php
/**
 * 首页
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:11
 */
include 'library/init.inc.php';

$operation = 'reg';

$opera = check_action($operation, getPOST('opera'));

if('reg' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $password = getPOST('password');
    $mobile = getPOST('mobile');

    if($mobile == '')
    {
        $response['msg'] .= '-请填写手机号码<br/>';
    } else {
        if(!is_mobile($mobile))
        {
            $response['msg'] .= '-手机号码格式错误<br/>';
        } else {
            $mobile = $db->escape($mobile);
        }
    }

    if($password == '')
    {
        $response['msg'] .= '-请填写密码<br/>';
    }

    if(!isset($_SESSION['token']) || $_SESSION['token'] != 'verify message code success.')
    {
        $response['msg'] .= '-请先通过身份验证<br/>';
    }

    if($response['msg'] == '')
    {
        $password = md5($password.PASSWORD_END);

        if($account = register_mobile($mobile, $password, $_SESSION['parent_id']))
        {
            $response['error'] = 0;
            //发送短信提示用户
            $response['account'] = $account;
            $_SESSION['account'] = $account;
            $response['msg'] = '注册成功，您的会员卡号为:'.$account.',请牢记';
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}
$_SESSION['token'] = 'can send message.';
$smarty->display('register.phtml');