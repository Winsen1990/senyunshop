<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/10
 * Time: 上午10:06
 */
include 'library/init.inc.php';

$functions = array(
    array('url' => 'javascript:popup_message(\'功能建设中，敬请期待\');', 'name' => '积分捐助', 'original' => ''),
    array('url' => 'javascript:popup_message(\'功能建设中，敬请期待\');', 'name' => '精准扶贫', 'original' => ''),
    array('url' => 'javascript:popup_message(\'功能建设中，敬请期待\');', 'name' => '公益活动', 'original' => ''),
    array('url' => 'javascript:popup_message(\'功能建设中，敬请期待\');', 'name' => '媒体跟踪', 'original' => ''),
);

assign('functions', $functions);
assign('title', '公益专区');

$smarty->display('empty_functions.phtml');