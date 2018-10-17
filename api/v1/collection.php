<?php
/**
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/25
 * Time: 14:40
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$id = getPOST('id');
$id = intval($id);

if($id <= 0) {
    throw new RestFulException('参数错误', 400);
}

$product_sn = $db->getColumn('product', 'product_sn', ['id' => $id, 'status' => 4]);

if(empty($product_sn)) {
    throw new RestFulException('产品不存在或已下架', 404);
}

$db->autoReplace('collection', [
    [
        'product_sn' => $product_sn,
        'account' => $current_user['account']
    ]
]);

echo json_encode([
    'error' => 0,
    'message' => '操作完成'
]);