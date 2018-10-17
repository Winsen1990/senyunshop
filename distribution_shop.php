<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/22
 * Time: 上午7:17
 */
include 'library/init.inc.php';

global $db, $log, $config;

//获取用户信息
$get_user_info = 'select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user_info = $db->fetchRow($get_user_info);

//获取上一级用户信息
$get_parent = 'select `id` from '.$db->table('member').' where `id` in ('.$user_info['path'].'0) and `level_id`>0 order by `path` DESC limit 1';
$parent_id = $db->fetchOne($get_parent);

$get_product_list = '';
$user = null;
if($parent_id == 0)
{
    redirect('product_list.php');
    /*
    $user = array(
        'nickname' => $config['site_name'],
        'headimg' => 'themes/sbx/images/logo.jpg'
    );
    $get_product_list = 'select `name`,`price`,`id`,`img` from '.$db->table('product');
    */
} else {
    $get_user_info = 'select `id`,`nickname`,`headimg`,`account`,`mobile` from '.$db->table('member').' where `id`='.$parent_id;
    $user = $db->fetchRow($get_user_info);

    $get_product_sns = 'select `product_sn` from '.$db->table('distribution').' where `account`=\''.$user['account'].'\'';
    $product_sns = $db->fetchAll($get_product_sns);

    if($product_sns) {
        $_product_sns = array();
        foreach($product_sns as $_product_sn) {
            array_push($_product_sns, $_product_sn['product_sn']);
        }

        $product_sns = $_product_sns;
        $product_sn_str = implode('\', \'', $product_sns);
        $get_product_list = 'select `shop_price`,`given_integral`,`name`,if(`promote_end`>' . time() . ',`promote_price`,`price`) as `price`,`id`,`img` from ' . $db->table('product') .
                            ' where `product_sn` in (\'' . $product_sn_str . '\') and not exists (select `product_sn` from '.$db->table('activity_mapper').' where `activity_id`=1 and `product_sn`='.$db->table('product').'.`product_sn`)'.
                            ' order by `order_view` ASC';
    }
}

$business = array(
    'shop_name' => $user['nickname'],
    'shop_logo' => $user['headimg']
);

assign('user_info', $user);
assign('business', $business);
//获取平台分类
$get_category = 'select `id`,`name` from '.$db->table('category').' where `business_account`=\'\'';
$category = $db->fetchAll($get_category);
foreach($category as $key=>$c)
{
    $get_children = 'select `id`,`name` from '.$db->table('category').' where `parent_id`='.$c['id'];

    $category[$key]['children'] = $db->fetchAll($get_children);
}

//获取轮播广告
$get_cycle_ad = 'select `img`,`url` from '.$db->table('ad').' where `ad_pos_id`=1 order by `order_view`';
$cycle_ad = $db->fetchAll($get_cycle_ad);
assign('cycle_ad', $cycle_ad);

$now = time();
assign('category', $category);
//获取商家全部产品
if($get_product_list != '') {
    $product_list = $db->fetchAll($get_product_list);
    assign('product_list', $product_list);
} else {
    assign('product_list', null);
}
//获取新添加的产品
$get_product_list = 'select `shop_price`,`given_integral`,`name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`id`,`img` from '.$db->table('product').' where `add_time`>'.(time()-3600*24*7).' order by `order_view` ASC';
$new_product = $db->fetchAll($get_product_list);
assign('new_product_count', count($new_product));

//获取猜你喜欢
$get_fav_products = 'select `shop_price`,`given_integral`,`name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`img`,`id` from '.$db->table('product').
                    ' where not exists (select `product_sn` from '.$db->table('activity_mapper').' where `activity_id`=1 and `product_sn`='.$db->table('product').'.`product_sn`) order by `order_view` ASC limit 3';
$fav_products = $db->fetchAll($get_fav_products);
assign('fav_products', $fav_products);

$smarty->display('distribution_shop.phtml');