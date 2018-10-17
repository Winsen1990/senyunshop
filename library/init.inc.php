<?php
/**
 * 初始化程序
 * @author winsen
 * @version 1.0.0
 * @date 2015-01-09
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

//设置系统相关参数
date_default_timezone_set('Asia/Shanghai');
define('ROOT_PATH', str_replace('library/init.inc.php', '',str_replace('\\', '/', __FILE__)));
define('BASE_DIR', str_replace($_SERVER['DOCUMENT_ROOT'], '', ROOT_PATH));

if(!class_exists('AutoLoader'))
{
    include('AutoLoader.class.php');
}

$loader = AutoLoader::getInstance();
$configs = array('script_path'=>ROOT_PATH.'library/', 'class_path'=>ROOT_PATH.'library/');
$loader->setConfigs($configs);

$class_list = array('Smarty', 'Logs', 'MySQL', 'Code', 'JSSDK', 'WechatResponse');
$loader->includeClass($class_list);
$script_list = array('configs','functions','lang', 'member', 'transaction', 'wechat');
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

//初始化smarty对象
global $smarty;
$smarty = new Smarty();
$smarty->setCompileDir(ROOT_PATH.'data/compiles');
$smarty->setTemplateDir(ROOT_PATH.'themes/'.$config['themes']);
$smarty->setCacheDir(ROOT_PATH.'data/caches');

//Debug模式下每次都强制编译输出
if($debug_mode)
{
    $smarty->clearAllCache();
    $smarty->clearCompiledTemplate();
    $smarty->force_compile = true;
}

//设置语言包
assign('LANG', $lang);
//设置网站参数
assign('config', $config);
//设置模板路径
assign('template_dir', 'themes/'.$config['themes'].'/');

//读取ukey参数，记录推荐人信息
$ukey = getGET('ukey');
if($ukey != '')
{
    $ukey = intval($ukey);

    if($ukey > 0)
    {
        $get_member_id = 'select `id` from '.$db->table('member').' where `id`='.$ukey;

        if($member_id = $db->fetchOne($get_member_id))
        {
            $_SESSION['parent_id'] = $member_id;
        }
    }
}

if(!isset($_SESSION['parent_id'])){
    $_SESSION['parent_id'] = 0;
}

if(!isset($_SESSION['openid']))
{
    $_SESSION['openid'] = '';
}

if(!isset($_SESSION['account']))
{
    $_SESSION['account'] = '';
}

$code = getGET('code');
$state = getGET('state');
//微信获取和同步用户信息
if($_SESSION['openid'] == '' && $code != '' && $state == 2048 && is_weixin())
{
    $wechat_user = get_user_info($code, $config['appid'], $config['appsecret'], 'userinfo');

    if($wechat_user)
    {
        $log->record("get user openid:".$wechat_user->openid);
        $_SESSION['openid'] = $wechat_user->openid;

        $get_account = 'select `account` from '.$db->table('member').' where `openid`=\''.$wechat_user->openid.'\'';
        $account = $db->fetchOne($get_account);

        if(!$account)
        {
            //检查用户的unionid
            $access_token = get_access_token($config['appid'], $config['appsecret']);

            $all_info = get_user_wechat_info($access_token, $wechat_user->openid);

            if(isset($all_info->unionid))
            {
                $get_account = 'select `account` from '.$db->table('member').' where `unionid`=\''.$db->escape($all_info->unionid).'\'';
                $account = $db->fetchOne($get_account);

                if($account)
                {
                    $member_data = array(
                        'openid' => $_SESSION['openid']
                    );

                    $db->autoUpdate('member', $member_data,'`account`=\''.$account.'\'');
                }
            }

            if(!$account)
            {
                //如果用户不存在，则直接新注册用户
                $log->record("register new member");
                $user_account = register_member($_SESSION['openid'], $_SESSION['parent_id']);
                $get_parent_account = 'select `account` from '.$db->table('member').' where `id`='.$_SESSION['parent_id'];
                $parent_account = $db->fetchOne($get_account);

                if($config['recommend_integral'] > 0 && add_recommend_integral($parent_account, $config['recommend_integral'], '推荐新用户奖励'))
                {
                    $log->record('add recommend integral success:'.$account.' increment '.$config['recommend_integral']);
                }

                //发放关注奖励
                if($user_account && $config['subscribe_integral'] > 0) {
                    if(add_recommend_integral($user_account, $config['subscribe_integral'], '关注送积分'))
                    {
                        $log->record('add recommend integral success:'.$user_account.' increment '.$config['subscribe_integral']);
                    } else {
                        $log->record('add recommend integral fail:'.$user_account.' increment '.$config['subscribe_integral']);
                    }
                }
            }
        }

        $member_data = array(
            'sex' => $wechat_user->sex,
            'nickname' => $wechat_user->nickname,
            'province' => $wechat_user->province,
            'city' => $wechat_user->city,
            'headimg' => $wechat_user->headimgurl
        );

        if(isset($wechat_user->unionid))
        {
            $member_data['unionid'] = $wechat_user->unionid;
        }

        $db->autoUpdate('member', $member_data, '`openid`=\''.$wechat_user->openid.'\'');
        $get_account = 'select `account` from '.$db->table('member').' where `openid`=\''.$wechat_user->openid.'\'';
        $_SESSION['account'] = $db->fetchOne($get_account);
        $log->record('set account: '.$_SESSION['account']);
    } else {
        echo 'ERROR 2048: 获取授权信息失败';
        exit;
    }
}

if($_SESSION['openid'] == '' && $_SESSION['account'] == '') {
    $no_login_script = 'donate.php|active.php|recommend_shop.php|data_init.php|code.php|login.php|register.php|forgot.php|data_center.php|index.php|article_list.php|article.php|install.php|integral_product_list.php|';
    $no_login_script .= 'category.php|product.php|cart.php|product_list.php|search.php|shop.php|distribution_shop.php|notify.php|wechat.php|notify_url.php';

    if(!is_weixin()) {
        $no_login_script .= '|topay.php';
    }

    $script_name = str_replace(ROOT_PATH, '', $_SERVER['SCRIPT_FILENAME']);

    $flag = check_action($no_login_script, $script_name);
    if($flag == '')
    {
        if(is_weixin()) {
            $current_url = 'http://'.$config['domain'].'/'.$script_name;
            $log->record('wechat grant privilege login: '.$current_url);
            $current_url = urlencode($current_url);
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$config['appid'].'&redirect_uri='.$current_url.'&response_type=code&scope=snsapi_userinfo&state=2048#wechat_redirect';
            redirect($url);
            exit;
        } else {
            redirect('login.php');
            exit;
        }
    }
}

if($_SESSION['openid'] == '' && $_SESSION['account'] == '' && is_weixin()) {
    $_SESSION['subscribed'] = 0;
} else {
    $subscribe_status = $db->getColumn('member', 'is_subscribe', array('account' => $_SESSION['account']));

    $_SESSION['subscribed'] = $subscribe_status;
}

//微信JS调用参数
if(is_weixin())
{
    $jssdk = new JSSDK($config['appid'], $config['appsecret'], $db);
    $signPackage = $jssdk->GetSignPackage();
    assign('signPackage', $signPackage);
}

//统计PV,UV
/*
if( 1 == $config['statistics'] ) {
    $date = date('Ym', time());
    $table = 'statistics'.$date;
    $sql = 'create table if not exists '.$db->table($table).' (
        `id` bigint not null auto_increment primary key,
        `request_time` int not null,
        `ip` varchar(255) not null,
        `source` varchar(255) not null,
        `destination` varchar(255) not null,
        `keep_alive` int,
        `cookie` varchar(255) not null,
        `agent` varchar(255) not null,
        `markup` varchar(255)
    ) default charset=utf8;';

    $db->query($sql);

    $source = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER']: '';

    $data = array(
        'request_time' => time(),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'source' => $source,
        'destination' => $_SERVER['PHP_SELF'],
        'keep_alive' => 0,
        'cookie' => session_id(),
        'agent' => $_SERVER['HTTP_USER_AGENT'],
        'markup' => 'wechat',
    );

    $db->autoInsert($table, array($data));
}
*/