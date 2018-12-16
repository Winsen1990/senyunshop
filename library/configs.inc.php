<?php
/*
 * 系统全局配置文件
 * @author winsen
 * @date 2015-01-09
 * @version 1.0.0
 */
global $charset;
$charset = 'utf8'; //数据库采用编码

//数据库配置
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define('DB_DBNAME', 'senyunshop');
define('DB_PREFIX', 'wi_');
define('DB_CHARSET', 'utf8mb4');
define('PASSWORD_END', '_wine');

//参数配置
define('PRODUCT_PREFIX', 'WI');

//优惠券配置
define('COUPON_PREFIX_LENGTH', 3);
define('COUPON_SN_LENGTH', 5);

//人机校验
global $validate_config;
$validate_config = array(
    'site_key' => '0e9dc6c2173baac840167e0bbd82dd56',
    'api_key' => 'c22f7bec7c4dcee0d6a4970e482f7c3c'
);

//订单取消通知
define('WX_CANCEL_ORDER_TEMPLATE_ID', '6sNo5uiMZD_tPraW736DWE5jhQxXrkDI-0Hj7VumImg');
//订单发货提醒
define('WX_DELIVERY_ORDER_TEMPLATE_ID', 'bLhP9vHqFe9Tut-moIVab_C7jo5nGGWhuF7FC-oPP_8');
//订单支付成功通知
define('WX_PAID_ORDER_TEMPLATE_ID', '_kWnnWQuyRvUSUMwYvsI3ZDVIJToC5jlkwWi6k4CSGU');
//订单退款成功通知
define('WX_REFUND_ORDER_TEMPLATE_ID', 'gg5OTQBKQSTiZj6T7vaNWld871AQiNUnVVgIR13DIOo');