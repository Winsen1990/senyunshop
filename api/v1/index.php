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

$response = [
    'shop' => [],
    'categories' => [],
    'recommend' => [],
    'hot' => [],
    'error' => 0,
    'message' => ''
];

//商户信息
$get_shop = 'select `shop_name` as name,`shop_logo` as logo from '.$db->table('business').' where `id`=1';
$shop = $db->fetchRow($get_shop);
if($shop) {
    $response['shop'] = $shop;
}

//顶级分类
$get_categories = 'select `name`,`id` from '.$db->table('category').' where `parent_id`=0';
$categories = $db->fetchAll($get_categories);
if($categories) {
    foreach($categories as &$_category) {
        $_category['id'] = intval($_category['id']);
    }

    $response['categories'] = $categories;
}

//首页轮播广告
//$ads = $db->all('ad', ['url', 'img', 'alt', 'order_view'], ['ad_pos_id' => 1], ['order_view']);
//
//if(!empty($ads)) {
//    while($ad = array_shift($ads)) {
//        array_push($response['ads'], [
//            'url' => $ad['img'],
//            'link' => $ad['url'],
//            'alt' => $ad['alt'],
//            'sort' => $ad['order_view']
//        ]);
//    }
//}

//推荐产品
$get_recommend_products = 'select p.`id`,p.`name`,p.`price`,p.`shop_price` as market_price,p.`sale_count`,p.`img` from '.
                          $db->table('product').' as p join '.$db->table('activity_mapper').' as a using(`product_sn`) '.
                          ' where a.`activity_id`=1 and p.`status`=4 order by `order_view` limit 10';

$recommend_products = $db->fetchAll($get_recommend_products);

if($recommend_products) {
    foreach ($recommend_products as &$_r_product) {
        $_r_product['id'] = intval($_r_product['id']);
        $_r_product['price'] = floatval($_r_product['price']);
        $_r_product['market_price'] = floatval($_r_product['market_price']);
    }

    $response['recommend'] = $recommend_products;
}

//热卖产品
$get_hot_products = 'select `id`,`name`,`price`,`shop_price` as market_price,`sale_count`,`img` from '.
    $db->table('product').' where `status`=4 order by `sale_count` DESC limit 10';

$hot_products = $db->fetchAll($get_hot_products);
if($hot_products) {
    foreach($hot_products as &$_h_product) {
        $_h_product['id'] = intval($_h_product['id']);
        $_h_product['price'] = floatval($_h_product['price']);
        $_h_product['market_price'] = floatval($_h_product['market_price']);
    }

    $response['hot'] = $hot_products;
}

echo json_encode($response);