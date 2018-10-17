<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午4:47
 */
include 'library/init.inc.php';

$now = time();
$get_product_list = 'select p.`product_sn`,p.`id`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,p.`name`,p.`img` from '.$db->table('distribution').' as d join '.$db->table('product').' as p '.
                    ' using(`product_sn`) where d.`account`=\''.$_SESSION['account'].'\' order by d.`add_time` DESC';

$product_list = $db->fetchAll($get_product_list);
assign('product_list', $product_list);

assign('title', '我的分销');
assign('mode', 'distribution');
$smarty->display('wishlist.phtml');