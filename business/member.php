<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2017/12/28
 * Time: 上午11:35
 */
include 'library/init.inc.php';

global $log, $db, $config, $loader;
$loader->includeClass('RestFulException');

$operation = 'view';

$opera = check_action($operation, getPOST('opera'));

$response = array('error' => -1, 'message' => '');

if (!isset($_SESSION['business_account'])) {
    throw new RestFulException('没有访问权限', 40001);
}

if('view' == $opera) {
    $account = trim(getPOST('account'));

    $account_pattern = '/^SJ\d{10}$/i';

    if(empty($account)) {
        throw new RestFulException('请填写会员编号或微信昵称', 40002);
    }

    $columns = array(
        'account',
        'nickname',
        'sex',
        'headimg'
    );

    $condition = array();
    if(preg_match($account_pattern, $account)) {
        //按账号查找
        $condition['account'] = $account;
    } else {
        //按昵称查找
        $condition['nickname'] = array('like', '%'.$account.'%');
    }

    $member = $db->all('member', $columns, $condition);

    $response['error'] = 0;
    $response['member_list'] = $member;
}

echo json_encode($response);
exit;