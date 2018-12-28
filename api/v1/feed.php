<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2018/6/30
 * Time: 19:34
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$operation = 'add|delete|edit|default';
$action = 'view|show|get_default';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));

$response = [
    'error' => -1,
    'message' => ''
];

if($act == 'show') {
    $id = intval(getGET('id'));

    $feed = $db->find('content', ['id', 'title', 'wap_content', 'add_time', 'author'], ['id' => $id]);

    $response['error'] = 0;
    $response['feed'] = $feed;
}


if($act == 'view') {
    $page = intval(getGET('page'));
    $page = max($page, 1);

    $size = 2;
    $limit = ($page - 1) * $size . ',' . $size;

    $feeds = $db->all('content', ['id', 'title', 'original', 'add_time'], null, $limit, [['add_time', 'DESC']]);

    $response['feeds'] = [];

    if ($feeds) {
        $response['feeds'] = $feeds;
    }

    $response['error'] = 0;
}

echo json_encode($response);