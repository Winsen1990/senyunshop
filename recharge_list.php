<?php
/**
 * 充值记录
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:11
 */
include 'library/init.inc.php';


//获取用户信息
$get_user_info = 'select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user_info = $db->fetchRow($get_user_info);
assign('user_info', $user_info);

//获取充值记录同时清理没有支付的记录
$get_recharge_list = 'select `recharge_sn`,`amount`,`status`,`type`,`add_time` from '.$db->table('recharge').' where `account`=\''.$_SESSION['account'].'\' and `status` in (1,2)';

$recharge_list = $db->fetchAll($get_recharge_list);
$member_exchange = array();
if($recharge_list)
{
    foreach($recharge_list as $record)
    {
        $status = '';
        switch($record['status'])
        {
            case 0:
                $status = ' (待处理)';
                break;
            case 1:
                $status = ' (已到账)';
                break;
            case 2:
                $status = ' (充值中)';
                break;
            case 3:
                $status = ' (取消)';
                break;
        }

        $member_exchange[] = array(
            'remark' => '充值流水号:'.$record['recharge_sn'].$status,
            'add_time' => $record['add_time'],
            'balance' => $record['amount']
        );
    }
}

assign('member_exchange', $member_exchange);

assign('mode', 'balance');
assign('unit', '元');
assign('notice', '快到我的钱包里来');
assign('title', '充值记录');
$smarty->display('points.phtml');