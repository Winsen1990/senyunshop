<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/21
 * Time: 上午11:07
 */
global $plugins;

$i = isset($plugins) ? count($plugins) : 0;
$plugins[$i]['name'] = '公司快递';//插件名称
$plugins[$i]['desc'] = '公司使用签约的物流公司进行配送';//插件描述
$plugins[$i]['self_delivery'] = 0;//不支持自提
$plugins[$i]['plugins'] = 'normal.class.php';