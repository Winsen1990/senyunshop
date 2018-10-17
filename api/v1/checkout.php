<?php
/**
 * Created by PhpStorm.
 * User: airpl
 * Date: 2018/10/8
 * Time: 15:45
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$operation = 'add|delete';
$action = 'view';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'), 'view');

$response = [
    'error' => -1,
    'message' => ''
];

//读取地址信息
if(!isset($_SESSION['address_id']) || $_SESSION['address_id'] <= 0)
{
    $_SESSION['address_id'] = $address_id;
} else {
    $address_id = $_SESSION['address_id'];
}

$get_address_detail = 'select a.`is_default`,p.`province_name`,c.`city_name`,d.`district_name`,(select `group_name` from wi_group where id = a.group) as `group_name`,a.`address`,a.`consignee`,'.
    'a.`mobile`,a.`zipcode`,a.`id`,a.`province`,a.`city`,a.`district`,a.`group` from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
    $db->table('city').' as c, '.$db->table('district').' as d where '.
    'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` '.
    ' and a.`account`=\''.$_SESSION['account'].'\' and a.id='.$address_id;
//echo $get_address_detail;exit;
$address_info = $db->fetchRow($get_address_detail);
assign('address_info', $address_info);

//获取待购买产品
$get_cart_list = 'select c.`checked`,p.`img`,p.`product_type_id`,c.`id`,c.`attributes`,c.`product_sn`,c.`price`,c.`integral`,c.`number`,b.`shop_name`,b.`id` as b_id,p.`name`,p.`weight`,c.`business_account` from ('.
    $db->table('cart').' as c join '.$db->table('product').' as p using(`product_sn`)) join '.$db->table('business').
    ' as b on (c.`business_account`=b.`business_account`) where c.`account`=\''.$_SESSION['account'].'\' and c.`checked`=1 order by c.`business_account`';

$cart_list_tmp = $db->fetchAll($get_cart_list);

if(!$cart_list_tmp)
{
    redirect('cart.php');
}

$total_number = 0;
$total_amount = 0;
$total_integral = 0;
$total_delivery_fee = 0;
$total_product_amount = 0;

$cart_list = array();
$can_use_integral = true;
foreach($cart_list_tmp as $cart)
{
    if(!isset($cart_list[$cart['b_id']]))
    {
        $cart_list[$cart['b_id']] = array(
            'b_id' => $cart['b_id'],
            'shop_name' => $cart['shop_name'],
            'products' => array(),
            'business_account' => $cart['business_account']
        );
    }

    //获取产品属性表
    $get_product_attributes = 'select `id`,`name` from '.$db->table('product_attributes').' where `product_type_id`='.$cart['product_type_id'];
    $attributes_tmp = $db->fetchAll($get_product_attributes);
    $attributes_map = array();
    if($attributes_tmp)
    {
        foreach ($attributes_tmp as $a)
        {
            $attributes_map[$a['id']] = $a['name'];
        }
    }

    $check_activity = 'select `activity_id` from '.$db->table('activity_mapper').' where `product_sn`=\''.$cart['product_sn'].'\' and `activity_id`=1';

    if($db->fetchOne($check_activity)) {
        $can_use_integral = false;
    }

    $attributes = json_decode($cart['attributes']);
    $cart['attributes_str'] = '';
    if($attributes)
    {
        foreach ($attributes as $aid => $aval)
        {
            $cart['attributes_str'] .= $attributes_map[$aid] . ':' . $aval . ' ';
        }
    }

    $cart_list[$cart['b_id']]['products'][] = array(
        'c_id' => $cart['id'],
        'product_sn' => $cart['product_sn'],
        'attributes' => $cart['attributes_str'],
        'number' => intval($cart['number']),
        'price' => floatval($cart['price']),
        'integral' => floatval($cart['integral']),
        'name' => $cart['name'],
        'img' => $cart['img']
    );

    $total_product_amount += $cart['price'] * $cart['number'];
    $total_integral += $cart['integral'] * $cart['number'];
    $total_number += $cart['number'];
}

//获取物流信息
$get_product_weight = 'select b.`id` as b_id, sum(p.`weight`*c.`number`) as `total_weight`,b.`business_account` from '.$db->table('cart').' as c'.
    ' join '.$db->table('product').' as p using(`product_sn`) join '.$db->table('business').' as b '.
    ' on c.`business_account`=b.`business_account` where c.`checked`=1 and c.`account`=\''.$_SESSION['account'].'\' '.
    ' and p.`free_delivery`=0 group by c.`business_account`';
$product_weight = $db->fetchAll($get_product_weight);

$get_delivery_sql = 'select da.`id`,da.`first_weight`,da.`next_weight`,da.`free`,da.`delivery_id`,d.`name` from '.$db->table('delivery_area_mapper').
    ' as dam join '.$db->table('delivery_area').' as da on dam.`area_id`=da.`id` join '.$db->table('delivery').
    ' as d on da.`delivery_id`=d.`id` where ';

$delivery_list_json = array();

if($address_info) {
    if ($product_weight) {
        foreach ($product_weight as $weight) {
            $get_delivery = $get_delivery_sql . ' dam.`province`=' . $address_info['province'] . ' and dam.`city`=' . $address_info['city'] . ' and dam.`district`=' . $address_info['district'];
            $get_delivery .= ' and dam.`business_account`=\'' . $weight['business_account'] . '\'';
            $get_delivery .= ' union all ' . $get_delivery_sql . ' dam.`province`=' . $address_info['province'] . ' and dam.`city`=' . $address_info['city'];
            $get_delivery .= ' and dam.`business_account`=\'' . $weight['business_account'] . '\'';
            $get_delivery .= ' union all ' . $get_delivery_sql . ' dam.`province`=' . $address_info['province'];
            $get_delivery .= ' and dam.`business_account`=\'' . $weight['business_account'] . '\'';

            $delivery_list = $db->fetchAll($get_delivery);

            $delivery_list_mapper = array();
            if ($delivery_list) {
                foreach ($delivery_list as $delivery) {
                    $tmp = array(
                        'delivery_id' => $delivery['delivery_id'],
                        'delivery_name' => $delivery['name'],
                        'delivery_fee' => caculate_delivery_fee($delivery['first_weight'], $delivery['next_weight'], $delivery['free'], $weight['total_weight'])
                    );

                    $delivery_list_mapper[] = $tmp;
                    $delivery_list_json[$weight['b_id']][] = $tmp;
                }
                $cart_list[$weight['b_id']]['delivery_list'] = $delivery_list_mapper;
            }

        }
    }
}
$delivery_support = true;
//把运费计入总金额
foreach($cart_list as $key=>$cart)
{
    if(isset($cart['delivery_list']))
    {
        $total_delivery_fee += $cart['delivery_list'][0]['delivery_fee'];
        $delivery_list_json[$cart['b_id']][0]['selected'] = 1;
    } else {
        if($address_info) {
            $get_delivery = $get_delivery_sql . ' dam.`province`=' . $address_info['province'] . ' and dam.`city`=' . $address_info['city'] . ' and dam.`district`=' . $address_info['district'];
            $get_delivery .= ' and dam.`business_account`=\'' . $cart['business_account'] . '\'';
            $get_delivery .= ' union ' . $get_delivery_sql . ' dam.`province`=' . $address_info['province'] . ' and dam.`city`=' . $address_info['city'];
            $get_delivery .= ' and dam.`business_account`=\'' . $cart['business_account'] . '\'';
            $get_delivery .= ' union ' . $get_delivery_sql . ' dam.`province`=' . $address_info['province'];
            $get_delivery .= ' and dam.`business_account`=\'' . $cart['business_account'] . '\'';
            $delivery = $db->fetchRow($get_delivery);

            if ($delivery) {
                //所有产品重量都为0或者所有产品都包邮，获取商家的任意快递方式，并设置运费为0
                $tmp = array(
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_name' => $delivery['name'],
                    'delivery_fee' => 0
                );

                $cart['delivery_list'] = array(
                    $tmp
                );

                $cart_list[$key] = $cart;
                $delivery_list_json[$cart['b_id']][] = $tmp;
            } else {
                //商家不支持该地区的配送，则剔除该产品
                $db->autoUpdate('cart', array('checked' => 0), '`business_account`=\'' . $cart['business_account'] . '\'');
                unset($cart_list[$key]);
                foreach ($cart['products'] as $p) {
                    $total_product_amount -= $p['price'] * $p['number'];
                    $total_number -= $p['number'];
                }
                $delivery_support = false;
            }
        }
    }
}

assign('can_use_integral', $can_use_integral);
if(!$delivery_support)
{
    if(count($cart_list) == 0)
    {
        assign('target', 'cart.php');
    } else {
        assign('target', '');
    }
}
assign('delivery_support', $delivery_support);

$total_amount = $total_product_amount + $total_delivery_fee;
