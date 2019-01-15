<?php
/**
 * Created by PhpStorm.
 * User: airpl
 * Date: 2018/10/8
 * Time: 15:45
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$operation = 'add|delete|sync';
$action = 'view';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));

$response = [
    'error' => -1,
    'message' => ''
];

//同步购物车信息
if('sync' == $opera) {
    $cart_list = getPOST('cart');
    $cart_id_list = [];

    if(!is_array($cart_list)) {
        throw new RestFulException('参数错误', 401);
    }

    foreach($cart_list as &$_cart) {
        $_cart['id'] = intval($_cart['id']);
        $_cart['count'] = intval($_cart['count']);

        if($_cart['id'] <= 0) {
            continue;
        }

        if($_cart['count'] <= 0) {
            continue;
        }

        $_cart['checked'] = $_cart['checked'] == 1 ? 1 : 0;

        $db->autoUpdate('cart', [
            'number' => $_cart['count'],
            'checked' => $_cart['checked']
        ], '`account`=\''.$current_user['account'].'\' and `id`='. $_cart['id']);
    }

    $response['error'] = 0;
    $response['message'] = '购物车同步成功';
}

//结算页面
if('view' == $act) {
    $response['address'] = null;
    $response['shipping'] = [];
    $response['cart'] = [];
    $response['coupons'] = [];

    //收货地址
    $address_id = intval(getGET('address_id'));

    if(empty($address_id)) {
        //没有提供地址ID，读取默认地址
        $address_id = $db->getColumn('address', 'id', ['is_default' => 1, 'account' => $current_user['account']]);
    }

    $address_info = null;
    //如有地址ID则获取地址信息
    if($address_id > 0) {
        $address_info = $db->find('address', [
            'id',
            'province',
            'province_name',
            'city',
            'city_name',
            'district',
            'district_name',
            'group',
            'group_name',
            'address',
            'consignee',
            'mobile',
            'zipcode'
        ], ['id' => $address_id, 'account' => $current_user['account']]);

        if(empty($address_info)) {
            $address_info = $db->find('address', [
                'id',
                'province',
                'province_name',
                'city',
                'city_name',
                'district',
                'district_name',
                'group',
                'group_name',
                'address',
                'consignee',
                'mobile',
                'zipcode'
            ], ['account' => $current_user['account'], 'is_default' => 1]);
        }

        if(empty($address_info)) {
            $get_address_detail = 'select p.`province_name`,c.`city_name`,d.`district_name`,g.`group_name`,a.`address`,a.`consignee`,' .
                'a.`province`,a.`city`,a.`district`,a.`group`,g.`group_name`,' .
                'a.`mobile`,a.`zipcode`,a.`id` from ' . $db->table('address') . ' as a, ' . $db->table('province') . ' as p, ' .
                $db->table('city') . ' as c, ' . $db->table('district') . ' as d, ' . $db->table('group') . ' as g where ' .
                'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` and a.`group`=g.`id` and a.`is_default`=1'.
                ' and `account`=\'' . $current_user['account'] . '\'';

            $address_info = $db->fetchRow($get_address_detail);
        }

        $response['address'] = [
            'id' => $address_info['id'],
            'consignee' => $address_info['consignee'],
            'mobile' => $address_info['mobile'],
            'detail' => $address_info['province_name'].' '.$address_info['city_name'].' '.$address_info['district_name'].' '.$address_info['group_name'].' '.$address_info['address']
        ];
    }

    //获取待购买产品
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
    ], ['account' => $current_user['account'], 'checked' => 1]);

    if(empty($cart_list_tmp)) {
        throw new RestFulException('没有可购买的产品', 500);
    }

    //计算物流方式
    $total_weight = 0;
    $total_amount = 0;
    $total_integral = 0;

    $support_delivery = true;
    $inventory_enough = true;
    $shipping = [];
    $lack_inventory = [];
    foreach ($cart_list_tmp as $_cart) {
        //库存检查
        $inventory = $db->getColumn('inventory', 'inventory_logic', ['product_sn' => $_cart['product_sn']]);
        if($inventory < $_cart['number']) {
            $inventory_enough = false;
            array_push($lack_inventory, $_cart['name']);
        }

        if(!$_cart['free_delivery'] && !$_cart['is_gift']) {
            //非赠品且免邮
            $total_weight += $_cart['weight'] * $_cart['number'];
        }

        $total_amount += $_cart['price'] * $_cart['number'];
        $total_integral += $_cart['integral'] * $_cart['number'];

        array_push($response['cart'], [
            'id' => intval($_cart['id']),
            'img' => $_cart['img'],
            'name' => $_cart['name'],
            'count' => intval($_cart['number']),
            'price' => floatval($_cart['price']),
            'p_id' => intval($_cart['product_id'])
        ]);
    }
    $response['lack_inventory'] = $lack_inventory;
    $response['inventory_enough'] = $inventory_enough;

    //运费计算
    $delivery_list_tmp = $db->all('delivery', ['id', 'name'], ['status' => 1]);
    $delivery_list = [];

    if(empty($delivery_list_tmp)) {
        throw new RestFulException('商家尚未设置配送方式', 550);
    }

    foreach($delivery_list_tmp as $delivery) {
        $delivery_list[$delivery['id']] = [
            'id' => $delivery['id'],
            'name' => $delivery['name']
        ];
    }

    if(empty($address_info)) {
        $response['address'] = [
            'id' => 0,
            'consignee' => '',
            'mobile' => '',
            'detail' => ''
        ];
    } else {
        //计算各个物流方式的运费
        //读取地址所属区域
        //1.省市区区域
        $area_ids = $db->all('delivery_area_mapper', ['DISTINCT(`area_id`)'], [
            'province' => $address_info['province'],
            'city' => $address_info['city'],
            'district' => $address_info['district']
        ]);

        //2.省市区域
        if(empty($area_ids)) {
            $area_ids = $db->all('delivery_area_mapper', ['DISTINCT(`area_id`)'], [
                'province' => $address_info['province'],
                'city' => $address_info['city'],
                'district' => 0
            ]);

            //3.省级区域
            if(empty($area_ids)) {
                $area_ids = $db->all('delivery_area_mapper', ['DISTINCT(`area_id`)'], [
                    'province' => $address_info['province'],
                    'city' => 0,
                    'district' => 0
                ]);
            }
        }

        if(empty($area_ids)) {
            $support_delivery = false;
            $response['message'] = '当前地区不支持配送';
        } else {
            //读取区域运费计算规则
            $area_id = [];
            foreach($area_ids as $_area) {
                array_push($area_id, $_area['area_id']);
            }

            $shipping_rules = $db->all('delivery_area', ['delivery_id', 'first_weight', 'next_weight', 'free'], ['id' => ['in', $area_id]]);
            if(empty($shipping_rules)) {
                throw new RestFulException('商家没有设置运费规则', 550);
            }

            $delivery_ids = [];
            foreach($shipping_rules as $rule) {
                array_push($delivery_ids, $rule['delivery_id']);

                if($total_weight > 0) {
                    $shipping_fee = $rule['first_weight'];
                    if($total_weight > 1000) {
                        $shipping_fee += $rule['next_weight'] * ceil($total_weight/1000 - 1);
                    }
                    $shipping_fee -= $rule['free'];

                    if(isset($delivery_list[$rule['delivery_id']]['shipping_fee'])) {
                        $shipping_fee = min($delivery_list[$rule['delivery_id']]['shipping_fee'], $shipping_fee);
                    }

                    $delivery_list[$rule['delivery_id']]['shipping_fee'] = $shipping_fee;
                } else {
                    $delivery_list[$rule['delivery_id']]['shipping_fee'] = 0;
                }
            }

            //剔除不支持的配送方式
            $log->record_array($delivery_list);
            foreach($delivery_list as $i => $delivery) {
                if(!isset($delivery['shipping_fee'])) {
                    unset($delivery_list[$i]);
                }
            }
        }
    }

    foreach($delivery_list as &$_delivery) {
        if(!isset($_delivery['shipping_fee'])) {
            $_delivery['shipping_fee'] = 0;
        }
    }

    //计算优惠券
    $coupons = $db->all('coupon', [

    ], ['account' => $current_user['account']]);

    if($response['message'] == '') {
        $response['error'] = 0;
    }
    $response['total_amount'] = $total_amount;
    $response['total_integral'] = $total_integral;

    $response['shipping'] = array_values($delivery_list);

    //商家信息
    $shop = $db->find('business', ['id', 'shop_name'], ['id' => 1]);
    $shop['name'] = $shop['shop_name'];
    unset($shop['shop_name']);
    $response['shop'] = $shop;
}

echo json_encode($response);