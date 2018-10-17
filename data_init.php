<?php
/**
 * 初始化数据
 */
include 'library/init.inc.php';
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

//角色
$table[] = 'platform_role';
$data[] = array(
    array(
        'id' => 1,
        'name' => '超级管理员',
        'purview' => json_encode($purview)
    )
);

//管理员
$table[] = 'platform_admin';
$data[] = array(
    array(
        'account' => 'admin',
        'password' => md5('admin'.PASSWORD_END),
        'name' => '管理员',
        'email' => 'airplace1@gmail.com',
        'sex' => 'F',
        'role_id' => 1,
        'mobile' => '13929564894'
    )
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
$table[] = 'modules';
$data[] = array(
    array('url' => 'donate.php', 'name' => '公益专区', 'assoc_type' => '', 'assoc' => '', 'status' => 1, 'identity' => 'donate'),
    array('url' => 'active.php', 'name' => '活动专区', 'assoc_type' => '', 'assoc' => '', 'status' => 1, 'identity' => 'active'),
    array('url' => 'healthy_advisory.php', 'name' => '健康咨询', 'assoc_type' => 'section_id', 'assoc' => '2', 'status' => 1, 'identity' => 'healthy_advisory'),
    array('url' => 'expert_online.php', 'name' => '专家连线', 'assoc_type' => 'section_id', 'assoc' => '2', 'status' => 1, 'identity' => 'expert_online'),
    array('url' => 'interactive.php', 'name' => '互动专区', 'assoc_type' => 'section_id', 'assoc' => '2', 'status' => 1, 'identity' => 'interactive'),
    array('url' => 'business_study.php', 'name' => '培训预约', 'assoc_type' => 'section_id', 'assoc' => '3', 'status' => 1, 'identity' => 'business_study'),
    array('url' => 'notice.php', 'name' => '系统通知', 'assoc_type' => 'section_id', 'assoc' => '3', 'status' => 1, 'identity' => 'notice'),
    array('url' => 'business_online_advisory.php', 'name' => '在线咨询', 'assoc_type' => 'section_id', 'assoc' => '3', 'status' => 1, 'identity' => 'business_online_advisory'),
    array('url' => 'dance_study.php', 'name' => '培训预约', 'assoc_type' => 'section_id', 'assoc' => '4', 'status' => 1, 'identity' => 'dance_study'),
    array('url' => 'coach_reserve.php', 'name' => '教练预约', 'assoc_type' => 'section_id', 'assoc' => '4', 'status' => 1, 'identity' => 'coach_reserve'),
    array('url' => 'dance_online_advisory.php', 'name' => '在线咨询', 'assoc_type' => 'section_id', 'assoc' => '4', 'status' => 1, 'identity' => 'dance_online_advisory'),
);
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
