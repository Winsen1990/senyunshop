<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/23
 * Time: 下午12:25
 */
include 'library/init.inc.php';

$now = time();
$get_product_list = 'select p.`shop_price`,p.`given_integral`,p.`product_sn`,p.`name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,p.`id`,p.`img` from '.$db->table('history').' as d join '.$db->table('product').' as p '.
    ' using(`product_sn`) where d.`account`=\''.$_SESSION['account'].'\' order by d.`add_time` DESC';

$product_list = $db->fetchAll($get_product_list);
assign('product_list', $product_list);

assign('title', '我的足迹');
assign('mode', 'history');
$smarty->display('wishlist.phtml');