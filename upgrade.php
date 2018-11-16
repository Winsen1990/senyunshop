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
$sql[] = 'alter table '.$db->table('cart').' add `is_gift` tinyint(1) not null default \'0\' comment \'是否赠品：0 - 否，1 - 是\'';
$sql[] = 'alter table '.$db->table('cart').' add `relate_id` bigint not null default \'0\' comment \'赠品依赖产品\'';
$sql[] = 'alter table '.$db->table('cart').' add `relate_remark` varchar(255) comment \'赠品依据\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `is_gift` tinyint(1) not null default \'0\' comment \'是否赠品：0 - 否，1 - 是\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `relate_id` bigint not null default \'0\' comment \'赠品依赖产品\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `relate_remark` varchar(255) comment \'赠品依据\'';
$sql[] = 'alter table '.$db->table('admin').' drop `business_account`';
$sql[] = 'alter table '.$db->table('admin').' add `openid` varchar(255) comment \'会员OpenId\'';
$sql[] = 'alter table '.$db->table('order').' add `coupon_sn` varchar(255) comment \'优惠券号\'';
$sql[] = 'alter table '.$db->table('order').' add `coupon_name` varchar(255) comment \'优惠名称\'';
$sql[] = 'alter table '.$db->table('order').' add `coupon_reduce` decimal(18,3) not null default \'0\' comment \'优惠券减免金额\'';

while($_sql = array_shift($sql)) {
    $db->query($_sql);
}