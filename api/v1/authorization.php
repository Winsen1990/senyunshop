<?php
/**
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/22
 * Time: 18:49
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$code = getPOST('code');

if(empty($code)) {
    throw new RestFulException('参数错误', 400);
}

$api_response = get_openid($code, $config['mini_appid'], $config['mini_appsecret']);
$log->record_array($api_response);
if(empty($api_response['openid'])) {
   throw new RestFulException('授权登录失败', 500);
}
$openid = $api_response['openid'];
$get_user = 'select * from '.$db->table('member').' where `openid`=\''.$api_response['openid'].'\'';

if(isset($api_response['unionid']))
{
    $unionid = $db->escape($api_response['unionid']);
    $get_user = 'select * from '.$db->table('member').' where `unionid`=\''.$unionid.'\'';
}

$parent_id = 0;
$user = $db->fetchRow($get_user);
$account = '';
if(!$user) {
    //注册用户
    if ($account = register_member($openid, $parent_id)) {
        $log->record('async user list, add new user with: openid=' . $openid);
        //发放推广奖励
        if ($account && isset($config['recommend_integral']) && $config['recommend_integral'] > 0) {
            if (add_recommend_integral($account, $config['recommend_integral'], '推荐新用户奖励')) {
                $log->record('add recommend integral success:' . $account . ' increment ' . $config['recommend_integral']);
            } else {
                $log->record('add recommend integral fail:' . $account . ' increment ' . $config['recommend_integral']);
            }
        } else {
            $log->record('account is empty or recommend integral below zero');
        }

        //发放关注奖励
        if ($account && isset($config['subscribe_integral']) && $config['subscribe_integral'] > 0) {
            if (add_recommend_integral($account, $config['subscribe_integral'], '关注送积分')) {
                $log->record('add recommend integral success:' . $account . ' increment ' . $config['subscribe_integral']);
            } else {
                $log->record('add recommend integral fail:' . $account . ' increment ' . $config['subscribe_integral']);
            }
        }

        $user = [
            'account' => $account,
            'nickname' => null,
            'avatar' => null
        ];
    } else {
        $log->record('async user list fail. openid=' . $openid);
        throw new RestFulException('注册用户失败', 505);
    }
} else {
    $account = $user['account'];
}

//根据account获取token并进行刷新
$token_record = $db->find('api_token', ['account', 'add_time', 'token', 'expired'], ['account' => $account]);
$response = [
    'error' => -1,
    'message' => '',
    'token' => '',
    'expired' => 0
];

if(empty($token_record)) {
    //从未登录过，生产新的token
    $token_record = [
        'account' => $account,
        'add_time' => time(),
        'token' => md5($account.time()),
        'expired' => time() + 1800 //半个小时过期
    ];

    if($db->autoInsert('api_token', [$token_record])) {
        $response['error'] = 0;
        $response['message'] = '登录成功';
        $response['expired'] = date('Y-m-d H:i:s', $token_record['expired']);
        $response['token'] = $token_record['token'];
    }
} else {
    //刷新token
    if($token_record['expired'] < time()) {
        $token_record['token'] = md5($account.time());
        $token_record['add_time'] = time();
    }

    if($db->autoReplace('api_token', [$token_record])) {
        $response['error'] = 0;
        $response['message'] = '登录成功';
        $response['expired'] = date('Y-m-d H:i:s', $token_record['expired']);
        $response['token'] = $token_record['token'];
    }
}

if(!empty($user)) {
    $response['user'] = [
        'account' => $user['account'],
        'nickname' => $user['nickname'],
        'avatar' => $user['headimg']
    ];
} else {
    $response['user'] = null;
}

echo json_encode($response);