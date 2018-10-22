<?php
/**
 * 初始化数据
 */
include 'library/init.inc.php';
global $db, $loader;
$loader->includeScript('purview');

header('Content-Type: text/html;charset=utf-8');
$table = array();
$data = array();

//站点参数
$table[] = 'sysconf';
$data[] = array(
    //================================= 业务参数 =====================================================
    array('key'=>'recommend_integral', 'name'=>'推广赠送积分', 'value'=>10, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'level_1', 'name'=>'一级分销比例', 'value'=>0, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'level_2', 'name'=>'二级分销比例', 'value'=>0, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'level_3', 'name'=>'三级分销比例', 'value'=>0, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'fee_rate', 'name'=>'提现手续费比例', 'value'=>0, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'integral_rate', 'name'=>'积分比值', 'value'=>1, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'reward_rate', 'name'=>'佣金比例', 'value'=>1, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'withdraw_min', 'name'=>'最小提现金额', 'value'=>0, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    //================================= 微信参数 =====================================================
    array('key'=>'public_account', 'name'=>'公众号原始ID', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'appid', 'name'=>'微信AppID', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'mini_appid', 'name'=>'微信小程序AppID', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'appsecret', 'name'=>'微信AppSecret', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'mini_appsecret', 'name'=>'微信小程序AppSecret', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'token', 'name'=>'微信接入口令', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'access_token', 'name'=>'接口凭证', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'jsapi_ticket', 'name'=>'微信JSSDK口令', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'jsapi_ticket_expire', 'name'=>'微信JSSDK口令超时时间', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'expired', 'name'=>'接口凭证超时时间', 'value'=>0, 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'mch_id', 'name'=>'微信支付商户号', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'mch_key', 'name'=>'微信支付商户号', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'wx_ssl_key', 'name'=>'微信支付证书密钥', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    array('key'=>'wx_ssl_cert', 'name'=>'微信支付证书', 'value'=>'', 'type'=>'text', 'group'=>'wechat', 'remark'=>''),
    //================================= 系统设置 =====================================================
    array('key'=>'category_depth', 'name'=>'产品分类深度', 'value'=>4, 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'domain', 'name'=>'域名', 'value'=>'http://local.wine.winsen.com', 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'site_name', 'name'=>'站点名称', 'value'=>'', 'type'=>'text', 'group'=>'config', 'remark'=>''),
    array('key'=>'themes', 'name'=>'模板文件', 'value'=>'wine', 'type'=>'text', 'group'=>'themes', 'remark'=>''),
    array('key'=>'statistics ', 'name'=>'开启统计', 'value'=>'0', 'type'=>'text', 'group'=>'themes', 'remark'=>''),
);

//初始化商家
$table[] = 'business';
$data[] = array(
    array(
        'id' => 1,
        'business_account' => 'Wine2018',
        'password' => md5('Wine2018@'.PASSWORD_END),
        'company' => '知性小厨',
        'license' => '',
        'identity' => '',
        'industry_id' => 0,
        'category_id' => 0,
        'province' => 5,
        'city' => 5,
        'district' => 103,
        'group' => 0,
        'address' => '',
        'status' => 2,
        'contact' => '彭',
        'mobile' => '13929564894',
        'email' => 'airplace1@gmail.com',
        'shop_name' => '知性小厨',
        'shop_logo' => '',
        'comment' => 0,
        'account' => ''
    )
);

/*
//产品分类
$table[] = 'category';
$data[] = array(
);

$table[] = 'product';
$data[] = array(
);
 */
$table[] = 'card_pool';
$data[] = array(
    array('account'=>'SJ000000', 'status'=>1)
);

$table[] = 'payment';
$data[] = array(
    array('name'=>'微信支付', 'plugins'=>'wechat', 'desc'=>'微信支付', 'status'=>1)
);
/*
$table[] = '初始化广告位数据';
$sql[] = 'insert into '.$db->table('ad_position').' (`id`, `pos_name`, `width`, `height`, `number`, `code`) values
 (\'1\', \'首页轮播广告\', \'320\', \'140\', \'5\', \'\'),
 (\'2\', \'首页展示广告\', \'320\', \'128\', \'3\', \'\'),
 (\'3\', \'商家轮播\', \'320\', \'140\', \'3\', \'\'),
 (\'4\', \'PC端网站首页大广告\', \'788\', \'328\', \'5\', \'\');';


$table[] = '初始化会员等级';
$sql[] = 'insert into '.$db->table('level').' values (1, \'普通会员\', 0, 2, 0, 0, 100, \'\'),
(2, \'白银会员\', 1001, 2, 0, 0, 90, \'\'),(3, \'黄金会员\', 2001, 2, 0, 0, 80, \'\'),(4, \'铂金会员\', 3001, 2, 0, 0, 70, \'\'),
(5, \'钻石会员\', 5001, 2, 0, 0, 50, \'\')';
*/
echo '初始化数据:<br/>';
foreach($table as $key=>$name)
{
    $db->query('truncate table '.$db->table($name).';');
    echo $name;

    $dot_count = 30 - strlen($name);

    while($dot_count--)
    {
        echo '-';
    }

    if($db->autoInsert($name, $data[$key]))
    {
        echo ' <font color="green">success</font><br/>';
    } else {
        echo ' <font color="red">fail</font><br/>'.$db->errmsg();
    }
}
