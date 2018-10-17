<?php
/**
 * 我的预约
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:11
 */
include 'library/init.inc.php';

$template = 'reservation.phtml';

$action = 'list|detail';
$act = check_action($action, getGET('act'));
if($act == '')
{
    $act = 'list';
}

$oc_status = array(
    0 => '有效',
    1 => '已使用',
    2 => '已过期',
    3 => '失效'
);

if('detail' == $act)
{
    $id = intval(getGET('id'));

    if($id <= 0)
    {
        redirect('reservation.php');
    }

    $get_order_content = 'select * from '.$db->table('order_content').' where `id`='.$id.' and `account`=\''.$_SESSION['account'].'\'';
    $order_content = $db->fetchRow($get_order_content);

    if($order_content)
    {
        $order_content['show_status'] = $oc_status[$order_content['status']];
        $order_content['left_time'] = $order_content['end_time'] - $order_content['begin_time'];
        $order_content['left_time'] = ($order_content['left_time']/3600).'小时';
    } else {
        redirect('reservation.php');
    }
    assign('order_content', $order_content);

    $get_shop_info = 'select `shop_name`,`mobile`,`longitude`,`latitude` from '.$db->table('business').' where `business_account`=\''.$order_content['business_account'].'\'';
    $shop = $db->fetchRow($get_shop_info);
    assign('shop', $shop);

    $get_product_info = 'select `img`,`detail` from '.$db->table('product').' where `product_sn`=\''.$order_content['product_sn'].'\'';
    $product_info = $db->fetchRow($get_product_info);
    assign('product_info', $product_info);
    $template = 'reservation_detail.phtml';
}

if('list' == $act)
{
    $get_order_content = 'select `id`,`product_sn`,`product_name`,`code`,`end_time`,`status` from ' . $db->table('order_content') . ' where `account`=\'' . $_SESSION['account'] . '\'';

    $order_content = $db->fetchAll($get_order_content);

    if($order_content)
    {
        foreach ($order_content as $key => $oc)
        {
            $oc['show_status'] = $oc_status[$oc['status']];
            $order_content[$key] = $oc;
        }
    }
    assign('order_content', $order_content);
}

$smarty->display($template);