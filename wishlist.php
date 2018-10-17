<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午4:47
 */
include 'library/init.inc.php';

$now = time();
$get_product_list = 'select p.`shop_price`,p.`given_integral`,p.`product_sn`,p.`name`,p.`img`,p.`id`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price` from '.$db->table('collection').' as d join '.$db->table('product').' as p '.
    ' using(`product_sn`) where d.`account`=\''.$_SESSION['account'].'\' order by d.`add_time` DESC';

$product_list = $db->fetchAll($get_product_list);
assign('product_list', $product_list);

assign('title', '我的收藏');
assign('mode', 'collection');
$smarty->display('wishlist.phtml');