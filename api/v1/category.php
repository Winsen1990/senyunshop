<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2018/6/23
 * Time: 22:22
 */
include '../library/api.inc.php';
global $db, $log, $config;

$response = [
    'categories' => [],
    'error' => 0,
    'message' => ''
];

$categories = $db->all('category', ['id', 'name'], ['business_account' => '', 'parent_id' => 0]);

if($categories) {
    $index = 0;
    foreach($categories as &$_category) {
        $_category['id'] = intval($_category['id']);
        $_category['sub_categories'] = [];

        $sub_categories = $db->all('category', ['id', 'name'], ['business_account' => '', 'parent_id' => $_category['id']]);
        if($sub_categories) {
            foreach($sub_categories as &$_sub_category) {
                $_sub_category['id'] = intval($_sub_category['id']);
            }

            $_category['sub_categories'] = $sub_categories;
        }
    }

    $response['categories'] = $categories;
}

echo json_encode($response);