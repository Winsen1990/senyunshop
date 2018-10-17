<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/22
 * Time: 上午7:17
 */
include 'library/init.inc.php';

$sn = getGET('sn');
if($sn == '')
{
    redirect('index.php');
}

$sn = $db->escape($sn);

//获取商家信息
$get_business_info = 'select * from '.$db->table('business').' where `business_account`=\''.$sn.'\'';
$business = $db->fetchRow($get_business_info);

assign('business', $business);
//获取商家分类
$get_category = 'select `id`,`name` from '.$db->table('category').' where `business_account`=\''.$sn.'\' and `parent_id`='.$business['category_id'];
$category = $db->fetchAll($get_category);
if($category) {
    foreach($category as $key=>$c)
    {
        $get_children = 'select `id`,`name` from '.$db->table('category').' where `parent_id`='.$c['id'];

        $category[$key]['children'] = $db->fetchAll($get_children);
    }
}

assign('category', $category);
//获取商家全部产品
$now = time();
$get_product_list = 'select `given_integral`,`shop_price`,`name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`id`,`img` from '.$db->table('product').' where `status`=4 and `business_account`=\''.$sn.'\' order by `order_view` ASC';
$product_list = $db->fetchAll($get_product_list);
assign('product_list', $product_list);
//获取新添加的产品
$get_product_list = 'select `given_integral`,`shop_price`,`name`,`price`,`id`,`img` from '.$db->table('product').' where  `status`=4 and `business_account`=\''.$sn.'\''.
                    ' and `add_time`>'.(time()-3600*24*7).' order by `order_view` ASC';
$new_product = $db->fetchAll($get_product_list);
assign('new_product_count', count($new_product));

//获取轮播广告
$get_cycle_ad = 'select `img`,`url` from '.$db->table('ad').' where `ad_pos_id`=3 and `business_account`=\''.$sn.'\' order by `order_view`';
$cycle_ad = $db->fetchAll($get_cycle_ad);
assign('cycle_ad', $cycle_ad);

$smarty->display('shop.phtml');
