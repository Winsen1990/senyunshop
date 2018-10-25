<?php
/**
 * 首页
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/22
 * Time: 17:18
 */
include '../library/api.inc.php';
global $db, $log, $config;

$operation = 'click';
$opera = check_action($operation, getPOST('opera'));

$action = 'view';
$act = check_action($action, getGET('act'));

$response = ['error' => -1, 'message' => ''];

if('click' == $opera) {
    $id = intval(getPOST('id'));

    if($id > 0) {
        $db->upgrade('keywords', ['click_count' => ['exp', '`click_count`+1']], ['id' => $id]);
    }
}

if('view' == $act) {
    $response['keywords'] = $db->all('keywords', ['id', 'keyword'], null, null, ['sort']);
}

echo json_encode($response);