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
define('ROOT_PATH', str_replace('business/library/init.inc.php', '',str_replace('\\', '/', __FILE__)));
define('BASE_DIR', str_replace($_SERVER['DOCUMENT_ROOT'], '', ROOT_PATH));

if(!class_exists('AutoLoader'))
{
    include(ROOT_PATH.'library/AutoLoader.class.php');
}

$loader = AutoLoader::getInstance();
$configs = array('script_path'=>ROOT_PATH.'library/', 'class_path'=>ROOT_PATH.'library/');
$loader->setConfigs($configs);

$class_list = array('Smarty', 'Logs', 'MySQL', 'Code', 'RestfulException');
$loader->includeClass($class_list);
$script_list = array('configs','functions','lang', 'business', 'transaction', 'member', 'wechat');
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
if($config_tmp) {
    foreach ($config_tmp as $tmp) {
        $config[$tmp['key']] = $tmp['value'];
    }
}

//初始化smarty对象
global $smarty;
$smarty = new Smarty();
$smarty->setCompileDir(ROOT_PATH.'business/data/compiles');
$smarty->setTemplateDir(ROOT_PATH.'business/themes/');
$smarty->setCacheDir(ROOT_PATH.'business/data/caches');
$smarty->setCacheLifetime(1800);//设置缓存文件超时时间为1800秒

//Debug模式下每次都强制编译输出
if($debug_mode)
{
    $smarty->clearAllCache();
    $smarty->clearCompiledTemplate();
    $smarty->force_compile = true;
}

assign('config', $config);
