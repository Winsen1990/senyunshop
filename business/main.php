<?php
/**
 * 商户管理后台首页
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-8-19
 * @version 1.0.0
 */

include 'library/init.inc.php';

//商户管理后台初始化
business_base_init();
$template = 'main/';

$action = 'view';
$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;
//产品数量
$get_product_count = 'select count(*) from '.$db->table('product');
$get_product_count .= ' where `business_account` = \''.$_SESSION['business_account'].'\' and `status` <> 5';
$product_count = $db->fetchOne($get_product_count);
assign('product_count', $product_count);

//产品分类数量
$get_category_count = 'select count(*) from '.$db->table('category');
$get_category_count .= ' where `business_account` = \''.$_SESSION['business_account'].'\'';
$category_count = $db->fetchOne($get_category_count);
assign('category_count', $category_count);

//订单数量
$get_order_count = 'select count(*) from '.$db->table('order');
$get_order_count .= ' where `business_account` = \''.$_SESSION['business_account'].'\'';
$order_count = $db->fetchOne($get_order_count);
assign('order_count', $order_count);

//系统消息数量
$get_message_count = 'select count(*) from '.$db->table('message');
$get_message_count .= ' where `business_account` = \''.$_SESSION['business_account'].'\' and status = 0';
$message_count = $db->fetchOne($get_message_count);
assign('message_count', $message_count);

$template .= $act.'.phtml';
$smarty->display($template);




