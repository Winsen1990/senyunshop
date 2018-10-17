<?php
/**
 * 管理后台首页
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-8-5
 * @version 1.0.0
 */

include 'library/init.inc.php';

//管理后台初始化
back_base_init();
$template = 'main/';

$action = 'view';
$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

//会员数量
$get_member_count = 'select count(*) from '.$db->table('member').' where 1';
$member_count = $db->fetchOne($get_member_count);
assign('member_count', $member_count);

//商户数量
$get_business_count = 'select count(*) from '.$db->table('business').' where status = 2 or status = 3';
$business_count = $db->fetchOne($get_business_count);
assign('business_count', $business_count);

//管理员数量
$get_admin_count = 'select count(*) from '.$db->table('platform_admin').' where 1';
$admin_count = $db->fetchOne($get_admin_count);
assign('admin_count', $admin_count);

$template .= $act.'.phtml';
$smarty->display($template);




