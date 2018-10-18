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
define('DB_DBNAME', 'wine');
define('DB_PREFIX', 'wi_');
define('DB_CHARSET', 'utf8mb4');
define('PASSWORD_END', '_wine');

//参数配置
define('PRODUCT_PREFIX', 'WI');

//人机校验
global $validate_config;
$validate_config = array(
    'site_key' => '0e9dc6c2173baac840167e0bbd82dd56',
    'api_key' => 'c22f7bec7c4dcee0d6a4970e482f7c3c'
);