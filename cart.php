<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/10
 * Time: 下午5:11
 */
include 'library/init.inc.php';

$operation = 'add_to_cart|delete';

$opera = check_action($operation, getPOST('opera'));

if('delete' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $cid = intval(getPOST('cid'));

    if(!check_cross_domain())
    {
        if($cid <= 0)
        {
            $response['msg'] = '403:参数错误';
        }

        if($response['msg'] == '')
        {
            if($db->autoDelete('cart', '`id`='.$cid.' and `account`=\''.$_SESSION['account'].'\''))
            {
                $response['error'] = 0;
                $response['msg'] = '产品已移出购物车';
            } else {
                $response['msg'] = '001:系统繁忙，请稍后再试';
            }
        }
    } else {
        $response['msg'] = '404:参数错误';
    }

    echo json_encode($response);
    exit;
}

if('add_to_cart' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $product_sn = getPOST('product_sn');
    $number = intval(getPOST('number'));
    $attributes = getPOST('attributes');

    if(!check_cross_domain() && isset($_SESSION['account']) && $_SESSION['account'])
    {
        if ($product_sn == '')
        {
            $response['msg'] .= "-参数错误\n";
        } else {
            $product_sn = $db->escape($product_sn);
        }

        if($number <= 0)
        {
            $response['msg'] .= "-请输入购买数量\n";
        }

        if($attributes != '' && count($attributes) > 0)
        {
            $attributes_tmp = $attributes;
            $attributes = '{';
            foreach($attributes_tmp as $id=>$value)
            {
                $attributes .= '"'.$id.'":"'.$db->escape($value).'",';
            }
            $attributes = substr($attributes, 0, strlen($attributes)-1);
            $attributes .= '}';
            $attributes = $db->escape($attributes);
        } else {
            $attributes = '';
        }

        if($response['msg'] == '') {
            $check_cart = 'select `id`,`number` from ' . $db->table('cart') .
                          ' where `product_sn`=\'' . $product_sn . '\' and `account`=\'' . $_SESSION['account'] . '\' '.
                          ' and `attributes`=\''.$attributes.'\'';
            $cart = $db->fetchRow($check_cart);

            $buy_number = $number;

            if($cart)
            {
                $buy_number += $cart['number'];
            }

            //检查库存
            $check_inventory = 'select `inventory_logic` from '.$db->table('inventory').
                               ' where `attributes`=\''.$attributes.'\' and `product_sn`=\''.$product_sn.'\'';

            $log->record($check_inventory);
            $inventory_logic = $db->fetchOne($check_inventory);
            $response['inventory_logic'] = $inventory_logic;
            if($inventory_logic < $buy_number)
            {
                $buy_number = $inventory_logic;
            }

            if($inventory_logic >= $buy_number)
            {
                //获取产品信息
                $get_product = 'select `price`,`integral`,`business_account`,`is_virtual`,`promote_price`,`promote_begin`,`promote_end` from '.$db->table('product').
                               ' where `product_sn`=\''.$product_sn.'\'';
                $product = $db->fetchRow($get_product);
                $now = time();

                //获取产品砍价总额
                $get_product_discount = 'select sum(`reduce`) from '.$db->table('discount').
                                        ' where `product_sn`=\''.$product_sn.'\' and `owner`=\''.$_SESSION['account'].'\'';

                $discount = $db->fetchOne($get_product_discount);

                //检查产品是否非商业会员不可购买
                $get_level_id = 'select `level_id` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
                $level_id = $db->fetchOne($get_level_id);

                if($level_id <= 0) {
                    $get_product_activity = 'select `activity_id` from ' . $db->table('activity_mapper') . ' where `product_sn`=\'' . $product_sn . '\' and `activity_id`=3';
                    $product_activity = $db->fetchOne($get_product_activity);

                    if($product_activity) {
                        $response['msg'] = '只有商业会员才能购买';
                    }
                }

                if($response['msg'] == '') {
                    if ($cart) {
                        //更新数量
                        $cart_data = array(
                            'number' => $buy_number,
                            'price' => $product['price'] - $discount,
                            'integral' => $product['integral']
                        );

                        if ($product['promote_end'] > $now && $product['promote_begin'] <= $now) {
                            $cart_data['price'] = $product['promote_price'];
                        }

                        if ($db->autoUpdate('cart', $cart_data, '`id`=' . $cart['id'])) {
                            $response['error'] = 0;
                            $response['msg'] = '加入购物车成功';
                        } else {
                            $response['msg'] = '001:系统繁忙，请稍后再试';
                        }
                    } else {
                        //新增记录
                        $cart_data = array(
                            'openid' => $_SESSION['openid'],
                            'account' => $_SESSION['account'],
                            'product_sn' => $product_sn,
                            'number' => $buy_number,
                            'add_time' => time(),
                            'price' => $product['price'] - $discount,
                            'integral' => $product['integral'],
                            'business_account' => $product['business_account'],
                            'attributes' => $attributes,
                            'is_virtual' => $product['is_virtual']
                        );

                        if ($product['promote_end'] > $now && $product['promote_begin'] <= $now) {
                            $cart_data['price'] = $product['promote_price'];
                        }

                        if ($db->autoInsert('cart', array($cart_data))) {
                            $response['error'] = 0;
                            $response['msg'] = '加入购物车成功';
                        } else {
                            $response['msg'] = '001:系统繁忙，请稍后再试';
                        }
                    }
                }
            } else {
                $response['msg'] = '产品库存不足';
            }
        }
    } else {
        if(empty($_SESSION['account']))
        {
            $response['msg'] = '请先登录';
            $response['error'] = 2;
        } else {
            $response['msg'] = '404:参数错误';
        }
    }

    echo json_encode($response);
    exit;
}

//获取购物车产品
$get_cart_list = 'select c.`checked`,p.`img`,p.`product_type_id`,c.`id`,c.`attributes`,c.`product_sn`,c.`price`,c.`integral`,c.`number`,b.`shop_name`,b.`id` as b_id,p.`name`,p.`id` as p_id,c.`business_account` from ('.
                 $db->table('cart').' as c join '.$db->table('product').' as p using(`product_sn`)) join '.$db->table('business').
                 ' as b on (c.`business_account`=b.`business_account`) where c.`account`=\''.$_SESSION['account'].'\' order by c.`business_account`';

$cart_list_tmp = $db->fetchAll($get_cart_list);

$total_amount = 0;
$total_integral = 0;

$cart_list = array();
$cart_json = array();
if($cart_list_tmp)
{
    foreach ($cart_list_tmp as $cart)
    {
        if (!isset($cart_list[$cart['b_id']]))
        {
            $cart_list[$cart['b_id']] = array(
                'b_id' => $cart['b_id'],
                'shop_name' => $cart['shop_name'],
                'products' => array(),
                'business_account' => $cart['business_account']
            );
        }

        $cart['checked'] = $cart['checked'] ? true : false;

        //获取产品属性表
        $get_product_attributes = 'select `id`,`name` from ' . $db->table('product_attributes') . ' where `product_type_id`=' . $cart['product_type_id'];
        $attributes_tmp = $db->fetchAll($get_product_attributes);
        $attributes_map = array();
        if($attributes_tmp)
        {
            foreach ($attributes_tmp as $a)
            {
                $attributes_map[$a['id']] = $a['name'];
            }
        } else {
            $attributes_map = '';
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

        //获取产品库存
        $get_inventory = 'select `inventory_logic` from '.$db->table('inventory').' where `product_sn`=\''.$cart['product_sn'].'\' and `attributes`=\''.$cart['attributes'].'\'';
        $inventory = $db->fetchOne($get_inventory);

        $cart_list[$cart['b_id']]['products'][] = array(
            'c_id' => $cart['id'],
            'product_sn' => $cart['product_sn'],
            'attributes' => $cart['attributes_str'],
            'number' => intval($cart['number']),
            'price' => floatval($cart['price']),
            'integral' => floatval($cart['integral']),
            'name' => $cart['name'],
            'img' => $cart['img'],
            'checked' => $cart['checked'],
            'inventory' => $inventory,
            'p_id' => $cart['p_id']
        );

        $cart_json[$cart['id']] = array(
            'c_id' => $cart['id'],
            'product_sn' => $cart['product_sn'],
            'number' => intval($cart['number']),
            'price' => floatval($cart['price']),
            'integral' => floatval($cart['integral']),
            'b_id' => $cart['b_id'],
            'checked' => $cart['checked'],
            'inventory' => intval($inventory)
        );

        if ($cart['checked'])
        {
            $total_amount += $cart['price'] * $cart['number'];
            $total_integral += $cart['integral'] * $cart['number'];
        }
    }
}

assign('total_amount', $total_amount);
assign('total_integral', $total_integral);
assign('total_number', count($cart_list_tmp));
assign('cart_list', $cart_list);
assign('cart_json', json_encode($cart_json));
$smarty->display('cart.phtml');