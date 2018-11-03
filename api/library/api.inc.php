<?php
/**
 * API启动程序
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/22
 * Time: 16:56
 */
//设置系统相关参数
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', str_replace('api/library/api.inc.php', '',str_replace('\\', '/', __FILE__)));
if(!class_exists('AutoLoader'))
{
    include(ROOT_PATH.'library/AutoLoader.class.php');
}

//创建加载器
$loader = AutoLoader::getInstance();
$configs = [
    'script_path' => ROOT_PATH.'library/',
    'class_path' => ROOT_PATH.'library/'
];
$loader->setConfigs($configs);

//引入需要的类
//命名格式：{ClassName}.class.php
$class_list = ['Logs', 'MySQL', 'RestFulException'];
$loader->includeClass($class_list);

//引入辅助文件
//命名格式: {functions}.inc.php
$script_list = ['configs', 'functions', 'member', 'wechat'];
$loader->includeScript($script_list);

//初始化数据库链接
global $db;
$db = new MySQL(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_DBNAME, DB_PREFIX, DB_CHARSET);

$debug_mode = true;//开启此项将开启日志跟踪
//初始化日志对象
global $log;
$log_file = date('Ymd').'.log';
$log = new Logs($debug_mode, $log_file);

//读取网站设置
global $config;
$config_tmp = $db->all('sysconf', ['key', 'value']);
foreach($config_tmp as $tmp) {
    $config[$tmp['key']] = $tmp['value'];
}

//获取json格式的请求传递到POST中
$request_json = file_get_contents('php://input');
$request_params = json_decode($request_json, true);
$log->record_array($request_params);
if(is_array($request_params)) {
    foreach($request_params as $key => $value) {
        $_POST[$key] = $value;
    }
}
//鉴权
/*
$time_delta = time() - getGET('timestamp');
//时间戳异常或请求时间超过5分钟未响应
if($time_delta < 0 || $time_delta > 300) {
    throw new RestFulException('请求超时', 500);
}
*/

//登录判断
global $current_user;
$current_user = null;
$login_flag = false;
$token = getGET('token');
$token = empty($token) ? getPOST('token') : $token;
if(!empty($token)) {
    //检查口令有效性
    $account = $db->getColumn('api_token', 'account', ['token' => $token]);

    if(!empty($account)) {
        $current_user = $db->find('member', '*', ['account' => $account]);

        if(!empty($current_user)) {
            $login_flag = true;
        }
    }
}

global $levels;
$level_list = $db->all('level', ['id', 'name', 'discount', 'experience', 'experience_type', 'is_special']);
if($level_list) {
    while($_level = array_shift($level_list)) {
        $levels[$_level['id']] = $_level;
    }
}

if(!$login_flag) {
    $ignore_script = 'authorization.php|index.php|category.php|product.php|feed.php|comment.php|keywords.php';

    $script_name = str_replace(ROOT_PATH, '', $_SERVER['SCRIPT_FILENAME']);
    $script_name_info = explode('/', $script_name);
    if(count($script_name_info)) {
        $script_name = $script_name_info[count($script_name_info) - 1];
    }

    $need_authorization = check_action($ignore_script, $script_name, false) === false;

    if($need_authorization) {
        throw new RestFulException('请先登录', 503);
    }
}
