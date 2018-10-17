<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/10
 * Time: 上午10:06
 */
include 'library/init.inc.php';

$functions = array(
    array('url' => 'javascript:popup_message(\'功能建设中，敬请期待\');', 'name' => '发起活动', 'original' => ''),
    array('url' => 'javascript:popup_message(\'功能建设中，敬请期待\');', 'name' => '最新活动', 'original' => ''),
    array('url' => 'javascript:popup_message(\'功能建设中，敬请期待\');', 'name' => '历史活动', 'original' => ''),
    array('url' => 'javascript:popup_message(\'功能建设中，敬请期待\');', 'name' => '活动纪实', 'original' => ''),
);

assign('functions', $functions);
assign('title', '活动专区');

$smarty->display('empty_functions.phtml');