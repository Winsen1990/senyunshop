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
$get_categories = 'select `name`,`id` from '.$db->table('category').' where `parent_id`=0 and `id`<>43';
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
$blocks = $db->all('blocks', ['id', 'name', 'cover'], ['status' => 1], null, ['sort']);
if($blocks) {
    foreach($blocks as $block) {
        $get_assoc_products = 'select p.`id`,p.`product_sn`,p.`name`,p.`price`,p.`img`,m.`sort`,p.`shop_price` as market_price,'.
                                'p.`sale_count` from '.$db->table('product').' as p inner join '.
            $db->table('block_product_mapper').' as m on m.`product_sn`=p.`product_sn` and m.`block_id`='.$block['id'].' where p.`status`=4 order by m.`sort`';

        $assoc_products = $db->fetchAll($get_assoc_products);
        $products = [];
        if($assoc_products) {
            foreach($assoc_products as $assoc_product) {
                $_product = [
                    'id' => intval($assoc_product['id']),
                    'img' => $assoc_product['img'],
                    'name' => $assoc_product['name'],
                    'price' => floatval($assoc_product['price']),
                    'market_price' => floatval($assoc_product['market_price']),
                    'sale_count' => intval($assoc_product['sale_count'])
                ];
                $products[] = $_product;
            }
        }

        $response['blocks'][] = [
            'cover' => $block['cover'],
            'name' => $block['name'],
            'products' => $products
        ];
    }
}

if(count($response['blocks'])) {
    $response['recommend'] = $response['blocks'][0]['products'];
}

if(count($response['blocks']) >= 2) {
    $response['hot'] = $response['blocks'][1]['products'];
}

echo json_encode($response);