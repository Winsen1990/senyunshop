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

    $email = getPOST('email');
    $sex = getPOST('sex');
    $mobile = getPOST('mobile');
    $identity = trim(getPOST('identity'));

    if(!is_mobile($mobile))
    {
        $response['msg'] .= '-手机号码格式不正确<br/>';
    } else {
        $mobile = $db->escape($mobile);
        //检查号码是否已被使用
        $check_mobile = 'select `account` from '.$db->table('member').' where `mobile`=\''.$mobile.'\' and `account`<>\''.$_SESSION['account'].'\'';

        if($db->fetchOne($check_mobile))
        {
            $response['msg'] = '-该号码已被其他用户使用<br/>';
        }
    }

//    if($email == '')
//    {
//        $response['msg'] .= '-请填写邮箱地址<br/>';
//    } else {
//        if(filter_var($email, FILTER_VALIDATE_EMAIL))
//        {
//            $email = $db->escape($email);
//        } else {
//            $response['msg'] .= '-邮箱格式不正确<br/>';
//        }
//    }
    if( $identity == '' ) {
        $identity = '';
    } else {
        if( check_identity_num($identity) ) {
            $identity = $db->escape($identity);
        } else {
            $response['msg'] = '-身份证格式不正确<br />';
        }
    }

    $sex_list = 'N|F|M';
    $sex = check_action($sex_list, $sex);

    if($sex == '')
    {
        $sex = 'N';
    }

    if($response['msg'] == '')
    {
        $member_data = array(
//            'email' => $email,
            'sex' => $sex,
            'mobile' => $mobile,
            'identity' => $identity,
        );

        if($db->autoUpdate('member', $member_data, '`account`=\''.$_SESSION['account'].'\''))
        {
            $response['error'] = 0;
            $response['msg'] = '修改信息成功';
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

$level = array(
    0 => '普通会员',
    1 => '商业会员',
    2 => '招商会员'
);
assign('level', $level);

$get_user_info = 'select `level_id`,`nickname`,`mobile`,`account`,`sex`,`email`, `identity` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user = $db->fetchRow($get_user_info);
assign('user', $user);

$smarty->display('user-info.phtml');
