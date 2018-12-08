<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/10
 * Time: 下午5:11
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user, $levels;

$operation = 'add|delete';
$action = 'view|count';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));

$response = [
    'error' => -1,
    'message' => ''
];

if('delete' == $opera) {
    $cid = intval(getPOST('cid'));

    if($cid <= 0) {
        $response['message'] = '403:参数错误';
    }

    if($response['message'] == '') {
        if($db->destroy('cart', ['id' => $cid, 'account' => $current_user['account']])) {
            $response['error'] = 0;
            $response['message'] = '产品已移出购物车';
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }
}

if('add' == $opera) {
    $product_id = intval(getPOST('product_id'));
    $number = intval(getPOST('count'));
    $direct_buy = intval(getPOST('direct'));
    $attributes = '';
    $product_sn = '';

    if ($product_id <= 0) {
        $response['message'] .= "参数错误";
    } else {
        $product_sn = $db->getColumn('product', 'product_sn', ['id' => $product_id]);
    }

    if($number <= 0) {
        $response['message'] .= "请输入购买数量";
    }

    if($response['message'] == '') {
        $cart = $db->find('cart', ['id', 'number'], [
            'product_sn' => $product_sn,
            'account' => $current_user['account'],
            'attributes' => $attributes
        ]);

        $buy_number = $number;

        if($cart) {
            $buy_number += $cart['number'];
        }

        //检查库存
        $inventory_logic = $db->getColumn('inventory', 'inventory_logic', ['product_sn' => $product_sn, 'attributes' => $attributes]);
        $response['inventory_logic'] = $inventory_logic;
        if($inventory_logic < $buy_number) {
            $buy_number = $inventory_logic;
        }

        $member_level = null;
        if(isset($levels[$current_user['level_id']])) {
            $member_level = $levels[$current_user['level_id']];
        }

        if($inventory_logic >= $buy_number)
        {
            //获取产品信息
            $columns = [
                'price',
                'integral',
                'id',
                'product_sn',
                'name',
                'free_delivery',
                'weight'
            ];
            $product = $db->find('product', $columns, ['product_sn' => $product_sn]);
            $now = time();

            if($response['message'] == '') {
                //直接购买，屏蔽其他产品
                if($direct_buy) {
                    $db->upgrade('cart', ['checked' => 0], ['account' => $current_user['account']]);
                }

                if ($cart) {
                    //更新数量
                    $cart_data = array(
                        'number' => $buy_number,
                        'price' => $product['price'],
                        'integral' => $product['integral'],
                        'checked' => 1
                    );

                    if($member_level) {
                        $cart_data['price'] = $member_level['discount'] * $cart_data['price'] / 100;
                    }

                    if ($db->upgrade('cart', $cart_data, ['id' => $cart['id']]) !== false) {
                        $response['error'] = 0;
                        $response['message'] = '加入购物车成功';
                        $response['id'] = $cart['id'];
                        $response['count'] = $buy_number;
                    } else {
                        $response['message'] = '系统繁忙，请稍后再试';
                    }
                } else {
                    //新增记录
                    $cart_data = array(
                        'account' => $current_user['account'],
                        'product_sn' => $product_sn,
                        'product_id' => $product['id'],
                        'name' => $product['name'],
                        'img' => $product['img'],
                        'weight' => $product['weight'],
                        'free_delivery' => $product['free_delivery'],
                        'number' => $buy_number,
                        'add_time' => time(),
                        'price' => $product['price'],
                        'integral' => $product['integral'],
                        'attributes' => $attributes,
                        'is_virtual' => $product['is_virtual'],
                        'checked' => 1
                    );

                    if($member_level) {
                        $cart_data['price'] = $member_level['discount'] * $cart_data['price'] / 100;
                    }

                    if ($db->create('cart', $cart_data)) {
                        $response['error'] = 0;
                        $response['message'] = '加入购物车成功';
                        $response['id'] = $db->get_last_id();
                        $response['count'] = $buy_number;
                    } else {
                        $response['message'] = '系统繁忙，请稍后再试';
                    }
                }
            }
        } else {
            $response['message'] = '产品库存不足';
        }
    }
}

//读取购物车信息
if('view' == $act) {
    $response['error'] = 0;
    $response['cart'] = [];
    $response['message'] = '读取购物车成功';
    //获取购物车产品
    $cart_list_tmp = $db->all('cart', [
        'id',
        'product_id',
        'img',
        'attributes',
        'product_sn',
        'price',
        'integral',
        'number',
        'name',
        'weight',
        'free_delivery',
        'is_gift'
    ], ['account' => $current_user['account']]);

    if ($cart_list_tmp) {
        foreach ($cart_list_tmp as $cart) {
            //获取产品库存
            $inventory_logic = $db->getColumn('inventory', 'inventory_logic', ['product_sn' => $cart['product_sn'], 'attributes' => $cart['attributes']]);

            array_push($response['cart'], [
                'id' => $cart['id'],
                'img' => $cart['img'],
                'p_id' => $cart['product_id'],
                'name' => $cart['name'],
                'count' => intval($cart['number']),
                'price' => floatval($cart['price']),
                'checked' => $cart['checked'] ? true : false,
                'inventory' => intval($inventory_logic)
            ]);
        }
    }

    $shop = $db->find('business', ['id', 'shop_name'], ['id' => 1]);
    $shop['name'] = $shop['shop_name'];
    unset($shop['shop_name']);
    $response['shop'] = $shop;
}

//读取购物车数量
if('count' == $act) {
    $response['error'] = 0;

    $cart_count = $db->getColumn('cart', 'sum(`number`)', ['account' => $current_user['account'], 'is_gift' => 0]);
    $response['cart_count'] = $cart_count;
}

echo json_encode($response);