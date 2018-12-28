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
        if($db->autoDelete('cart', '`id`='.$cid.' and `account`=\''.$current_user['account'].'\'')) {
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
        $response['message'] .= "-参数错误\n";
    } else {
        $product_sn = $db->getColumn('product', 'product_sn', ['id' => $product_id]);
    }

    if($number <= 0) {
        $response['message'] .= "-请输入购买数量\n";
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
                'business_account',
                'is_virtual',
                'promote_price',
                'promote_begin',
                'promote_end'
            ];
            $product = $db->find('product', $columns, ['product_sn' => $product_sn]);
            $now = time();

            if($response['message'] == '') {
                //直接购买，屏蔽其他产品
                if($direct_buy) {
                    $db->autoUpdate('cart', ['checked' => 0], '`account`=\''.$current_user['account'].'\'');
                }

                if ($cart) {
                    //更新数量
                    $cart_data = array(
                        'number' => $buy_number,
                        'price' => $product['price'],
                        'integral' => $product['integral'],
                        'checked' => 1,
                        'discount' => 100,
                        'discount_reduce' => 0
                    );

                    if ($product['promote_end'] > $now && $product['promote_begin'] <= $now) {
                        $cart_data['price'] = $product['promote_price'];
                    } else if($member_level) {
                        $original_price = $cart_data['price'];
                        $cart_data['price'] = round($member_level['discount'] * $cart_data['price'] / 100, 2);
                        $cart_data['discount'] = $member_level['discount'];
                        $cart_data['discount_reduce'] = $original_price - $cart_data['price'];
                    }

                    if ($db->autoUpdate('cart', $cart_data, '`id`=' . $cart['id']) !== false) {
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
                        'openid' => $current_user['openid'],
                        'account' => $current_user['account'],
                        'product_sn' => $product_sn,
                        'number' => $buy_number,
                        'add_time' => time(),
                        'price' => $product['price'],
                        'integral' => $product['integral'],
                        'business_account' => $product['business_account'],
                        'attributes' => $attributes,
                        'is_virtual' => $product['is_virtual'],
                        'checked' => 1,
                        'discount' => 100,
                        'discount_reduce' => 0
                    );

                    if ($product['promote_end'] > $now && $product['promote_begin'] <= $now) {
                        $cart_data['price'] = $product['promote_price'];
                    } else if($member_level) {
                        $original_price = $cart_data['price'];
                        $cart_data['price'] = $member_level['discount'] * $cart_data['price'] / 100;
                        $cart_data['discount'] = $member_level['discount'];
                        $cart_data['discount_reduce'] = $original_price - $cart_data['price'];
                    }

                    if ($db->autoInsert('cart', [$cart_data])) {
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

if('view' == $act) {
    $response['error'] = 0;
    $response['cart'] = [];
    $response['message'] = '读取购物车成功';
    //获取购物车产品
    $get_cart_list = 'select c.`checked`,p.`img`,p.`product_type_id`,c.`id`,c.`attributes`,c.`product_sn`,c.`price`,c.`integral`,c.`number`,p.`name`,p.`id` as p_id,c.`business_account` from (' .
        $db->table('cart') . ' as c join ' . $db->table('product') . ' as p using(`product_sn`)) ' .
        ' where c.`account`=\'' . $current_user['account'] . '\' order by c.`business_account`';

    $cart_list_tmp = $db->fetchAll($get_cart_list);

    if ($cart_list_tmp) {
        foreach ($cart_list_tmp as $cart) {
            //获取产品库存
            $inventory_logic = $db->getColumn('inventory', 'inventory_logic', ['product_sn' => $cart['product_sn'], 'attributes' => $cart['attributes']]);

            array_push($response['cart'], [
                'id' => $cart['id'],
                'img' => $cart['img'],
                'p_id' => $cart['p_id'],
                'name' => $cart['name'],
                'count' => intval($cart['number']),
                'price' => floatval($cart['price']),
                'checked' => $cart['checked'] ? true : false,
                'inventory' => intval($inventory_logic)
            ]);
        }
    }
}

if('count' == $act) {
    $response['error'] = 0;
    $response['cart_count'] = 0;
    $response['message'] = '读取购物车成功';
    //获取购物车产品
    $cart = $db->all('cart', ['number'], ['account' => $current_user['account']]);

    if($cart) {
        while($_cart = array_shift($cart)) {
            $response['cart_count'] += $_cart['number'];
        }
    }
}

echo json_encode($response);