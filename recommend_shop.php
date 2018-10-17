<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/22
 * Time: 上午7:17
 */
include 'library/init.inc.php';

global $db, $log, $config;

$uid = trim(getGET('ukey'));
$uid = intval($uid);
$log->record('get ukey='.$uid);
if($uid <= 0) {
    redirect('distribution_shop.php');
}

//获取用户信息
$get_user = 'select * from '.$db->table('member').' where `id`='.$uid;
$user = $db->fetchRow($get_user);

$get_product_list = '';

$business = array(
    'shop_name' => $config['site_name'],
    'shop_logo' => 'themes/sbx/images/logo.jpg'
);

if($user && $user['level_id'] > 0) {
    $shop_config = $db->fetchRow('select * from '.$db->table('member_shop').' where `account`=\''.$user['account'].'\'');

    if($shop_config) {
        if($shop_config['name'] != '') {
            $business['shop_name'] = $shop_config['name'];
        }

        if($shop_config['logo'] != '') {
            $business['shop_logo'] = $shop_config['logo'];
        }
    }

    $get_product_sns = 'select `product_sn` from '.$db->table('distribution').' where `account`=\''.$user['account'].'\'';
    $product_sns = $db->fetchAll($get_product_sns);

    if($product_sns) {
        $_product_sns = array();
        foreach($product_sns as $_product_sn) {
            array_push($_product_sns, $_product_sn['product_sn']);
        }

        $product_sns = $_product_sns;
        $product_sn_str = implode('\', \'', $product_sns);
        $get_product_list = 'select `name`,if(`promote_end`>' . time() . ',`promote_price`,`price`) as `price`,`id`,`img` from ' . $db->table('product') . ' where `product_sn` in (\'' . $product_sn_str . '\') order by `order_view` ASC';
    }
} else {
    $log->record('recommend user not exists or level_id is lower than 1');
    redirect('distribution_shop.php');
}

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
$get_product_list = 'select `name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`id`,`img` from '.$db->table('product').' where `add_time`>'.(time()-3600*24*7).' order by `order_view` ASC';
$new_product = $db->fetchAll($get_product_list);
assign('new_product_count', count($new_product));

//获取猜你喜欢
$get_fav_products = 'select `shop_price`,`given_integral`,`name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`img`,`id` from '.$db->table('product').' order by `order_view` ASC limit 3';
$fav_products = $db->fetchAll($get_fav_products);
assign('fav_products', $fav_products);

$smarty->display('distribution_shop.phtml');