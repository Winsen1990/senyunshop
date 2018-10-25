<?php
/**
 * 初始化数据库
 */
include 'library/init.inc.php';

header('Content-Type: text/html;charset=utf-8');
$table = array();
$sql = array();

$table[] = '产品分类';
$sql[] = 'create table if not exists '.$db->table('category').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null,
    `business_account` varchar(255) not null,
    `parent_id` int not null,
    `path` varchar(255),
    `price_filter` int not null default \'3\',
    `icon` varchar(255),
    `search_brand` tinyint(1) not null default \'1\'
) default charset=utf8;';

$table[] = '产品类型';
$sql[] = 'create table if not exists '.$db->table('product_type').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null
) default charset=utf8;';

$table[] = '产品类型属性';
$sql[] = 'create table if not exists '.$db->table('product_attributes').' (
    `id` bigint not null auto_increment primary key,
    `product_type_id` int not null,
    `type` varchar(255) not null,
    `options` text,
    `name` varchar(255) not null,
    `needle` tinyint(1) not null default \'1\',
    `filter` tinyint(1) not null default \'0\'
) default charset=utf8;';

$table[] = '产品相册';
$sql[] = 'create table if not exists '.$db->table('gallery').' (
    `id` bigint not null auto_increment primary key,
    `big_img` varchar(255) not null,
    `thumb_img` varchar(255) not null,
    `original_img` varchar(255) not null,
    `product_sn` varchar(255) not null,
    `order_view` int not null,
    index (`product_sn`)
) default charset=utf8;';

$table[] = '产品品牌';
$sql[] = 'create table if not exists '.$db->table('brand').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null,
    `img` varchar(255) not null,
    `desc` text
) default charset=utf8;';

$table[] = '库存';
$sql[] = 'create table if not exists '.$db->table('inventory').' (
    `id` bigint not null auto_increment primary key,
    `product_sn` varchar(255) not null,
    `attributes` varchar(255) not null,
    `inventory` int not null default \'0\',
    `inventory_await` int not null default \'0\',
    `inventory_logic` int not null default \'0\',
    index (`product_sn`),
    index (`attributes`)
) default charset=utf8;';

$table[] = '产品评价';
$sql[] = 'create table if not exists '.$db->table('comment').' (
    `id` bigint not null auto_increment primary key,
    `add_time` int not null,
    `comment` varchar(255) not null,
    `star` int not null default \'5\',
    `img` varchar(255),
    `product_sn` varchar(255) not null,
    `account` varchar(255) not null,
    `last_modify` timestamp,
    `path` varchar(255),
    `parent_id` int not null default \'0\'
) default charset=utf8;';

$table[] = '我的足迹';
$sql[] = 'create table if not exists '.$db->table('history').' (
    `product_sn` varchar(20) not null,
    `account` varchar(20) not null,
    `add_time` timestamp,
    primary key (`product_sn`,`account`)
) default charset=utf8;';

$table[] = '产品收藏';
$sql[] = 'create table if not exists '.$db->table('collection').' (
    `product_sn` varchar(255) not null,
    `account` varchar(255) not null,
    `add_time` int not null,
    primary key (`product_sn`,`account`)
) default charset=utf8;';

$table[] = '产品分销';
$sql[] = 'create table if not exists '.$db->table('distribution').' (
    `product_sn` varchar(255) not null,
    `account` varchar(255) not null,
    `add_time` int not null,
    primary key (`product_sn`,`account`)
) default charset=utf8;';

$table[] = '砍价记录';
$sql[] = 'create table if not exists '.$db->table('discount').' (
    `id` bigint not null auto_increment primary key,
    `product_sn` varchar(255) not null,
    `account` varchar(255) not null,
    `reduce` decimal(18,2) not null,
    `owner` varchar(255) not null,
    `add_time` int not null,
    index (`product_sn`),
    index (`owner`)
) default charset=utf8;';

$table[] = '提现申请';
$sql[] = 'create table if not exists '.$db->table('withdraw').' (
    `withdraw_sn` varchar(255) not null primary key,
    `account` varchar(255) not null,
    `amount` decimal(18,2) not null,
    `fee` decimal(18,2) not null,
    `status` int not null default \'0\',
    `add_time` int not null,
    `solve_time` int,
    `remark` varchar(255),
    `bank` varchar(255) not null,
    `bank_account` varchar(255) not null,
    `bank_card` varchar(255) not null
) default charset=utf8;';

$table[] = '提现操作记录';
$sql[] = 'create table if not exists '.$db->table('withdraw_log').' (
    `id` bigint not null auto_increment primary key,
    `add_time` int not null,
    `operator` varchar(255) not null,
    `withdraw_sn` varchar(255) not null,
    `status` int not null,
    `remark` varchar(255),
    index (`withdraw_sn`)
) default charset=utf8;';

$table[] = '商家提现申请';
$sql[] = 'create table if not exists '.$db->table('business_withdraw').' (
    `withdraw_sn` varchar(255) not null primary key,
    `business_account` varchar(255) not null,
    `amount` decimal(18,2) not null,
    `fee` decimal(18,2) not null,
    `status` int not null default \'0\',
    `add_time` int not null,
    `solve_time` int,
    `remark` varchar(255),
    `bank` varchar(255) not null,
    `bank_account` varchar(255) not null,
    `bank_card` varchar(255) not null
) default charset=utf8;';

$table[] = '商家提现操作记录';
$sql[] = 'create table if not exists '.$db->table('business_withdraw_log').' (
    `id` bigint not null auto_increment primary key,
    `add_time` int not null,
    `operator` varchar(255) not null,
    `withdraw_sn` varchar(255) not null,
    `status` int not null,
    `remark` varchar(255),
    index (`withdraw_sn`)
) default charset=utf8;';

$table[] = '商家';
$sql[] = 'create table if not exists '.$db->table('business').' (
    `id` bigint not null auto_increment unique,
    `business_account` varchar(255) not null primary key,
    `password` varchar(255) not null,
    `license` varchar(255) not null,
    `identity` varchar(255) not null,
    `company` varchar(255) not null,
    `industry_id` int not null,
    `category_id` int not null,
    `province` int not null,
    `city` int not null,
    `district` int not null,
    `group` int not null,
    `address` varchar(255) not null,
    `desc` varchar(255),
    `status` int not null,
    `contact` varchar(255) not null,
    `mobile` varchar(255) not null,
    `email` varchar(255) not null,
    `shop_name` varchar(255),
    `shop_logo` varchar(255),
    `shop_sign` varchar(255),
    `comment` int not null,
    `is_recommend` tinyint(1) not null default \'0\',
    `balance` decimal(18,2) not null default \'0\',
    `trade` decimal(18,2) not null default \'0\',
    `longitude` varchar(255),
    `latitude` varchar(255),
    `expired` int not null default \'0\',
    `account` varchar(255) not null,
    `product_authorization` tinyint(1) not null default \'1\' comment \'产品上传需要审核 0-不需要 1-需要\'
) default charset=utf8;';

$table[] = '商家交易';
$sql[] = 'create table if not exists '.$db->table('trade').' (
    `id` bigint not null auto_increment primary key,
    `business_account` varchar(255) not null,
    `trade` decimal(18,2) not null,
    `status` int not null default \'0\',
    `remark` varchar(255),
    `settle_time` int not null,
    `solve_time` int,
    index (`business_account`)
) default charset=utf8;';

$table[] = '商家明细';
$sql[] = 'create table if not exists '.$db->table('business_exchange_log').' (
    `id` bigint not null auto_increment primary key,
    `business_account` varchar(255) not null,
    `balance` decimal(18,2) not null default \'0\',
    `trade` decimal(18,2) not null default \'0\',
    `add_time` int not null,
    `remark` varchar(255),
    `operator` varchar(255) not null,
    index (`business_account`)
) default charset=utf8;';

$table[] = '会员账户明细';
$sql[] = 'create table if not exists '.$db->table('member_exchange_log').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null,
    `add_time` int not null,
    `balance` decimal(18,2) not null default \'0\',
    `reward` decimal(18,2) not null default \'0\',
    `integral` decimal(18,2) not null default \'0\',
    `integral_await` decimal(18,2) not null default \'0\',
    `reward_await` decimal(18,2) not null default \'0\',
    `operator` varchar(255) not null,
    `remark` varchar(255)
) default charset=utf8;';

$table[] = '会员';
$sql[] = 'create table if not exists '.$db->table('member').' (
    `id` bigint not null auto_increment unique,
    `account` varchar(255) not null primary key,
    `mobile` varchar(255),
    `password` varchar(255),
    `openid` varchar(255),
    `sex` char(2),
    `nickname` varchar(255) character set utf8mb4,
    `province` varchar(255),
    `city` varchar(255),
    `headimg` varchar(255),
    `add_time` int not null,
    `leave_time` int,
    `status` int not null default \'1\',
    `longitude` varchar(255),
    `latitude` varchar(255),
    `unionid` varchar(255),
    `email` varchar(255),
    `integral` decimal(18,2) not null default \'0\',
    `reward` decimal(18,2) not null default \'0\',
    `balance` decimal(18,2) not null default \'0\',
    `reward_await` decimal(18,2) not null default \'0\',
    `integral_await` decimal(18,2) not null default \'0\',
    `integral_amount` decimal(18,2) not null default \'0\',
    `reward_amount` decimal(18,2) not null default \'0\',
    `parent_id` int not null default \'0\',
    `path` varchar(255),
    `business_account` varchar(255),
    `ticket` varchar(255),
    `expired` int not null default \'0\',
    `scene_id` int not null default \'0\',
    `level_id` int not null default \'0\',
    `kf_id` int not null default \'0\',
    `is_subscribe` int not null default \'1\'
) default charset=utf8;';

$table[] = '会员扩展信息';
$sql[] = 'create table if not exists '.$db->table('member_info').' (
    `account` varchar(255) not null primary key,
    `real_name` varchar(255)
) engine=InnoDB default charset=utf8;';

$table[] = '订单';
$sql[] = 'create table if not exists '.$db->table('order').' (
    `id` bigint not null auto_increment unique,
    `order_sn` varchar(255) not null primary key,
    `add_time` int not null,
    `delivery_time` int,
    `receive_time` int,
    `status` int not null default \'0\',
    `amount` decimal(18,2) not null,
    `integral_amount` decimal(18,2) not null,
    `delivery_id` int not null,
    `delivery_name` varchar(255),
    `business_account` varchar(255) not null,
    `delivery_fee` decimal(18,2) not null,
    `product_amount` decimal(18,2) not null,
    `integral_given_amount` decimal(18,2) not null,
    `payment_id` int not null,
    `payment_name` varchar(255),
    `payment_sn` varchar(255),
    `express_id` int,
    `express_sn` varchar(255),
    `remark` varchar(255),
    `address_id` int not null default \'0\',
    `province` int not null,
    `city` int not null,
    `district` int not null,
    `group` int not null,
    `address` varchar(255) not null,
    `consignee` varchar(255) not null,
    `mobile` varchar(255) not null,
    `zipcode` varchar(255),
    `reward_amount` decimal(18,2) not null,
    `account` varchar(255) not null,
    `self_delivery` tinyint(1) not null default \'0\',
    `pay_time` int,
    `is_comment` tinyint(1) not null default \'0\',
    `integral_paid` decimal(18,2) not null default \'0\',
    `reward_paid` decimal(18,2) not null default \'0\',
    `balance_paid` decimal(18,2) not null default \'0\',
    `is_virtual` tinyint not null default \'0\' comment \'0:实体产品订单，1:虚拟产品订单\',
    `given_integral_amount` decimal(18,2) not null default \'0\',
    index (`business_account`)
) default charset=utf8;';

$table[] = '订单操作记录';
$sql[] = 'create table if not exists '.$db->table('order_log').' (
    `id` bigint not null auto_increment primary key,
    `order_sn` varchar(255) not null,
    `operator` varchar(255) not null,
    `status` int not null,
    `add_time` int not null,
    `remark` varchar(255)
) default charset=utf8;';

$table[] = '配送方式';
$sql[] = 'create table if not exists '.$db->table('delivery').' (
    `id` bigint not null auto_increment primary key,
    `self_delivery` tinyint(1) not null default \'0\',
    `name` varchar(255) not null,
    `business_account` varchar(255) not null,
    `plugins` varchar(255) not null,
    `desc` varchar(255),
    `status` int not null default \'0\',
    index (`business_account`)
) default charset=utf8;';

$table[] = '配送区域';
$sql[] = 'create table if not exists '.$db->table('delivery_area').' (
    `id` bigint not null auto_increment primary key,
    `first_weight` decimal(18,2) not null,
    `next_weight` decimal(18,2) not null,
    `free` decimal(18,2) not null default \'0\',
    `delivery_id` int not null,
    `name` varchar(255),
    `business_account` varchar(255) not null,
    index (`delivery_id`)
) default charset=utf8;';

$table[] = '配送区域映射';
$sql[] = 'create table if not exists '.$db->table('delivery_area_mapper').' (
    `id` bigint not null auto_increment primary key,
    `area_id` int not null,
    `province` int not null,
    `city` int not null,
    `district` int not null,
    `business_account` varchar(255) not null
) default charset=utf8;';

$table[] = '支付方式';
$sql[] = 'create table if not exists '.$db->table('payment').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null,
    `plugins` varchar(255) not null,
    `configure` text,
    `desc` varchar(255),
    `status` int not null default \'0\'
) default charset=utf8;';

$table[] = '广告位置';
$sql[] = 'create table if not exists '.$db->table('ad_position').' (
    `id` bigint not null auto_increment primary key,
    `pos_name` varchar(255) not null,
    `width` varchar(255) not null,
    `height` varchar(255) not null,
    `number` int not null default \'1\',
    `code` text
) default charset=utf8;';

$table[] = '初始化广告位数据';
$sql[] = 'insert into '.$db->table('ad_position').' (`id`, `pos_name`, `width`, `height`, `number`, `code`) values
 (\'1\', \'首页轮播广告\', \'320\', \'140\', \'5\', \'\'),
 (\'2\', \'首页展示广告\', \'320\', \'128\', \'3\', \'\'),
 (\'3\', \'商家轮播\', \'320\', \'140\', \'3\', \'\'),
 (\'4\', \'PC端网站首页大广告\', \'788\', \'328\', \'5\', \'\');';

$table[] = '广告';
$sql[] = 'create table if not exists '.$db->table('ad').' (
    `id` bigint not null auto_increment primary key,
    `img` varchar(255) not null,
    `alt` varchar(255) not null,
    `forever` tinyint(1) not null default \'1\',
    `click_time` int not null default \'0\',
    `url` varchar(255) not null,
    `order_view` int not null default \'50\',
    `ad_pos_id` int not null,
    `begin_time` int,
    `end_time` int,
    `add_time` int not null,
    `business_account` varchar(255) not null default \'\'
) default charset=utf8;';

$table[] = '管理员';
$sql[] = 'create table if not exists '.$db->table('admin').' (
    `account` varchar(255) not null primary key,
    `password` varchar(255) not null,
    `email` varchar(255) not null,
    `name` varchar(255) not null,
    `role_id` int not null,
    `mobile` varchar(255) not null,
    `sex` char(2) not null,
    `business_account` varchar(255) not null,
    index (`business_account`)
) default charset=utf8;';

$table[] = '角色';
$sql[] = 'create table if not exists '.$db->table('role').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null,
    `purview` text not null,
    `business_account` varchar(255) not null,
    index (`business_account`)
) default charset=utf8;';

$table[] = '站点参数';
$sql[] = 'create table if not exists '.$db->table('sysconf').' (
    `key` varchar(255) not null primary key,
    `name` varchar(255) not null,
    `value` varchar(255),
    `type` varchar(255) not null,
    `options` text,
    `group` varchar(255) not null,
    `remark` varchar(255) not null
) default charset=utf8;';

$table[] = '购物车';
$sql[] = 'create table if not exists '.$db->table('cart').' (
    `id` bigint not null auto_increment primary key,
    `openid` varchar(255) not null,
    `account` varchar(255),
    `product_sn` varchar(255) not null,
    `price` decimal(18,2) not null,
    `integral` decimal(18,2) not null,
    `number` int not null,
    `business_account` varchar(255) not null,
    `add_time` int not null,
    `attributes` varchar(255),
    `checked` tinyint(1) not null default \'1\',
    `is_virtual` tinyint(1) not null default \'0\',
    index (`openid`),
    index (`account`),
    index (`business_account`)
) default charset=utf8;';

$table[] = '支付记录';
$sql[] = 'create table if not exists '.$db->table('pay_log').' (
    `pay_sn` varchar(255) not null primary key,
    `amount` decimal(18,2) not null,
    `status` int not null,
    `order_assoc` varchar(255) not null,
    `id` bigint not null auto_increment unique,
    `pay_time` int,
    `add_time` int not null
) default charset=utf8;';

$table[] = '会员佣金';
$sql[] = 'create table if not exists '.$db->table('member_reward').' (
    `id` bigint not null auto_increment unique,
    `remark` varchar(255),
    `integral` decimal(18,2) not null default \'0\',
    `reward` decimal(18,2) not null default \'0\',
    `status` int not null,
    `settle_time` int not null,
    `solve_time` int,
    `account` varchar(255) not null
) default charset=utf8;';

$table[] = '产品';
$sql[] = 'create table if not exists '.$db->table('product').' (
    `id` bigint not null auto_increment unique,
    `product_sn` varchar(255) not null primary key,
    `name` varchar(255) not null,
    `shop_price` decimal(18,2) not null,
    `price` decimal(18,2) not null,
    `lowest_price` decimal(18,2) not null,
    `reward` decimal(18,2) not null,
    `integral` decimal(18,2) not null,
    `integral_given` decimal(18,2) not null,
    `given_integral` decimal(18,2) not null default \'0\',
    `img` varchar(255) not null,
    `desc` varchar(255),
    `detail` text,
    `business_account` varchar(255) not null,
    `category_id` int not null,
    `shop_category_id` int not null,
    `product_type_id` int not null,
    `status` int not null default \'0\',
    `prev_status` int not null default \'0\',
    `promote_price` decimal(18,2) not null,
    `promote_begin` int,
    `promote_end` int,
    `add_time` int not null,
    `weight` int not null,
    `collection_count` int not null default \'0\',
    `comment_count` int not null default \'0\',
    `star` int not null default \'0\',
    `distribution_count` int not null default \'0\',
    `brand_id` int not null,
    `up` int not null default \'0\',
    `share_count` int not null default \'0\',
    `sale_count` int not null default \'0\',
    `order_view` int not null default \'50\',
    `free_delivery` tinyint(1) not null default \'0\',
    `is_virtual` tinyint(1) not null default \'0\' comment \'0:实体产品，1:虚拟产品\'
) default charset=utf8;';

$table[] = '短链接';
$sql[] = 'create table if not exists '.$db->table('short_link').' (
    `url` varchar(255) not null primary key,
    `hash` varchar(255) not null unique
) default charset=utf8;';


$table[] = '省';
$sql[] = 'create table if not exists '.$db->table('province').' (
    `id` int not null auto_increment primary key,
    `province_name` varchar(255) not null
) default charset=utf8;';

$table[] = '市';
$sql[] = 'create table if not exists '.$db->table('city').' (
    `id` int not null auto_increment primary key,
    `city_name` varchar(255) not null,
    `province_id` int not null,
    `first_letter` char(1) not null,
    `is_hot` tinyint not null default 0,
    `code` varchar(255) not null
) default charset=utf8;';

$table[] = '区';
$sql[] = 'create table if not exists '.$db->table('district').' (
    `id` int not null auto_increment primary key,
    `district_name` varchar(255) not null,
    `city_id` int not null,
    `code` varchar(255) not null
) default charset=utf8;';

$table[] = '商圈';
$sql[] = 'create table if not exists '.$db->table('group').' (
    `id` int not null auto_increment primary key,
    `group_name` varchar(255) not null,
    `district_id` int not null,
    `city_id` int not null,
    `code` varchar(255) not null,
    `first_letter` char(1) not null
) default charset=utf8;';

$table[] = '主营行业';
$sql[] = 'create table if not exists '.$db->table('industry').' (
    `id` int not null auto_increment primary key,
    `name` varchar(255) not null
) default charset=utf8;';

$table[] = '主营分类';
$sql[] = 'create table if not exists '.$db->table('classification').' (
    `id` int not null auto_increment primary key,
    `name` varchar(255) not null
) default charset=utf8;';

$table[] = '商家认证信息';
$sql[] = 'create table if not exists '.$db->table('auth').' (
    `id` int not null auto_increment primary key,
    `business_account` varchar(255) not null,
    `company` varchar(255) not null,
    `desc` text not null,
    `license` varchar(255) not null,
    `identity` varchar(255) not null,
    `industry_id` int not null,
    `category_id` int not null,
    `contact` varchar(255) not null,
    `mobile` varchar(255) not null,
    `email` varchar(255) not null,
    `add_time` int not null,
    `status` tinyint not null default 0
) default charset=utf8;';

$table[] = '订单详情';
$sql[] = 'create table if not exists '.$db->table('order_detail').' (
    `id` int not null auto_increment primary key,
    `order_sn` varchar(255) not null,
    `product_sn` varchar(255) not null,
    `product_name` varchar(255) not null,
    `product_attributes` varchar(255) not null,
    `attributes` varchar(255),
    `product_price` decimal(18,2) not null,
    `integral` decimal(18,2) not null,
    `integral_given` decimal(18,2) not null,
    `reward` decimal(18,2) not null,
    `count` int not null,
    `business_account` varchar(255) not null,
    `is_virtual` tinyint(1) not null default \'0\',
    `given_integral` decimal(18, 2) not null default \'0\',
    index(`order_sn`, `product_sn`)
) default charset=utf8;';

$table[] = '收货地址';
$sql[] = 'create table if not exists '.$db->table('address').' (
    `id` int not null auto_increment primary key,
    `province` int not null,
    `city` int not null,
    `district` int not null,
    `group` int not null default 0,
    `address` varchar(255) not null,
    `consignee` varchar(255) not null,
    `mobile` varchar(255) not null,
    `zipcode` varchar(255),
    `account` varchar(255) not null,
    `is_default` tinyint(1) not null default \'0\',
    index(`account`)
) default charset=utf8;';

$table[] = '平台管理员';
$sql[] = 'create table if not exists '.$db->table('platform_admin').' (
    `account` varchar(255) not null primary key,
    `password` varchar(255) not null,
    `name` varchar(255) not null,
    `email` varchar(255) not null,
    `sex` char(2) not null default \'M\',
    `mobile` varchar(255) not null,
    `role_id` int not null
) default charset=utf8;';

$table[] = '平台角色';
$sql[] = 'create table if not exists '.$db->table('platform_role').' (
    `id` int not null auto_increment primary key,
    `name` varchar(255) not null,
    `purview` text not null
) default charset=utf8;';

$table[] = '卡号池';
$sql[] = 'create table if not exists '.$db->table('card_pool').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null,
    `status` int not null default \'1\'
) default charset=utf8;';

$table[] = '银行卡号';
$sql[] = 'create table if not exists '.$db->table('bank_card').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null,
    `bank` varchar(255) not null,
    `bank_account` varchar(255) not null,
    `bank_card` varchar(255) not null,
    `mobile` varchar(255) not null,
    index (`account`)
) default charset=utf8;';

$table[] = '系统消息';
$sql[] = 'create table if not exists '.$db->table('message').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null,
    `business_account` varchar(255) not null,
    `title` varchar(255) not null,
    `content` text not null,
    `status` int not null,
    `add_time` int not null
) default charset=utf8;';

$table[] = '栏目';
$sql[] = 'create table if not exists '.$db->table('section').' (
    `id` int not null auto_increment primary key,
    `section_name` varchar(255) not null,
    `parent_id` int not null default \'0\',
    `path` varchar(255),
    `keywords` varchar(255),
    `description` varchar(255),
    `order_view` int not null default \'50\',
    `thumb` varchar(255),
    `original` varchar(255)
) default charset=utf8;';

$table[] = '内容';
$sql[] = 'create table if not exists '.$db->table('content').' (
    `id` int not null auto_increment primary key,
    `title` varchar(255) not null,
    `author` varchar(255) not null,
    `add_time` int not null,
    `content` text,
    `wap_content` text,
    `last_modify` timestamp,
    `keywords` varchar(255),
    `description` varchar(255),
    `thumb` varchar(255),
    `original` varchar(255),
    `order_view` int not null default \'50\',
    `original_url` varchar(255),
    `section_id` int not null,
    `status` tinyint not null default 1,
    `view_count` int not null default \'0\' comment \'浏览次数\',
    `thumb_up` int not null default \'0\' comment \'点赞次数\',
    `comment_count` int not null default \'0\' comment \'评论数量\'
) default charset=utf8;';

$table[] = '资讯点赞记录';
$sql[] = 'create table if not exists '.$db->table('content_up').' (
    `account` varchar(255) not null comment \'会员账号\',
    `content_id` int not null comment \'评论ID\',
    `add_time` int not null comment \'点赞时间\',
    primary key(`account`, `content_id`)
) engine=InnoDB default charset=utf8;';

$table[] = '资讯评论';
$sql[] = 'create table if not exists '.$db->table('content_comment').' (
    `id` bigint not null auto_increment primary key,
    `path` varchar(255) comment \'评论关系\',
    `account` varchar(255) not null comment \'会员账号\',
    `comment` text not null comment \'评论内容\',
    `add_time` int not null comment \'评论时间\',
    `status` int not null default \'0\' comment \'状态：0 - 待审，1 - 通过，2 - 不通过\',
    `thumb_up` int not null default \'0\' comment \'点赞次数\',
    `top` tinyint(1) not null default \'0\' comment \'置顶：0 - 否，1 - 是\',
    `parent_id` bigint not null default \'0\' comment \'上级评论ID\',
    `content_id` int not null comment \'资讯ID\',
    index(`parent_id`),
    index(`account`),
    index(`content_id`)
) engine=InnoDB default charset=utf8;';

$table[] = '资讯评论点赞记录';
$sql[] = 'create table if not exists '.$db->table('content_comment_up').' (
    `account` varchar(255) not null comment \'会员账号\',
    `content_comment_id` bigint not null comment \'资讯评论ID\',
    `add_time` int not null comment \'点赞时间\',
    primary key(`account`, `content_comment_id`)
) engine=InnoDB default charset=utf8;';

$table[] = '虚拟产品内容';
$sql[] = 'create table if not exists '.$db->table('virtual_content').' (
    `id` int not null auto_increment primary key,
    `product_sn` varchar(255) not null,
    `content` varchar(255) not null,
    `count` varchar(255) not null,
    `total` varchar(255) not null
) default charset=utf8;';

$table[] = '短信验证码池';
$sql[] = 'create table if not exists '.$db->table('message_code').' (
    `id` bigint not null auto_increment unique,
    `mobile` varchar(255) not null primary key,
    `code` varchar(255) not null,
    `expire` int not null
) default charset=utf8;';

$table[] = '虚拟订单内容';
$sql[] = 'create table if not exists '.$db->table('order_content').' (
    `id` int not null auto_increment primary key,
    `order_sn` varchar(255) not null,
    `product_sn` varchar(255) not null,
    `content` varchar(255) not null,
    `count` varchar(255) not null,
    `total` varchar(255) not null
) default charset=utf8;';

$table[] = '充值记录';
$sql[] = 'create table if not exists'.$db->table('recharge').'(
    `recharge_sn` varchar(255) not null primary key comment \'充值编号\',
    `account` varchar(255) not null comment \'帐号\',
    `amount` decimal(18,2) not null comment \'充值金额\',
    `status` tinyint not null default 1 comment \'1：到帐，2：充值中，3：取消\',
    `add_time` int not null comment \'充值时间\',
    `type` tinyint not null default 0 comment \'0:线上，1：线下\',
    `bank` varchar(255) not null default \'\' comment \'银行(type=1有效)\',
    `card_num` varchar(255) not null default \'\' comment \'卡号(type=1有效)\',
    `solve_time` int not null default 0 comment \'处理时间(type=1有效)\'
) default charset=utf8;';

$table[] = '充值日志';
$sql[] = 'create table if not exists'.$db->table('recharge_log').'(
    `id` int not null auto_increment primary key,
    `account` varchar(255) not null,
    `add_time` int not null,
    `operator` varchar(255) not null,
    `recharge_sn` varchar(255) not null,
    `type` tinyint not null comment \'0:线上，1：线下\',
    `status` tinyint not null comment \'0:未到帐，1：到帐\',
    `remark` varchar(255) not null default \'\'
) default charset=utf8;';

$table[] = '导航栏';
$sql[] = 'create table if not exists '.$db->table('nav').' (
    `id` int not null auto_increment primary key,
    `name` varchar(255) not null,
    `url` varchar(255) not null,
    `parent_id` int not null default \'0\',
    `position` varchar(255) not null,
    `order_view` int not null default \'50\'
) default charset=utf8;';

$table[] = '促销活动';
$sql[] = 'create table if not exists '.$db->table('activity').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null
) default charset=utf8;';

$table[] = '初始化促销活动';
$sql[] = 'insert into '.$db->table('activity').' values (1,\'优选产品\'), (2, \'促销专区\'), (3, \'积分兑换\');';

$table[] = '活动产品';
$sql[] = 'create table if not exists '.$db->table('activity_mapper').' (
    `id` bigint not null auto_increment unique,
    `activity_id` bigint not null,
    `product_sn` varchar(255) not null,
    primary key(`activity_id`,`product_sn`)
) default charset=utf8;';

//微信模块
$table[] = '微信菜单';
$sql[] = 'create table if not exists '.$db->table('wx_menu').' (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `key` varchar(255) NOT NULL,
    `type` varchar(255) NOT NULL,
    `parent_id` int(11) NOT NULL DEFAULT \'0\',
    `path` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) default charset=utf8;';

$table[] = '微信客服';
$sql[] = 'create table if not exists '.$db->table('wx_kf').' (
    `id` bigint not null auto_increment unique,
    `kf_account` varchar(255) not null,
    `nickname` varchar(255) not null,
    `password` varchar(255) not null,
    `headimg` varchar(255)
) default charset=utf8;';

$table[] = '回复规则';
$sql[] = 'create table if not exists '.$db->table('wx_rule').' (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `enabled` tinyint(1) NOT NULL DEFAULT \'1\',
    `rule` varchar(255) NOT NULL,
    `match_mode` int(11) NOT NULL DEFAULT \'0\',
    `response_id` int(11) NOT NULL,
    `order_view` int(11) NOT NULL DEFAULT \'50\',
    `is_default` tinyint(1) NOT NULL DEFAULT \'0\',
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) default charset=utf8;';

$table[] = '回复内容';
$sql[] = 'create table if not exists '.$db->table('wx_response').' (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `msgType` varchar(255) NOT NULL,
    `content` text,
    `title` text,
    `description` text,
    `musicUrl` varchar(255) DEFAULT NULL,
    `HQMusicUrl` varchar(255) DEFAULT NULL,
    `url` text,
    `picUrl` text,
    `mediaId` int(11) DEFAULT NULL,
    `thumbMediaId` int(11) DEFAULT NULL,
    PRIMARY KEY (`id`)
) default charset=utf8;';

$table[] = '会员店铺设置';
$sql[] = 'create table if not exists '.$db->table('member_shop').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null unique,
    `name` varchar(255) comment \'店铺名称\',
    `header_bg` varchar(255) comment \'店招\',
    `logo` varchar(255) comment \'店铺logo\',
    `add_time` int not null comment \'创建时间\'
) engine=InnoDB default charset=utf8;';

$table[] = '功能模块';
$sql[] = 'create table if not exists '.$db->table('modules').' (
    `id` bigint not null auto_increment primary key,
    `identity` varchar(255) not null unique,
    `icon` varchar(255),
    `url` varchar(255) not null,
    `name` varchar(255) not null,
    `status` tinyint(1) not null default 0,
    `assoc_type` varchar(255),
    `assoc` varchar(255),
    index(`assoc_type`, `assoc`)
) engine=InnoDB default charset=utf8;';

$table[] = '活动';
$sql[] = 'create table if not exists '.$db->table('business_activity').' (
    `id` bigint not null auto_increment primary key,
    `business_account` varchar(255) not null default \'\' comment \'活动商家，默认为平台\',
    `name` varchar(255) not null comment \'活动名称\',
    `start_time` int not null comment \'活动开始时间\',
    `end_time` int not null comment \'活动结束时间\',
    `apply_start_time` int comment \'报名开始时间\',
    `apply_end_time` int comment \'报名结束时间\',
    `cover` varchar(255) not null comment \'封面\',
    `allow_apply` tinyint(1) not null default \'1\' comment \'允许自主报名\',
    `need_ticket` tinyint(1) not null default \'1\' comment \'需要凭据才能参与\',
    `need_audit` tinyint(1) not null default \'0\' comment \'报名需要审核\',
    `plugin` varchar(255) comment \'活动插件\',
    `params` text comment \'活动设置参数\',
    `status` tinyint(1) not null default \'1\' comment \'活动状态: 0 - 停用, 1 - 启用, 2 - 已结束\',
    `has_reward` tinyint(1) not null default \'1\' comment \'活动是否有奖品\',
    `rule` text comment \'活动规则\',
    `term` int not null default \'1\' comment \'活动期数\'
) engine=InnoDB default charset=utf8;';

$table[] = '活动奖品';
$sql[] = 'create table if not exists '.$db->table('business_activity_reward').' (
    `id` bigint not null auto_increment primary key,
    `activity_id` int not null comment \'活动ID\',
    `title` varchar(255) comment \'奖项名称\',
    `name` varchar(255) not null comment \'奖品名称\',
    `number` int not null default \'0\' comment \'奖品数量\',
    `sort` int not null default \'50\' comment \'排序\',
    `left_number` int not null comment \'剩余数量\',
    `is_virtual` tinyint(1) not null default \'0\' comment \'是否虚拟产品\',
    `assoc_product` int not null default \'0\' comment \'关联产品ID\',
    `integral` decimal(18,3) not null default \'0\' comment \'积分\',
    `total` decimal(18,3) not null default \'0\' comment \'奖品总数量\',
    `unit` varchar(255) not null comment \'单位\'
) engine=InnoDB default charset=utf8;';

$table[] = '活动凭据';
$sql[] = 'create table if not exists '.$db->table('business_activity_ticket').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null comment \'用户账号\',
    `count` int not null comment \'可用次数\',
    `activity_id` int not null comment \'活动ID\',
    `add_time` int not null comment \'生成凭据时间\',
    `consume` int not null default \'0\' comment \'已使用次数\',
    `remark` varchar(255) comment \'备注\',
    `status` int not null default \'1\' comment \'状态: 0 - 失效, 1 - 有效\',
    `expire` int not null comment \'过期时间，-1为永不过期\'
) engine=InnoDB default charset=utf8;';

$table[] = '活动中奖记录';
$sql[] = 'create table if not exists '.$db->table('business_activity_reward_record').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null comment \'用户账号\',
    `activity_id` int not null comment \'活动ID\',
    `status` int not null default \'0\' comment \'状态: 0 - 未兑奖, 1 - 已兑奖, 2 - 已失效\',
    `title` varchar(255) not null comment \'奖项名称\',
    `name` varchar(255) not null comment \'奖品名称\',
    `number` int not null comment \'奖品数量\',
    `unit` varchar(255) not null comment \'奖品单位\',
    `assoc_order` varchar(255) comment \'关联订单\',
    `assoc_product` int comment \'关联产品\',
    `is_virtual` tinyint(1) not null comment \'是否虚拟产品\',
    `integral` decimal(18,3) not null default \'0\' comment \'赠送积分\'
) engine=InnoDB default charset=utf8;';

$table[] = '登录授权口令';
$sql[] = 'create table if not exists '.$db->table('api_token').' (
    `account` varchar(255) not null primary key comment \'用户账号\',
    `add_time` int not null comment \'授权登录时间\',
    `token` varchar(255) not null unique comment \'口令\',
    `expired` int not null comment \'授权过期时间\'
) engine=InnoDB default charset=utf8;';

$table[] = '产品-分类-映射';
$sql[] = 'create table if not exists '.$db->table('product_category_mapper').' (
    `id` bigint not null auto_increment unique,
    `product_sn` varchar(255) not null,
    `category_id` int not null,
    primary key(`product_sn`, `category_id`),
    index(`product_sn`),
    index(`category_id`)
) engine=InnoDB default charset=utf8;';

$table[] = '会员等级';
$sql[] = 'create table if not exists '.$db->table('level').'(
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null comment \'等级名称\',
    `experience` decimal(18, 3) not null default \'0\' comment \'经验值\',
    `experience_type` int not null default \'1\' comment \'经验值类型：1 - 单次消费额，2 - 累计消费额，3 - 累计推广积分\',
    `recommend_count` int not null default \'0\' comment \'推荐人数\',
    `is_special` tinyint(1) not null default \'0\' comment \'特殊等级：0 - 否，1 - 是\',
    `discount` int not null default \'100\' comment \'享受折扣\',
    `remark` varchar(255) comment \'备注\'
) engine=InnoDB default charset=utf8;';

$table[] = '专区';
$sql[] = 'create table if not exists '.$db->table('blocks').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null comment \'专区名称\',
    `status` int not null default \'0\' comment \'状态：0 - 停用，1 - 启用\',
    `url` varchar(255) not null comment \'链接\',
    `cover` varchar(255) not null comment \'封面图\',
    `sort` int not null default \'50\' comment \'排序 升序\'
) engine=InnoDB default charset=utf8;';

$table[] = '关键词';
$sql[] = 'create table if not exists '.$db->table('keywords').' (
    `id` bigint not null auto_increment primary key,
    `keyword` varchar(255) not null unique comment \'关键词\',
    `click_count` int not null default \'0\' comment \'点击次数\',
    `sort` int not null default \'50\' comment \'排序 升序排列\'
) engine=InnoDB default charset=utf8;';

$table[] = '专区-产品映射';
$sql[] = 'create table if not exists '.$db->table('block_product_mapper').' (
    `block_id` bigint not null comment \'专区ID\',
    `product_sn` varchar(255) not null comment \'产品编号\',
    `sort` int not null default \'50\' comment \'排序\',
    primary key(`block_id`, `product_sn`)
) engine=InnoDB default charset=utf8;';

echo '创建数据库表:<br/>';
foreach($table as $key=>$name)
{
    echo $name;

    $dot_count = 40 - mb_strlen($name)*3;
    while($dot_count--)
    {
        echo '-';
    }
    
    if($db->query($sql[$key]))
    {
        echo ' <font color="green">success</font><br/>';
    } else {
        echo ' <font color="red">fail</font>:'.$db->errmsg().'<br/>';
    }
}
