<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午4:49
 */
include 'library/init.inc.php';

$operation = 'bind';
$opera = check_action($operation, getPOST('opera'));

if('bind' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');
    $verify = getPOST('verify');
    $mobile = getPOST('mobile');
    $password = getPOST('password');

    if(!check_cross_domain())
    {
        if($mobile == '')
        {
            $response['msg'] .= '-请输入手机号码<br/>';
        } else {
            if(is_mobile($mobile))
            {
                $mobile = $db->escape($mobile);
            } else {
                $response['msg'] .= '-手机号码格式错误<br/>';
            }
        }

        if($verify == '')
        {
            $response['msg'] .= '-请输入验证码<br/>';
        } else {
            $check_code = 'select `code`,`expire` from '.$db->table('message_code').' where `mobile`=\''.$mobile.'\'';
            $message_code = $db->fetchRow($check_code);

            if($verify != $message_code['code'])
            {
                $response['msg'] .= '-验证码错误<br/>';
            }
        }

        if($password == '')
        {
            $response['msg'] .= '-请填写密码<br/>';
        } else {
            $password = md5($password.PASSWORD_END);
        }

        if($response['msg'] == '')
        {
            $data = array('mobile'=>$mobile, 'password'=>$password);

            if($db->autoUpdate('member', $data, '`account`=\''.$_SESSION['account'].'\''))
            {
                $response['error'] = 0;
                $response['msg'] = '绑定手机号码成功';
            } else {
                $response['msg'] ='001:系统繁忙，请稍后再试';
            }
        }
    } else {
        $response['msg'] = '404:参数错误';
    }

    echo json_encode($response);
    exit;
}

$_SESSION['token'] = 'can send message.';
$smarty->display('binding.phtml');