<?php
/**
 * 初始化数据库
 */
include 'library/init.inc.php';
global $db, $loader;
header('Content-Type: text/html;charset=utf-8');
$table = array();
$sql = array();

$table[] = '产品分类';
$sql[] = 'create table if not exists '.$db->table('category').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null comment \'分类名称\',
    `parent_id` int not null comment \'上级分类ID\',
    `path` varchar(255) comment \'分类关系\',
    `img` varchar(255) comment \'分类图标\',
    `sort` int not null default \'0\' comment \'排序，升序排列\'
) engine=InnoDB default charset=utf8;';

$table[] = '产品品牌';
$sql[] = 'create table if not exists '.$db->table('brand').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null comment \'品牌名称\',
    `logo` varchar(255) not null comment \'品牌logo\',
    `sort` int not null default \'50\' comment \'排序，升序排列\',
    `desc` text comment \'品牌简介\'
) engine=InnoDB default charset=utf8;';

$table[] = '产品类型';
$sql[] = 'create table if not exists '.$db->table('product_type').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null comment \'产品类型名称\',
    `has_sku` tinyint(1) not null comment \'拥有SKU属性：0 - 否，1 - 是\'
) engine=InnoDB default charset=utf8;';

$table[] = '产品类型属性';
$sql[] = 'create table if not exists '.$db->table('product_attr').' (
    `id` bigint not null auto_increment primary key,
    `product_type_id` int not null comment \'产品类型ID\',
    `type` varchar(255) not null comment \'取值方式：text - 文本，options - 选择\',
    `options` text comment \'可选项\',
    `name` varchar(255) not null comment \'属性名\',
    `required` tinyint(1) not null default \'1\' comment \'必填项：0 - 否，1 - 是\',
    `as_sku` tinyint(1) not null default \'0\' comment \'是否SKU属性\'
) engine=InnoDB default charset=utf8;';

$table[] = '产品';
$sql[] = 'create table if not exists '.$db->table('product').' (
    `id` bigint not null auto_increment unique,
    `product_sn` varchar(255) not null primary key comment \'产品编号\',
    `name` varchar(255) not null comment \'产品名称\',
    `market_price` decimal(18,3) not null comment \'市场价格\',
    `price` decimal(18,3) not null comment \'零售价\',
    `min_price` decimal(18,3) not null comment \'价格区间最小价格，产品拥有多个SKU时使用\',
    `max_price` decimal(18,3) not null comment \'价格区间最大价格，产品拥有多个SKU时使用\',
    `given_reward` decimal(18,3) not null comment \'0\' comment \'返现佣金\',
    `given_integral` decimal(18,3) not null default \'0\' comment \'赠送积分\',
    `img` varchar(255) not null comment \'产品主图\',
    `desc` varchar(255) comment \'产品简介\',
    `detail` text comment \'产品详情\',
    `product_type_id` int not null default \'0\' comment \'产品类型ID\',
    `status` int not null default \'0\' comment \'产品状态：0 - 下架，1 - 上架，2 - 回收站，3 - 软删除\',
    `add_time` int not null comment \'创建时间\',
    `weight` int not null comment \'产品重量，单位：克\',
    `collection_count` int not null default \'0\' comment \'收藏数\',
    `comment_count` int not null default \'0\' comment \'评价数\',
    `star` int not null default \'5\' comment \'综合评分\',
    `brand_id` int not null comment \'品牌ID\',
    `sale_count` int not null default \'0\' comment \'销售量\',
    `recent_sale_count` int not null default \'0\' comment \'近30天销售量\',
    `sort` int not null default \'50\' comment \'排序，升序排列\',
    `free_delivery` tinyint(1) not null default \'0\' comment \'包邮：0 - 否，1 - 是\',
    `sale_mode` int not null default \'1\' comment \'销售模式：1 - 现金购买，2 - 积分兑换，3 - 积分换购，4 - 积分抵扣\',
    `is_special` tinyint(1) not null default \'0\' comment \'特殊产品：0 - 否，1 - 是\',
    `is_virtual` tinyint(1) not null default \'0\' comment \'0:实体产品，1:虚拟产品\'
) engine=InnoDB default charset=utf8;';

$table[] = '产品相册';
$sql[] = 'create table if not exists '.$db->table('gallery').' (
    `id` bigint not null auto_increment primary key,
    `img` varchar(255) not null comment \'相册图片\',
    `alt` varchar(255) comment \'替换文字\',
    `product_sn` varchar(255) not null comment \'产品编号\',
    `sort` int not null default \'50\' comment \'排序，升序排列\',
    index (`product_sn`)
) engine=InnoDB default charset=utf8;';

$table[] = '产品-属性取值';
$sql[] = 'create table if not exists '.$db->table('product_attr_mapper').' (
    `product_attr_value_id` bigint not null auto_increment unique,
    `product_type_id` bigint not null comment \'产品类型ID\',
    `product_sn` varchar(255) not null comment \'产品编号\',
    `product_attr_id` bigint not null comment \'产品属性ID\',
    `value` varchar(255) not null comment \'产品属性取值\',
    primary key(`product_sn`,`product_attr_id`, `value`),
    index(`product_type_id`,`product_attr_id`)
) engine=InnoDB default charset=utf8;';

$table[] = '库存';
$sql[] = 'create table if not exists '.$db->table('inventory').' (
    `id` bigint not null auto_increment unique,
    `sku` varchar(255) not null primary key comment \'SKU码\',
    `product_sn` varchar(255) not null comment \'产品编号\',
    `attributes` varchar(255) comment \'属性-取值JSON\',
    `desc` varchar(255) comment \'属性值，多个属性值用分号分隔\',
    `img` varchar(255) comment \'SKU产品图片\',
    `price` decimal(18,3) not null default \'0\' comment \'售价\',
    `market_price` decimal(18,3) not null default \'0\' comment \'市场价\',
    `integral` decimal(18,3) not null default \'0\' comment \'兑换积分\',
    `given_integral` decimal(18,3) not null default \'0\' comment \'赠送积分\',
    `given_reward` decimal(18,3) not null default \'0\' comment \'返现佣金\',
    `weight` int not null comment \'产品重量，单位：克\',
    `sale_count` int not null default \'0\' comment \'销售量\',
    `recent_sale_count` int not null default \'0\' comment \'近30天销售量\',
    `free_delivery` tinyint(1) not null default \'0\' comment \'包邮：0 - 否，1 - 是\',
    `sale_mode` int not null default \'1\' comment \'销售模式：1 - 现金购买，2 - 积分兑换，3 - 积分换购，4 - 积分抵扣\',
    `inventory` int not null default \'0\' comment \'物理库存\',
    `inventory_await` int not null default \'0\' comment \'待发库存\',
    `inventory_logic` int not null default \'0\' comment \'逻辑库存\',
    index (`product_sn`),
    index (`sku`)
) engine=InnoDB default charset=utf8;';

$table[] = '产品评价';
$sql[] = 'create table if not exists '.$db->table('comment').' (
    `id` bigint not null auto_increment primary key,
    `add_time` int not null comment \'评价时间\',
    `comment` varchar(255) not null comment \'评价内容\',
    `star` int not null default \'5\' comment \'评价星等\',
    `img` text comment \'评价图片JSON数组\',
    `product_sn` varchar(255) not null comment \'产品编号\',
    `sku` varchar(255) not null comment \'产品SKU码\',
    `sku_desc` varchar(255) comment \'产品SKU描述信息\',
    `account` varchar(255) not null comment \'会员账号\',
    `last_modify` datetime not null default CURRENT_TIMESTAMP comment \'最后修改时间\',
    `path` varchar(255) comment \'评价关系\',
    `parent_id` int not null default \'0\' comment \'回复评价ID\',
    index(`product_sn`)
) engine=InnoDB default charset=utf8;';

$table[] = '我的足迹';
$sql[] = 'create table if not exists '.$db->table('history').' (
    `product_sn` varchar(20) not null comment \'产品编号\',
    `account` varchar(20) not null comment \'会员账号\',
    `add_time` datetime not null default CURRENT_TIMESTAMP comment \'浏览时间\',
    primary key (`product_sn`,`account`)
) engine=InnoDB default charset=utf8;';

$table[] = '产品收藏';
$sql[] = 'create table if not exists '.$db->table('collection').' (
    `product_sn` varchar(255) not null comment \'产品编号\',
    `account` varchar(255) not null comment \'会员编号\',
    `add_time` datetime not null default CURRENT_TIMESTAMP comment \'收藏时间\',
    primary key (`product_sn`,`account`)
) engine=InnoDB default charset=utf8;';

$table[] = '会员账户流水';
$sql[] = 'create table if not exists '.$db->table('member_trade_log').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null comment \'会员编号\',
    `add_time` datetime not null default CURRENT_TIMESTAMP comment \'变动时间\',
    `balance` decimal(18,3) not null default \'0\' comment \'余额变动\',
    `reward` decimal(18,3) not null default \'0\' comment \'佣金变动\',
    `integral` decimal(18,3) not null default \'0\' comment \'积分变动\',
    `integral_await` decimal(18,3) not null default \'0\' comment \'待发积分变动\',
    `reward_await` decimal(18,3) not null default \'0\' comment \'待发佣金变动\',
    `operator` varchar(255) not null comment \'操作员\',
    `opera_type` int not null comment \'操作类型\',
    `remark` varchar(255) comment \'备注\'
) engine=InnoDB default charset=utf8;';

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
) engine=InnoDB default charset=utf8;';

$table[] = '会员扩展信息';
$sql[] = 'create table if not exists '.$db->table('member_info').' (
    `account` varchar(255) not null primary key,
    `real_name` varchar(255)
) engine=InnoDB engine=InnoDB default charset=utf8;';

$table[] = '订单';
$sql[] = 'create table if not exists '.$db->table('order').' (
    `id` bigint not null auto_increment unique,
    `order_sn` varchar(255) not null primary key,
    `add_time` int not null,
    `status` int not null default \'0\',
    `amount` decimal(18,3) not null,
    `integral_amount` decimal(18,3) not null,
    `product_amount` decimal(18,3) not null,
    `given_integral_amount` decimal(18,3) not null,
    `given_reward_amount` decimal(18,3) not null,
    `delivery_id` int not null,
    `delivery_name` varchar(255),
    `delivery_fee` decimal(18,2) not null,
    `delivery_express` varchar(255),
    `delivery_sn` varchar(255),
    `delivery_code` varchar(255),
    `delivery_time` int,
    `receive_time` int,
    `payment_id` int not null,
    `payment_name` varchar(255),
    `payment_sn` varchar(255),
    `pay_time` int,
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
    `account` varchar(255) not null,
    `is_comment` tinyint(1) not null default \'0\',
    `integral_paid` decimal(18,2) not null default \'0\',
    `reward_paid` decimal(18,2) not null default \'0\',
    `balance_paid` decimal(18,2) not null default \'0\',
    `is_virtual` tinyint not null default \'0\' comment \'0:实体产品订单，1:虚拟产品订单\'
) engine=InnoDB default charset=utf8;';

$table[] = '订单操作记录';
$sql[] = 'create table if not exists '.$db->table('order_log').' (
    `id` bigint not null auto_increment primary key,
    `order_sn` varchar(255) not null,
    `operator` varchar(255) not null,
    `status` int not null,
    `add_time` int not null,
    `remark` varchar(255)
) engine=InnoDB default charset=utf8;';

$table[] = '配送方式';
$sql[] = 'create table if not exists '.$db->table('delivery').' (
    `id` bigint not null auto_increment primary key,
    `self_delivery` tinyint(1) not null default \'0\',
    `name` varchar(255) not null,
    `plugins` varchar(255) not null,
    `desc` varchar(255),
    `status` int not null default \'0\'
) engine=InnoDB default charset=utf8;';

$table[] = '配送区域';
$sql[] = 'create table if not exists '.$db->table('delivery_area').' (
    `id` bigint not null auto_increment primary key,
    `first_weight` decimal(18,2) not null,
    `next_weight` decimal(18,2) not null,
    `free` decimal(18,2) not null default \'0\',
    `delivery_id` int not null,
    `name` varchar(255),
    index (`delivery_id`)
) engine=InnoDB default charset=utf8;';

$table[] = '配送区域映射';
$sql[] = 'create table if not exists '.$db->table('delivery_area_mapper').' (
    `id` bigint not null auto_increment primary key,
    `area_id` int not null,
    `province` int not null,
    `city` int not null,
    `district` int not null
) engine=InnoDB default charset=utf8;';

$table[] = '支付方式';
$sql[] = 'create table if not exists '.$db->table('payment').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null,
    `plugins` varchar(255) not null,
    `configure` text,
    `desc` varchar(255),
    `status` int not null default \'0\',
    `device` varchar(255) not null comment \'支持通道：mobile - 移动端, pc - PC端, app - APP, mixed - 全部支持\'
) engine=InnoDB default charset=utf8;';

$table[] = '广告位置';
$sql[] = 'create table if not exists '.$db->table('ad_position').' (
    `id` bigint not null auto_increment primary key,
    `pos_name` varchar(255) not null,
    `width` varchar(255) not null,
    `height` varchar(255) not null,
    `number` int not null default \'1\'
) engine=InnoDB default charset=utf8;';

$table[] = '广告';
$sql[] = 'create table if not exists '.$db->table('ad').' (
    `id` bigint not null auto_increment primary key,
    `img` varchar(255) not null,
    `alt` varchar(255) not null,
    `forever` tinyint(1) not null default \'1\',
    `click_time` int not null default \'0\',
    `url` varchar(255) not null,
    `sort` int not null default \'50\',
    `ad_pos_id` int not null,
    `begin_time` int,
    `end_time` int,
    `add_time` int not null
) engine=InnoDB default charset=utf8;';

$table[] = '管理员';
$sql[] = 'create table if not exists '.$db->table('admin').' (
    `account` varchar(255) not null primary key,
    `password` varchar(255) not null,
    `email` varchar(255) not null,
    `name` varchar(255) not null,
    `role_id` int not null,
    `mobile` varchar(255) not null,
    `sex` char(2) not null
) engine=InnoDB default charset=utf8;';

$table[] = '角色';
$sql[] = 'create table if not exists '.$db->table('role').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null,
    `purview` text not null
) engine=InnoDB default charset=utf8;';

$table[] = '站点参数';
$sql[] = 'create table if not exists '.$db->table('sysconf').' (
    `key` varchar(255) not null primary key,
    `name` varchar(255) not null,
    `value` varchar(255),
    `type` varchar(255) not null,
    `options` text,
    `group` varchar(255) not null,
    `remark` varchar(255) not null
) engine=InnoDB default charset=utf8;';

$table[] = '购物车';
$sql[] = 'create table if not exists '.$db->table('cart').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255),
    `product_sn` varchar(255) not null,
    `sku` varchar(255) not null,
    `sku_desc` varchar(255),
    `img` varchar(255) not null,
    `price` decimal(18,2) not null,
    `integral` decimal(18,2) not null,
    `number` int not null,
    `add_time` int not null,
    `checked` tinyint(1) not null default \'1\',
    `is_virtual` tinyint(1) not null default \'0\',
    `is_gift` tinyint(1) not null default \'0\' comment \'赠品：0 - 否，1 - 是\',
    `belongs` bigint not null default \'0\' comment \'赠品从属购物车ID\',
    index(`account`),
    index(`product_sn`),
    index(`sku`)
) engine=InnoDB default charset=utf8;';

$table[] = '支付记录';
$sql[] = 'create table if not exists '.$db->table('pay_log').' (
    `pay_sn` varchar(255) not null primary key,
    `amount` decimal(18,2) not null,
    `status` int not null,
    `order_assoc` varchar(255) not null,
    `id` bigint not null auto_increment unique,
    `pay_time` int,
    `add_time` int not null
) engine=InnoDB default charset=utf8;';

$table[] = '会员佣金';
$sql[] = 'create table if not exists '.$db->table('member_reward').' (
    `id` bigint not null auto_increment unique,
    `account` varchar(255) not null comment \'会员账号\',
    `integral` decimal(18,3) not null default \'0\' comment \'积分\',
    `reward` decimal(18,3) not null default \'0\' comment \'佣金\',
    `status` int not null comment \'状态：0 - 待发，1 - 已发\',
    `add_time` int not null comment \'结算时间\',
    `solve_time` int comment \'发放时间\',
    `assoc_type` varchar(255) comment \'关联类型：order - 订单\',
    `assoc` varchar(255) comment \'关联信息\',
    `remark` varchar(255) comment \'备注\'
) engine=InnoDB default charset=utf8;';

$table[] = '省';
$sql[] = 'create table if not exists '.$db->table('province').' (
    `id` int not null auto_increment primary key,
    `province_name` varchar(255) not null
) engine=InnoDB default charset=utf8;';

$table[] = '市';
$sql[] = 'create table if not exists '.$db->table('city').' (
    `id` int not null auto_increment primary key,
    `city_name` varchar(255) not null,
    `province_id` int not null,
    `first_letter` char(1) not null,
    `is_hot` tinyint not null default 0,
    `code` varchar(255) not null
) engine=InnoDB default charset=utf8;';

$table[] = '区';
$sql[] = 'create table if not exists '.$db->table('district').' (
    `id` int not null auto_increment primary key,
    `district_name` varchar(255) not null,
    `city_id` int not null,
    `code` varchar(255) not null
) engine=InnoDB default charset=utf8;';

$table[] = '商圈';
$sql[] = 'create table if not exists '.$db->table('group').' (
    `id` int not null auto_increment primary key,
    `group_name` varchar(255) not null,
    `district_id` int not null,
    `code` varchar(255) not null,
    `first_letter` char(1) not null
) engine=InnoDB default charset=utf8;';

$table[] = '订单详情';
$sql[] = 'create table if not exists '.$db->table('order_detail').' (
    `id` int not null auto_increment primary key,
    `order_sn` varchar(255) not null,
    `product_sn` varchar(255) not null,
    `sale_mode` int not null,
    `name` varchar(255) not null,
    `sku` varchar(255) not null,
    `sku_desc` varchar(255),
    `price` decimal(18,3) not null,
    `integral` decimal(18,3) not null,
    `given_reward` decimal(18,3) not null default \'0\',
    `given_integral` decimal(18, 2) not null default \'0\',
    `count` int not null,
    `is_virtual` tinyint(1) not null default \'0\',
    `is_special` tinyint(1) not null default \'0\',
    `is_gift` tinyint(1) not null default \'0\',
    `is_comment` tinyint(1) not null default \'0\' comment \'已评价：0 - 否，1 - 是\',
    index(`order_sn`),
    index(`product_sn`),
    index(`sku`)
) engine=InnoDB default charset=utf8;';

$table[] = '收货地址';
$sql[] = 'create table if not exists '.$db->table('address').' (
    `id` int not null auto_increment primary key,
    `province` int not null comment \'省ID\',
    `province_name` varchar(255) not null comment \'省名称\',
    `city` int not null comment \'市ID\',
    `city_name` varchar(255) not null comment \'市名称\',
    `district` int not null comment \'区ID\',
    `district_name` varchar(255) not null comment \'区名称\',
    `group` int not null default \'0\' comment \'商圈ID\',
    `group_name` varchar(255) not null comment \'商圈名称\',
    `address` varchar(255) not null comment \'详细地址\',
    `consignee` varchar(255) not null comment \'收货人\',
    `mobile` varchar(255) not null comment \'联系手机\',
    `zipcode` varchar(255) comment \'邮编\',
    `account` varchar(255) not null comment \'会员账号\',
    `is_default` tinyint(1) not null default \'0\' comment \'默认地址：0 - 否，1 - 是\',
    index(`account`)
) engine=InnoDB default charset=utf8;';

$table[] = '卡号池';
$sql[] = 'create table if not exists '.$db->table('card_pool').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null,
    `status` int not null default \'1\'
) engine=InnoDB default charset=utf8;';
/*
$table[] = '银行卡号';
$sql[] = 'create table if not exists '.$db->table('bank_card').' (
    `id` bigint not null auto_increment primary key,
    `account` varchar(255) not null,
    `bank` varchar(255) not null,
    `bank_account` varchar(255) not null,
    `bank_card` varchar(255) not null,
    `mobile` varchar(255) not null,
    index (`account`)
) engine=InnoDB default charset=utf8;';
*/
$table[] = '栏目';
$sql[] = 'create table if not exists '.$db->table('section').' (
    `id` int not null auto_increment primary key,
    `section_name` varchar(255) not null,
    `parent_id` int not null default \'0\',
    `path` varchar(255),
    `keywords` varchar(255),
    `description` varchar(255),
    `sort` int not null default \'50\',
    `thumb` varchar(255),
    `original` varchar(255)
) engine=InnoDB default charset=utf8;';

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
    `sort` int not null default \'50\',
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
) engine=InnoDB default charset=utf8;';

$table[] = '短信验证码池';
$sql[] = 'create table if not exists '.$db->table('message_code').' (
    `id` bigint not null auto_increment unique,
    `mobile` varchar(255) not null primary key comment \'手机号码\',
    `code` varchar(255) not null comment \'验证码\',
    `expire` int not null comment \'验证码过期时间\'
) engine=InnoDB default charset=utf8;';
/*
$table[] = '虚拟订单内容';
$sql[] = 'create table if not exists '.$db->table('order_content').' (
    `id` int not null auto_increment primary key,
    `order_sn` varchar(255) not null,
    `product_sn` varchar(255) not null,
    `content` varchar(255) not null,
    `count` varchar(255) not null,
    `total` varchar(255) not null
) engine=InnoDB default charset=utf8;';

$table[] = '充值记录';
$sql[] = 'create table if not exists '.$db->table('recharge').'(
    `recharge_sn` varchar(255) not null primary key comment \'充值编号\',
    `account` varchar(255) not null comment \'帐号\',
    `amount` decimal(18,2) not null comment \'充值金额\',
    `status` tinyint not null default 1 comment \'1：到帐，2：充值中，3：取消\',
    `add_time` int not null comment \'充值时间\',
    `type` tinyint not null default 0 comment \'0:线上，1：线下\',
    `bank` varchar(255) not null default \'\' comment \'银行(type=1有效)\',
    `card_num` varchar(255) not null default \'\' comment \'卡号(type=1有效)\',
    `solve_time` int not null default 0 comment \'处理时间(type=1有效)\'
) engine=InnoDB default charset=utf8;';

$table[] = '充值日志';
$sql[] = 'create table if not exists '.$db->table('recharge_log').'(
    `id` int not null auto_increment primary key,
    `account` varchar(255) not null,
    `add_time` int not null,
    `operator` varchar(255) not null,
    `recharge_sn` varchar(255) not null,
    `type` tinyint not null comment \'0:线上，1：线下\',
    `status` tinyint not null comment \'0:未到帐，1：到帐\',
    `remark` varchar(255) not null default \'\'
) engine=InnoDB default charset=utf8;';

$table[] = '导航栏';
$sql[] = 'create table if not exists '.$db->table('nav').' (
    `id` int not null auto_increment primary key,
    `name` varchar(255) not null,
    `url` varchar(255) not null,
    `parent_id` int not null default \'0\',
    `position` varchar(255) not null,
    `sort` int not null default \'50\'
) engine=InnoDB default charset=utf8;';
*/

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
) engine=InnoDB default charset=utf8;';
/*
$table[] = '微信客服';
$sql[] = 'create table if not exists '.$db->table('wx_kf').' (
    `id` bigint not null auto_increment unique,
    `kf_account` varchar(255) not null,
    `nickname` varchar(255) not null,
    `password` varchar(255) not null,
    `headimg` varchar(255)
) engine=InnoDB default charset=utf8;';

$table[] = '回复规则';
$sql[] = 'create table if not exists '.$db->table('wx_rule').' (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `enabled` tinyint(1) NOT NULL DEFAULT \'1\',
    `rule` varchar(255) NOT NULL,
    `match_mode` int(11) NOT NULL DEFAULT \'0\',
    `response_id` int(11) NOT NULL,
    `sort` int(11) NOT NULL DEFAULT \'50\',
    `is_default` tinyint(1) NOT NULL DEFAULT \'0\',
    `name` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) engine=InnoDB default charset=utf8;';

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
) engine=InnoDB default charset=utf8;';
*/
$table[] = '小程序登录授权口令';
$sql[] = 'create table if not exists '.$db->table('api_token').' (
    `account` varchar(255) not null primary key comment \'用户账号\',
    `add_time` int not null comment \'授权登录时间\',
    `token` varchar(255) not null unique comment \'口令\',
    `expired` int not null comment \'授权过期时间\'
) engine=InnoDB engine=InnoDB default charset=utf8;';

$table[] = '产品-分类-映射';
$sql[] = 'create table if not exists '.$db->table('product_category_mapper').' (
    `id` bigint not null auto_increment unique,
    `product_sn` varchar(255) not null comment \'产品编号\',
    `category_id` bigint not null comment \'产品分类ID\',
    primary key(`product_sn`, `category_id`),
    index(`product_sn`),
    index(`category_id`)
) engine=InnoDB engine=InnoDB default charset=utf8;';

$table[] = '会员等级';
$sql[] = 'create table if not exists '.$db->table('level').'(
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null comment \'等级名称\',
    `experience` decimal(18, 3) not null default \'0\' comment \'经验值\',
    `experience_type` int not null default \'1\' comment \'经验值类型：1 - 单次消费额，2 - 累计消费额\',
    `recommend_count` int not null default \'0\' comment \'推荐人数\',
    `is_special` tinyint(1) not null default \'0\' comment \'特殊等级：0 - 否，1 - 是\',
    `discount` int not null default \'100\' comment \'享受折扣\',
    `forever` tinyint(1) not null default \'1\' comment \'会员身份长期有效：0 - 否，1 - 是\',
    `expired` int not null default \'0\' comment \'会员身份有效时长，单位：小时\',
    `remark` varchar(255) comment \'备注\'
) engine=InnoDB engine=InnoDB default charset=utf8;';

$table[] = '专区';
$sql[] = 'create table if not exists '.$db->table('blocks').' (
    `id` bigint not null auto_increment primary key,
    `name` varchar(255) not null comment \'专区名称\',
    `status` int not null default \'0\' comment \'状态：0 - 停用，1 - 启用\',
    `url` varchar(255) not null comment \'链接\',
    `cover` varchar(255) not null comment \'封面图\',
    `sort` int not null default \'50\' comment \'排序 升序\'
) engine=InnoDB engine=InnoDB default charset=utf8;';

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

$table[] = '测试';
$sql[] = 'create table if not exists '.$db->table('exam').' (
    `id` bigint not null auto_increment primary key,
    `title` varchar(255) not null comment \'测试名称\',
    `forever` tinyint(1) not null default \'0\' comment \'长期有效：0 - 否，1 - 是\',
    `status` int not null default \'0\' comment \'状态：0 - 停用，1 - 启用\',
    `begin_time` int comment \'开始时间\',
    `end_time` int comment \'结束时间\'
) engine=InnoDB default charset=utf8;';

$table[] = '问题';
$sql[] = 'create table if not exists '.$db->table('question').' (
    `id` bigint not null auto_increment primary key,
    `exam_id` bigint not null comment \'测试ID\',
    `title` varchar(255) not null comment \'题目\',
    `answer_mode` int not null comment \'答案类型：1 - 单选，2 - 多选\',
    `answers` text not null comment \'答案选项\',
    index(`exam_id`)
) engine=InnoDB default charset=utf8;';

$table[] = '答案';
$sql[] = 'create table if not exists '.$db->table('result').' (
    `id` bigint not null auto_increment primary key,
    `exam_id` bigint not null comment \'测试ID\',
    `answer_series` varchar(255) not null comment \'答案序列\',
    `recommend_product` varchar(255) comment \'推荐产品编号，多个产品用逗号分隔\',
    `conclusion` varchar(255) not null comment \'结论\'
) engine=InnoDB default charset=utf8;';

$table[] = '用户测试结果';
$sql[] = 'create table if not exists '.$db->table('member_exam_result').' (
    `id` bigint not null auto_increment primary key,
    `exam_id` bigint not null comment \'测试ID\',
    `account` varchar(255) not null comment \'用户账号\',
    `add_time` int not null comment \'测试时间\',
    `answer_series` varchar(255) not null comment \'答案序列\',
    `conclusion` varchar(255) not null comment \'结论\',
    `recommend_product` varchar(255) comment \'推荐产品编号，多个编号用逗号分隔\',
    index(`account`),
    index(`exam_id`)
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
