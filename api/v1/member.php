<?php
/**
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/22
 * Time: 14:48
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user, $levels;

$response = [
    'error' => -1,
    'message' => ''
];


$opera = getPOST('opera');
$action = 'show';
$act = check_action($action, getGET('act'), 'show');

//同步用户信息
if('sync' == $opera) {
    $member_data = [
        'nickname' => $db->escape(getPOST('nickname')),
        'headimg' => $db->escape(getPOST('avatar'))
    ];

    if($db->autoUpdate('member', $member_data, '`account`=\''.$current_user['account'].'\'') !== false) {
        $response = [
            'error' => 0,
            'message' => '更新用户信息成功'
        ];
    } else {
        $response['message'] = '更新用户信息失败';
    }
}

//======================== 读取基本信息 =============================
if('show' == $act) {
    $columns = [
        'account',
        'nickname',
        'mobile',
        'integral',
        'balance',
        'headimg',
        'level_id'
    ];

    $member = $db->find('member', $columns, ['account' => $current_user['account']]);

    if($member) {
        $response['error'] = 0;
        $response['member'] = [
            'account' => $member['account'],
            'nickname' => $member['nickname'],
            'mobile' => $member['mobile'],
            'integral' => floatval($member['integral']),
            'balance' => floatval($member['balance']),
            'avatar' => $member['headimg'],
            'level_name' => isset($levels[$member['level_id']]) ? $levels[$member['level_id']]['name'] : ''
        ];
    } else {
        throw new RestFulException('请先登录', 503);
    }
}

echo json_encode($response);