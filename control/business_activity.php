<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2017/12/8
 * Time: 下午3:56
 */
include 'library/init.inc.php';
global $db, $log, $smarty, $config;

back_base_init();

$template = 'business_activity/';
assign('subTitle', '现场活动管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add|delete|add_reward|edit_reward|delete_reward';
$act = check_action($action, getGET('act'));

$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

//新增活动
if('add' == $opera) {

}

//修改活动
if('edit' == $opera) {

}

//删除活动
if('delete' == $opera) {

}

//新增奖品
if('add_reward' == $opera) {

}

//编辑奖品
if('edit_reward' == $opera) {

}

//删除奖品
if('delete_reward' == $opera) {

}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//查看活动列表
if('view' == $act) {

}

//编辑活动
if('edit' == $act) {

}

//奖品列表
if('view_reward' == $act) {

}

//新增奖品
if('add_reward' == $act) {

}

//编辑奖品
if('edit_reward' == $act) {

}

$template .= $act.'.phtml';
$smarty->display($template);