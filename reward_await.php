<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/17
 * Time: 下午4:56
 */
include 'library/init.inc.php';

//获取用户信息
$get_user_info = 'select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user_info = $db->fetchRow($get_user_info);
assign('user_info', $user_info);

//获取明细
$get_member_exchange = 'select * from '.$db->table('member_exchange_log').' where `account`=\''.$_SESSION['account'].
    '\' and `reward_await`<>0 order by `add_time` DESC';

$member_exchange = $db->fetchAll($get_member_exchange);
assign('member_exchange', $member_exchange);

assign('mode', 'reward_await');
assign('unit', '元');
assign('notice', '快来推广赚佣金');
assign('title', '待发佣金');
$smarty->display('points.phtml');