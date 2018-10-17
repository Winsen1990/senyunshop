<?php
/**
 * 首页
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:11
 */
include 'library/init.inc.php';

//获取用户信息
$get_user_info = 'select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user_info = $db->fetchRow($get_user_info);
assign('user_info', $user_info);

//获取上线联系方式
if( $user_info && $user_info['parent_id'] ) {
    $get_parent_mobile = 'select `mobile` from '.$db->table('member').' where id = \''.$user_info['parent_id'].'\'';
    $parent_mobile = $db->fetchOne($get_parent_mobile);
} else {
    $parent_mobile = '';
}
assign('parent_mobile', $parent_mobile);

//获取公告
$get_notice = 'select `id`,`title` from '.$db->table('content').' where `section_id`=23 and `status`=1 order by `add_time` DESC limit 5';
$notice = $db->fetchAll($get_notice);
assign('notice', $notice);

//获取轮播广告
$get_cycle_ad = 'select `img`,`url` from '.$db->table('ad').' where `ad_pos_id`=1 order by `order_view`';
$cycle_ad = $db->fetchAll($get_cycle_ad);
assign('cycle_ad', $cycle_ad);

//获取展示广告
$get_perform_ad = 'select `img`,`url`,`alt` from '.$db->table('ad').' where `ad_pos_id`=2 order by `order_view`';
$perform_ad = $db->fetchAll($get_perform_ad);
assign('perform_ad', $perform_ad);

//获取猜你喜欢
$now = time();
$get_fav_products = 'select `shop_price`,`given_integral`,`name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`img`,`id` from '.$db->table('product').
                    ' where `status`=4 order by `order_view` ASC limit 3';
$fav_products = $db->fetchAll($get_fav_products);
assign('fav_products', $fav_products);

$smarty->display('index.phtml');
