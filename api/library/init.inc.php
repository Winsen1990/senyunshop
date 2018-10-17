<?php
/**
 * 管理后台初始化程序
 * @author 王仁欢
 * @version 1.0.0
 * @date 2015-01-09
 */

//设置系统相关参数
session_start();
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', str_replace('api/library/init.inc.php', '',str_replace('\\', '/', __FILE__)));
if(!class_exists('AutoLoader'))
{
    include(ROOT_PATH.'library/AutoLoader.class.php');
}

$loader = AutoLoader::getInstance();
$configs = array('script_path'=>ROOT_PATH.'library/', 'class_path'=>ROOT_PATH.'library/');
$loader->setConfigs($configs);

$class_list = array('Logs', 'MySQL', 'Smarty', 'JSSDK');
$loader->includeClass($class_list);
$script_list = array('configs','functions', 'member', 'wechat');
$loader->includeScript($script_list);
//初始化数据库链接
global $db;
$db = new MySQL(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DBNAME, DB_PREFIX, DB_CHARSET);

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
//初始化smarty对象
global $smarty;
$smarty = new Smarty();
$smarty->setCompileDir(ROOT_PATH.'data/compiles');
$smarty->setTemplateDir(ROOT_PATH.'themes/'.$config['themes']);
$smarty->setCacheDir(ROOT_PATH.'data/caches');

assign('config', $config);
//设置模板路径
assign('template_dir', '../themes/'.$config['themes'].'/');

//微信JS调用参数
if(is_weixin())
{
    $jssdk = new JSSDK($config['appid'], $config['appsecret'], $db);
    $signPackage = $jssdk->GetSignPackage();
    assign('signPackage', $signPackage);
}