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
    'banners' => [],
    'functions' => [],
    'blocks' => [],
    'error' => 0,
    'message' => ''
];

//首页轮播广告
$banners = $db->all('ad', ['url', 'img', 'alt', 'order_view'], ['ad_pos_id' => 1], ['order_view']);

if(!empty($banners)) {
    while($banner = array_shift($banners)) {
        array_push($response['banners'], [
            'img' => $banner['img'],
            'url' => $banner['url']
        ]);
    }
}

//功能区
$functions = $db->all('category', ['id', 'icon', 'name'], ['parent_id' => 15], ['order_view']);
if(!empty($functions)) {
    foreach($functions as &$_function) {
        $_function['id'] = intval($_function);
        array_push($response['functions'], $_function);
    }
}

//产品专区
$blocks = $db->all('blocks', ['id', 'name', 'cover'], ['status' => 1], null, ['sort']);
if($blocks) {
    foreach($blocks as $block) {
        $get_assoc_products = 'select p.`id`,p.`product_sn`,p.`name`,p.`price`,p.`img`,m.`sort`,p.`shop_price` as market_price,'.
                                'p.`sale_count` from '.$db->table('product').' as p inner join '.
            $db->table('block_product_mapper').' as m on m.`product_sn`=p.`product_sn` and m.`block_id`='.$block['id'].' order by m.`sort`';

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

echo json_encode($response);