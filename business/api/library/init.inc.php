<?php
/**
 * 管理后台初始化程序
 * @author 王仁欢
 * @version 1.0.0
 * @date 2015-01-09
 */
header('Content-Type: application/json');
//设置系统相关参数
session_start();
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', str_replace('business/api/library/init.inc.php', '',str_replace('\\', '/', __FILE__)));
if(!class_exists('AutoLoader'))
{
    include(ROOT_PATH.'library/AutoLoader.class.php');
}

$loader = AutoLoader::getInstance();
$configs = array('script_path'=>ROOT_PATH.'library/', 'class_path'=>ROOT_PATH.'library/');
$loader->setConfigs($configs);

$class_list = array('Logs', 'MySQL', 'RestFulException');
$loader->includeClass($class_list);
$script_list = array('configs','functions','lang', 'purview', 'member', 'wechat');
$loader->includeScript($script_list);
//初始化数据库链接
global $db;
$db = new MySQL(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DBNAME, DB_PREFIX);

$debug_mode = true;//开启此项将关闭Smarty缓存模式，并开启日志跟踪
//初始化日志对象
global $log;
$log_file = date('Ymd').'.log';
$log = new Logs($debug_mode, $log_file);
//读取网站设置
$get_sysconf = 'select `key`,`value` from '.$db->table('sysconf');
global $config;
$config_tmp = $db->fetchAll($get_sysconf);
foreach($config_tmp as $tmp)
{
    $config[$tmp['key']] = $tmp['value'];
}

if(!isset($_SERVER['HTTP_X_AUTH']) && !isset($_SESSION['business_account'])) {
    throw new RestFulException('没有操作权限', 503);
}

$raw_request_data = file_get_contents('php://input');
parse_str($raw_request_data, $_POST);

if(isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false && !empty($raw_request_data)) {
    $request_json_data = json_decode($raw_request_data, true);

    foreach($request_json_data as $request_key => $request_value) {
        $_POST[$request_key] = $request_value;
    }
}