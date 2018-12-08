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
$sql[] = 'alter table '.$db->table('cart').' add `img` varchar(255) not null comment \'产品图片\'';
$sql[] = 'alter table '.$db->table('cart').' add `name` varchar(255) not null comment \'产品名称\'';
$sql[] = 'alter table '.$db->table('cart').' add `weight` int not null default \'0\' comment \'产品重量\'';
$sql[] = 'alter table '.$db->table('cart').' add `product_id` bigint not null comment \'产品ID\'';
$sql[] = 'alter table '.$db->table('cart').' add `free_delivery` tinyint(1) not null default \'0\' comment \'包邮：0 - 否，1 - 是\'';
$sql[] = 'alter table '.$db->table('cart').' add `special_price` tinyint(1) default \'0\' comment \'特殊价格：0 - 否，1 - 是\'';
$sql[] = 'alter table '.$db->table('cart').' drop `business_account`';
$sql[] = 'alter table '.$db->table('cart').' drop `openid`';
$sql[] = 'delete from '.$db->table('cart').' as c where not exists (
    select p.`id` from '.$db->table('product').' as p where p.`product_sn`=c.`product_sn`
)';
$sql[] = 'update '.$db->table('cart').' as c left join '.$db->table('product').' as p using(`product_sn`) '.
         ' set c.`img`=p.`img`,c.`name`=p.`name`,c.`free_delivery`=p.`free_delivery`,c.`product_id`=p.`id`,c.`weight`=p.`weight`';
$sql[] = 'alter table '.$db->table('cart').' add unique(`product_sn`,`account`)';

$sql[] = 'alter table '.$db->table('order_detail').' add `is_gift` tinyint(1) not null default \'0\' comment \'是否赠品：0 - 否，1 - 是\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `relate_id` bigint not null default \'0\' comment \'赠品依赖产品\'';
$sql[] = 'alter table '.$db->table('order_detail').' add `relate_remark` varchar(255) comment \'赠品依据\'';

$sql[] = 'alter table '.$db->table('admin').' drop `business_account`';
$sql[] = 'alter table '.$db->table('admin').' add `openid` varchar(255) comment \'会员OpenId\'';

$sql[] = 'alter table '.$db->table('order').' add `coupon_sn` varchar(255) comment \'优惠券号\'';
$sql[] = 'alter table '.$db->table('order').' add `coupon_name` varchar(255) comment \'优惠名称\'';
$sql[] = 'alter table '.$db->table('order').' add `coupon_reduce` decimal(18,3) not null default \'0\' comment \'优惠券减免金额\'';

$sql[] = 'alter table '.$db->table('address').' add `province_name` varchar(255) not null comment \'省份名称\'';
$sql[] = 'alter table '.$db->table('address').' add `city_name` varchar(255) not null comment \'城市名称\'';
$sql[] = 'alter table '.$db->table('address').' add `district_name` varchar(255) not null comment \'行政区名称\'';
$sql[] = 'alter table '.$db->table('address').' add `group_name` varchar(255) not null comment \'商圈名称\'';
$sql[] = 'update '.$db->table('address').' as a left join '.$db->table('province').' as p on p.`id`=a.`province` set a.`province_name`=p.`province_name`';
$sql[] = 'update '.$db->table('address').' as a left join '.$db->table('city').' as c on c.`id`=a.`city` set a.`city_name`=c.`city_name`';
$sql[] = 'update '.$db->table('address').' as a left join '.$db->table('district').' as d on d.`id`=a.`district` set a.`district_name`=d.`district_name`';
$sql[] = 'update '.$db->table('address').' as a left join '.$db->table('group').' as g on g.`id`=a.`group` set a.`group_name`=g.`group_name`';

while($_sql = array_shift($sql)) {
    $db->query($_sql);
}
echo 'done!';