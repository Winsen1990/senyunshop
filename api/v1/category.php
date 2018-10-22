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
                $get_sub_category_products = 'select p.`id`,p.`img`,p.`name`,p.`desc` from '.$db->table('product').' as p where exists ('.
                    'select m.`product_sn` from '.$db->table('product_category_mapper').' as m where m.`product_sn`=p.`product_sn` and m.`category_id`='.$_sub_category['id'].
                    ')';
                $_sub_category['products'] = $db->fetchAll($get_sub_category_products);
            }

            $_category['sub_categories'] = $sub_categories;
        }
    }

    $response['categories'] = $categories;
}

echo json_encode($response);