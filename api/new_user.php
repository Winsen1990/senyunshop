<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/24
 * Time: 下午2:30
 */
include 'library/init.inc.php';

global $log, $db;

$log->record_array($_POST);
$response = array('error'=>1, 'msg'=>'');
$openid = getPOST('openid');
$scene_id = intval(getPOST('scene_id'));

$parent_id = 0;
$account = '';
if($scene_id > 0)
{
    $get_user = 'select `id`,`account` from ' . $db->table('member') . ' where `scene_id`=' . $scene_id;
    $log->record($get_user);

    $user = $db->fetchRow($get_user);

    if($user)
    {
        $log->record_array($user);
        $parent_id = $user['id'];
        $account = $user['account'];
    }
}

$openid = $db->escape($openid);
$get_user = 'select `id` from '.$db->table('member').' where `openid`=\''.$openid.'\'';

$access_token = get_access_token($config['appid'], $config['appsecret']);
$all_info = get_user_wechat_info($access_token, $openid);
if(isset($all_info->unionid))
{
    $unionid = $db->escape($all_info->unionid);
    $get_user = 'select `id` from '.$db->table('member').' where `unionid`=\''.$unionid.'\'';
}

$uid = $db->fetchOne($get_user);
if(!$uid && ($user_account = register_member($openid, $parent_id)))
{
    if(get_user_wechat_info($access_token, $openid))
    {
        $log->record('update user info success');
    }

    $log->record('async user list, add new user with: openid='.$openid.',scene_id='.$scene_id);
    //发放推广奖励
    if($account != '' && $config['recommend_integral'] > 0)
    {
        if(add_recommend_integral($account, $config['recommend_integral'], '推荐新用户奖励'))
        {
            $log->record('add recommend integral success:'.$account.' increment '.$config['recommend_integral']);
        } else {
            $log->record('add recommend integral fail:'.$account.' increment '.$config['recommend_integral']);
        }
    } else {
        $log->record('account is empty or recommend integral below zero');
    }

    //发放关注奖励
    if($user_account && $config['subscribe_integral'] > 0) {
        if(add_recommend_integral($user_account, $config['subscribe_integral'], '关注送积分'))
        {
            $log->record('add recommend integral success:'.$user_account.' increment '.$config['subscribe_integral']);
        } else {
            $log->record('add recommend integral fail:'.$user_account.' increment '.$config['subscribe_integral']);
        }
    }
} else {
    $log->record('async user list fail. openid='.$openid.',scene_id='.$scene_id);
}

echo json_encode($response);
exit;