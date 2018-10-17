<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 上午9:17
 */
include 'library/init.inc.php';

global $db, $config, $log, $smarty;

$level = array(
    0 => '普通会员',
    1 => '商业会员',
    2 => '招商会员'
);
assign('level', $level);

//获取用户信息
$get_user_info = 'select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user_info = $db->fetchRow($get_user_info);
assign('user_info', $user_info);

//获取订单数量  待付款  待收货  待评价
$get_order_count = 'select count(*) from '.$db->table('order').' where `account`=\''.$_SESSION['account'].'\' and `status`=';

$order_await_pay = $db->fetchOne($get_order_count.'1');

$order_await_receive = $db->fetchOne($get_order_count.'6');

$order_await_comment = $db->fetchOne($get_order_count.'7');

assign('order_await_pay', $order_await_pay);
assign('order_await_receive', $order_await_receive);
assign('order_await_comment', $order_await_comment);

//获取消费总额
$get_order_sum = 'select sum(`amount`) from '.$db->table('order').' where `status`>2 and `status`<>11 and `account`=\''.$_SESSION['account'].'\'';
$order_sum = $db->fetchOne($get_order_sum);
$order_sum = floatval($order_sum);
$order_sum = sprintf("%.2f", $order_sum);
assign('order_sum', $order_sum);

//获取收藏总数
$get_collection_count = 'select count(*) from '.$db->table('collection').' where `account`=\''.$_SESSION['account'].'\'';
$collection_count = $db->fetchOne($get_collection_count);
assign('collection_count', $collection_count);

//获取我的足迹
$get_history_count = 'select count(*) from '.$db->table('history').' where `account`=\''.$_SESSION['account'].'\'';
$history_count = $db->fetchOne($get_history_count);
assign('history_count', intval($history_count));

//获取猜你喜欢
$get_fav_products = 'select `given_integral`,`name`,`price`,`img`,`id` from '.$db->table('product').
                    ' where `status`=4 and not exists (select `product_sn` from '.$db->table('activity_mapper').' where `activity_id`=1 and `product_sn`='.$db->table('product').'.`product_sn`) order by `order_view` ASC limit 3';
$fav_products = $db->fetchAll($get_fav_products);
assign('fav_products', $fav_products);

$smarty->display('home.phtml');
