<?php
/**
 * Created by PhpStorm.
 * User: airpl
 * Date: 2018/11/12
 * Time: 12:10
 */
/**
 * 更新数据库
 */
include 'library/init.inc.php';
global $db, $loader;
header('Content-Type: text/html;charset=utf-8');

$sql = [];
/*
$sql[] = 'alter table '.$db->table('cart').' add `is_gift` tinyint(1) not null default \'0\' comment \'是否赠品：0 - 否，1 - 是\'';
$sql[] = 'alter table '.$db->table('cart').' add `relate_id` bigint not null default \'\' comment \'赠品依赖产品\'';
$sql[] = 'alter table '.$db->table('cart').' add `relate_remark` varchar(255) comment \'赠品依据\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `is_gift` tinyint(1) not null default \'0\' comment \'是否赠品：0 - 否，1 - 是\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `relate_id` bigint not null default \'\' comment \'赠品依赖产品\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `relate_remark` varchar(255) comment \'赠品依据\'';
$sql[] = 'alter table '.$db->table('admin').' drop `business_account`';
$sql[] = 'alter table '.$db->table('admin').' add `openid` varchar(255) comment \'会员OpenId\'';
$sql[] = 'alter table '.$db->table('order').' add `coupon_sn` varchar(255) comment \'优惠券号\'';
$sql[] = 'alter table '.$db->table('order').' add `coupon_name` varchar(255) comment \'优惠名称\'';
$sql[] = 'alter table '.$db->table('order').' add `coupon_reduce` decimal(18,3) not null default \'0\' comment \'优惠券减免金额\'';
*/
$sql[] = 'alter table '.$db->table('cart').' add `discount` int not null default \'100\' comment \'折扣\'';
$sql[] = 'alter table '.$db->table('cart').' add `discount_reduce` decimal(18,2) not null default \'0\' comment \'折扣减免\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `discount` int not null default \'100\' comment \'折扣\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `discount_reduce` decimal(18,2) not null default \'0\' comment \'折扣减免\'';
$sql[] = 'alter table '.$db->table('order').' add `discount` int not null default \'100\' comment \'订单折扣\'';
$sql[] = 'alter table '.$db->table('order').' add `discount_reduce` decimal(18,2) not null default \'0\' comment \'订单减免\'';
$sql[] = 'alter table '.$db->table('member').' add `birthday` datetime comment \'生日\'';
$sql[] = 'alter table '.$db->table('member').' add `level_expired` datetime comment \'会员等级过期时间\'';
$sql[] = 'alter table '.$db->table('member').' add `experience` decimal(18,3) not null default \'0\' comment \'升级经验\'';
$sql[] = 'alter table '.$db->table('comment').' add `nickname` varchar(255) comment \'虚拟用户昵称\'';
$sql[] = 'alter table '.$db->table('comment').' add `avatar` varchar(255) comment \'虚拟用户头像\'';
$sql[] = 'alter table '.$db->table('level').' add `expired` int not null default \'-1\' comment \'等级过期时间, 单位秒\'';
$sql[] = 'alter table '.$db->table('level').' add `birthday_integral_rate` decimal(18,3) not null default \'1\' comment \'生日当天积分倍数\'';
$sql[] = 'alter table '.$db->table('level').' add `recharge_discount` int not null default \'100\' comment \'充值折扣\'';
$sql[] = 'alter table '.$db->table('level').' add `birthday_given_integral` decimal(18,3) not null default \'0\' comment \'生日当天赠送积分\'';

while($_sql = array_shift($sql)) {
    $db->query($_sql);
}