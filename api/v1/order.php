<?php
/**
 * Created by PhpStorm.
 * User: Winsen
 * Date: 2018/6/26
 * Time: 7:46
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user, $loader;
$loader->includeScript('transaction');

$operation = 'add|delete|edit|pay|cancel|receive|rollback|comment|product_comment';
$action = 'view|show|express_info|detail';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));

$response = [
    'error' => -1,
    'message' => ''
];

if('product_comment' == $opera)
{
    $response = array('error'=>1, 'message'=>'');

    $product_sn = getPOST('product_sn');
    $star = getPOST('star');
    $comment = getPOST('comment');
    $detail_id = intval(getPOST('detail_id'));

    if($product_sn == '')
    {
        $response['message'] = '参数错误';
    } else {
        $product_sn = $db->escape($product_sn);
    }

    $star = intval($star);
    if($star <= 0)
    {
        $response['message'] .= '-请选择星级<br/>';
    }

    if($comment == '')
    {
        $response['message'] .= '-请填写评价<br/>';
    } else {
        $comment = htmlspecialchars($comment);
        $comment = $db->escape($comment);
    }

    if($response['message'] == '')
    {
        $comment_data = array(
            'add_time' => time(),
            'comment' => $comment,
            'star' => $star,
            'product_sn' => $product_sn,
            'account' => $current_user['account'],
            'parent_id' => 0
        );

        if($db->autoInsert('comment', array($comment_data)))
        {
            $id = $db->get_last_id();

            $comment_data = array('path'=>$id.',');

            $db->autoUpdate('comment', $comment_data, '`id`='.$id);

            //获取评论数量和星级
            $get_comment_info = 'select count(*) as c, sum(`star`) as star from '.$db->table('comment').' where `product_sn`=\''.$product_sn.'\'';
            $comment_info = $db->fetchRow($get_comment_info);

            $product_data = [
                'star' => $comment_info['star']/$comment_info['c'],
                'comment_count' => ['exp', '`comment_count`+1']
            ];

            $db->upgrade('product', $product_data, ['product_sn' => $product_sn]);

            $db->upgrade('order_detail', ['is_comment' => 1], ['id' => $detail_id]);

            $response['error'] = 0;
            $response['message'] = '评论成功';
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

if('comment' == $opera)
{
    $order_sn = getPOST('order_sn');
    $star = getPOST('star');
    $star = intval($star);

    $response = array('error'=>1, 'message'=>'');

    if($order_sn == '')
    {
        $response['message'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_business_account = 'select `business_account` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$current_user['account'].'\'';
        $business_account = $db->fetchOne($check_business_account);

        if($business_account)
        {
            //获取历史已评价订单数量
            $get_comment_order = 'select count(*) from '.$db->table('order').' where `business_account`=\''.$business_account.'\' and `is_comment`=1';
            $comment_order = $db->fetchOne($get_comment_order);

            //获取店铺当前评价
            $get_comment = 'select `comment` from '.$db->table('business').' where `business_account`=\''.$business_account.'\'';
            $comment = $db->fetchOne($get_comment);

            $comment = $comment*$comment_order;
            $comment_order++;
            $comment += $star;

            $comment = $comment/$comment_order;

            $data = array('comment'=>$comment);

            if($db->autoUpdate('business', $data, '`business_account`=\''.$business_account.'\''))
            {
                $order_data = array('is_comment'=>1, 'status'=>12);

                if($db->autoUpdate('order', $order_data, '`order_sn`=\''.$order_sn.'\''))
                {
                    add_order_log($order_sn, $current_user['account'], 12, '用户评价订单');
                    $response['message'] = '服务评价成功';
                    $response['error'] = 0;
                } else {
                    $response['message'] = '系统繁忙，请稍后再试';
                }
            } else {
                $response['message'] = '系统繁忙，请稍后再试';
            }
        } else {
            $response['message'] = '订单错误';
        }
    }

    echo json_encode($response);
    exit;
}

if('rollback' == $opera)
{
    $order_sn = getPOST('order_sn');
    $serve_type = intval(getPOST('serve_type'));
    $serve_type = max(1, $serve_type);
    $serve_type = min(4, $serve_type);

    /**
     * serve_type
     * 1: 退款
     * 2: 退款退货
     * 3: 换货
     * 4: 补货
     */

    if($order_sn == '')
    {
        $response['message'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_order_sn = 'select `order_sn` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$current_user['account'].'\'';
        $order_sn = $db->fetchOne($check_order_sn);

        if($order_sn)
        {
            $data = array(
                'status' => 8,
                'serve_type' => $serve_type
            );

            if($db->autoUpdate('order', $data, '`order_sn`=\''.$order_sn.'\''))
            {
                add_order_log($order_sn, $current_user['account'], 8, '用户申请退单');

                $response['error'] = 0;
                $response['message'] = '申请已提交';
            } else {
                $response['message'] = '系统繁忙，请稍后再试';
            }
        } else {
            $response['message'] = '订单错误';
        }
    }
}

if('receive' == $opera)
{
    $order_sn = getPOST('sn');

    if($order_sn == '')
    {
        $response['message'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_order_sn = 'select `order_sn` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$current_user['account'].'\'';
        $order_sn = $db->fetchOne($check_order_sn);

        if($order_sn)
        {
            $data = array(
                'status' => 7,
                'receive_time' => time()
            );

            if($db->autoUpdate('order', $data, '`order_sn`=\''.$order_sn.'\'') && $db->get_affect_rows())
            {
                add_order_log($order_sn, $current_user['account'], 7, '用户确认收货');
                //将款项打到商家账户中
                //读取担保交易记录，并将款项打入到商家的余额中
                $get_trade = 'select `id`,`business_account`,`trade` from '.$db->table('trade').' where `remark`=\''.$order_sn.'\' and `status`=0';
                $trade = $db->fetchRow($get_trade);

                if(add_business_exchange($trade['business_account'], $trade['trade'], -1*$trade['trade'], $current_user['account'], '用户确认收货'))
                {
                    $trade_status = array(
                        'status' => 1,
                        'solve_time' => time()
                    );

                    $db->autoUpdate('trade', $trade_status, '`id`='.$trade['id']);
                }

                //读取佣金,将佣金发放到用户账户里
                $get_member_rewards = 'select `id`,`remark`,`integral`,`account`,`reward` from '.$db->table('member_reward').' where `status`=0 and `assoc`=\''.$order_sn.'\'';
                $member_rewards = $db->fetchAll($get_member_rewards);
                if($member_rewards) {
                    foreach ($member_rewards as $reward) {
                        if (add_memeber_exchange_log($reward['account'], 0, $reward['reward'], $reward['integral'], -1 * $reward['integral'], -1 * $reward['reward'], $current_user['account'], $order_sn . '奖金发放')) {
                            $reward_status = array(
                                'status' => 1,
                                'solve_time' => time()
                            );

                            $db->autoUpdate('member_reward', $reward_status, '`id`=' . $reward['id']);
                        }
                    }
                }

                $response['error'] = 0;
                $response['message'] = '确认收货成功';
            } else {
                $response['message'] = '系统繁忙，请稍后再试';
            }
        } else {
            $response['message'] = '订单错误';
        }
    }
}

if('cancel' == $opera)
{
    $order_sn = getPOST('sn');

    if($order_sn == '')
    {
        $response['message'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_order_sn = 'select `order_sn` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$current_user['account'].'\'';
        $order_sn = $db->fetchOne($check_order_sn);

        if($order_sn)
        {
            $db->begin();
            //回退库存
            //回退积分/佣金/余额
            //改变订单状态
            $data = array(
                'status' => 11,
            );

            if($db->autoUpdate('order', $data, '`order_sn`=\''.$order_sn.'\''))
            {
                $response['error'] = 0;
                $response['message'] = '订单取消成功';
                $db->commit();

            } else {
                $response['message'] = '001:取消订单失败';
                $db->rollback();
            }
        } else {
            $response['message'] = '订单错误';
        }
    }
}

if('pay' == $opera)
{
    $order_sn = getPOST('order_sn');

    if($order_sn == '')
    {
        $response['message'] = '订单编号为空';
    } else {
        $order = $db->find('order', '*', ['order_sn' => $order_sn, 'account' => $current_user['account']]);

        if($order)
        {
            if($order['status'] != 1) {
                $response['message'] = '订单已支付';
            } else {
                $response['error'] = 0;
                $response['order_sn'] = $order['order_sn'];

                $mch_id = $config['mch_id'];
                $mch_key = $config['mch_key'];

                $total_fee = $order['amount'];
                $detail = '订单:' . $order['order_sn'];

                $response['price'] = '￥' . sprintf('%.2f', $total_fee);

                $body = $config['site_name'] . '订单收款';
                $body = $detail;
                $out_trade_no = $order['order_sn'];
                $openid = $current_user['openid'];

                $res = create_prepay($config['mini_appid'], $mch_id, $mch_key, $openid, $total_fee, $body, $detail, $out_trade_no);

                $res = simplexml_load_string($res,'SimpleXMLElement', LIBXML_NOCDATA);
                $res = json_encode($res);
                $res = json_decode($res, true);

                $nonce_str = get_nonce_str();
                $response['nonce_str'] = $nonce_str;
                $time_stamp = time();

                //最后参与签名的参数有appId, timeStamp, nonceStr, package, signType。
                $sign = 'appId=' . $config['mini_appid'] . '&nonceStr=' . $nonce_str . '&package=prepay_id=' . $res['prepay_id'] . '&signType=MD5&timeStamp=' . $time_stamp . '&key=' . $mch_key;
                $sign = md5($sign);
                $sign = strtoupper($sign);
                $response['timestamp'] = $time_stamp;
                $response['sign'] = $sign;
                $response['prepay_id'] = isset($res['prepay_id']) ? $res['prepay_id'] : '';
            }
        } else {
            $response['message'] = '订单错误';
        }
    }
}

if('express_info' == $act)
{
    $express_state = array(
        0 => '在途',
        1 => '揽件',
        2 => '疑难',
        3 => '签收',
        4 => '退签',
        5 => '派件',
        6 => '退回'
    );

    $order_sn = getGET('sn');

    if($order_sn == '')
    {
        $response['message'] = '参数错误';
    } else {
        $response['error'] = 0;
        $response['message'] = '';

        $order_sn = $db->escape($order_sn);

        $get_order_info = 'select * from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';
        $order = $db->fetchRow($get_order_info);

        if($order && $order['status'] == 6)
        {
            $get_express_info = 'select `code`,`name` from '.$db->table('express').' where `id`='.$order['express_id'];
            $express_info = $db->fetchRow($get_express_info);
            $express_flow = query_express($express_info['code'], $order['express_sn']);
            $express_flow = json_decode($express_flow, true);

            $response['express'] = [
                'name' => $express_info['name'],
                'status' => $express_flow['status'] == 200 ? $express_state[$express_flow['state']] : '暂无物流信息',
                'info' => $express_flow['data']
            ];
        }

        $get_order_detail = 'select p.`img` from '.$db->table('order_detail').' as od join '.$db->table('product').
            ' as p using(`product_sn`) where od.`order_sn`=\''.$order_sn.'\'';

        $product_image = $db->fetchOne($get_order_detail);

        $response['order'] = [
            'image' => $product_image,
            'express_sn' => $order['express_sn']
        ];
    }
}

if('add' == $opera)
{
    $address_id = intval(getPOST('address_id'));
    $shipping_id = intval(getPOST('shipping_id'));
    $payment_id = 1;
    $use_integral = getPOST('use_integral') == 'true' ? true : false;
    $use_balance = getPOST('use_balance') == 'true' ? true : false;
    $use_reward = getPOST('use_reward') == 'true' ? true : false;
    $comments = getPOST('remark');
    $message_notice = getPOST('message_notice') == 'true' ? 1 : 0;

    $get_address_detail = 'select p.`province_name`,c.`city_name`,d.`district_name`,g.`group_name`,a.`address`,a.`consignee`,'.
        'a.`province`,a.`city`,a.`district`,a.`group`,g.`group_name`,'.
        'a.`mobile`,a.`zipcode`,a.`id` from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
        $db->table('city').' as c, '.$db->table('district').' as d, '.$db->table('group').' as g where '.
        'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` and a.`group`=g.`id` and a.`id`='.$address_id.
        ' and `account`=\''.$current_user['account'].'\'';

    $address_info = $db->fetchRow($get_address_detail);
    //获取待购买产品
    $get_cart_list = 'select p.`given_integral`,p.`reward`,p.`integral_given`,b.`business_account`,c.`checked`,p.`img`,p.`product_type_id`,'.
                     'c.`id`,c.`attributes`,c.`product_sn`,c.`price`,c.`integral`,c.`number`,b.`shop_name`,b.`id` as b_id,p.`name`,'.
                     'p.`weight`,p.`is_virtual` from ('.
        $db->table('cart').' as c join '.$db->table('product').' as p using(`product_sn`)) join '.$db->table('business').
        ' as b on (c.`business_account`=b.`business_account`) where c.`account`=\''.$current_user['account'].'\' and c.`checked`=1 order by c.`business_account`';

    $cart_list_tmp = $db->fetchAll($get_cart_list);

    if(empty($cart_list_tmp)) {
        throw new RestFulException('没有可购买的产品', 500);
    }

    $total_amount = 0;
    $total_integral = 0;
    $total_delivery_fee = 0;
    $total_product_amount = 0;
    $total_reward = 0;
    $total_integral_given = 0;

    $can_use_integral = true;

    //按商家分离订单详情，计算订单总价
    $cart_list = array();
    foreach($cart_list_tmp as $cart)
    {
        if(!isset($cart_list[$cart['b_id']]))
        {
            $shipping_rule = array(
                'first_weight' => 0,
                'next_weight' => 0,
                'free' => 0
            );

            //获取运费计算规则
            //1.物流ID关联区域
            $delivery_area_ids = $db->all('delivery_area', ['id'], ['delivery_id' => $shipping_id]);
            if($delivery_area_ids) {
                $area_ids = [];
                foreach($delivery_area_ids as $_area) {
                    array_push($area_ids, $_area['id']);
                }

                //2.按顺次查询最匹配区域
                $rule = $db->find('delivery_area_mapper', ['area_id'], [
                    'province' => $address_info['province'],
                    'city' => $address_info['city'],
                    'district' => $address_info['district'],
                    'area_id' => ['in', $area_ids]
                ]);

                if(empty($rule)) {
                    $rule = $db->find('delivery_area_mapper', ['area_id'], [
                        'province' => $address_info['province'],
                        'city' => $address_info['city'],
                        'district' => 0,
                        'area_id' => ['in', $area_ids]
                    ]);

                    if(empty($rule)) {
                        $rule = $db->find('delivery_area_mapper', ['area_id'], [
                            'province' => $address_info['province'],
                            'city' => 0,
                            'district' => 0,
                            'area_id' => ['in', $area_ids]
                        ]);
                    }
                }

                if(!empty($rule)) {
                    $shipping_rule = $db->find('delivery_area', ['first_weight', 'next_weight', 'free'], ['id' => $rule['area_id']]);
                }
            }

            $cart_list[$cart['b_id']] = array(
                'business_account' => $cart['business_account'],
                'shop_name' => $cart['shop_name'],
                'products' => array(),
                'total_amount' => 0,
                'total_product_amount' => 0,
                'total_integral' => 0,
                'total_delivery_fee' => 0,
                'total_integral_given' => 0,
                'total_given_integral' => 0,
                'total_reward' => 0,
                'total_weight' => 0,
                'integral_paid' => 0,
                'balance_paid' => 0,
                'reward_paid' => 0,
                'remark' => $db->escape($comments),
                'shipping_rule' => $shipping_rule
            );
        }

        $check_activity = 'select `activity_id` from '.$db->table('activity_mapper').' where `product_sn`=\''.$cart['product_sn'].'\' and `activity_id`=1';
        if($db->fetchOne($check_activity)) {
            $can_use_integral = false;
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
            'product_sn' => $cart['product_sn'],
            'product_attributes' => $cart['attributes_str'],
            'count' => intval($cart['number']),
            'product_price' => floatval($cart['price']),
            'integral' => floatval($cart['integral']),
            'integral_given' => floatval($cart['integral_given']),
            'given_integral' => floatval($cart['given_integral']),
            'product_name' => $cart['name'],
            'business_account' => $cart['business_account'],
            'reward' => floatval($cart['reward']),
            'attributes' => $cart['attributes'],
            'inventory' => $inventory,
            'is_virtual' => $cart['is_virtual']
        );

        $cart_list[$cart['b_id']]['total_amount'] += $cart['price'] * $cart['number'];
        $cart_list[$cart['b_id']]['total_product_amount'] += $cart['price'] * $cart['number'];
        $cart_list[$cart['b_id']]['total_integral'] += $cart['integral'] * $cart['number'];
        $cart_list[$cart['b_id']]['total_integral_given'] += $cart['integral_given'] * $cart['number'];
        $cart_list[$cart['b_id']]['total_given_integral'] += $cart['given_integral'] * $cart['number'];
        $cart_list[$cart['b_id']]['total_weight'] += $cart['is_virtual'] ? 0 : $cart['weight'] * $cart['number'];
        $cart_list[$cart['b_id']]['total_reward'] += $cart['reward'] * $cart['number'];
    }

    foreach($cart_list as &$_cart) {
        $_cart['delivery_id'] = $shipping_id;
        $shipping_fee = $_cart['shipping_rule']['first_weight'];

        if($_cart['total_weight'] > 1000) {
            $shipping_fee += $_cart['shipping_rule']['next_weight'] * ceil($_cart['total_weight']/1000 - 1);
        }
        $shipping_fee -= $_cart['shipping_rule']['free'];

        $_cart['total_amount'] += $shipping_fee;
        $_cart['total_delivery_fee'] = $shipping_fee;
    }

    //读取用户信息
    $get_user_info = 'select `integral`,`reward`,`balance`,`path`,`account`,`level_id`,`nickname`,`headimg`,`openid` from '.$db->table('member').' where `account`=\''.$current_user['account'].'\'';
    $user_info = $db->fetchRow($get_user_info);

    $integral_paid = 0;
    $reward_paid = 0;
    $balance_paid = 0;
    $order_count = count($cart_list);//订单数量

    $user_integral = $user_info['integral'];
    $user_reward = $user_info['reward'];
    $user_balance = $user_info['balance'];

    //计算用户使用积分、奖金、余额
    //采用依次减到完的方式进行计算
    foreach($cart_list as $key=>$cart)
    {
        $log->record('开始计算使用积分/余额/奖金');
        if($cart['total_integral'])
        {
            $cart['integral_paid'] = $cart['total_integral'];
            $use_integral -= $cart['integral_paid'];
        }

        if ($use_integral && $can_use_integral && $cart['total_amount'] > 0 && $user_integral)
        {
            if ($cart['total_amount'] >= $user_integral / $config['integral_rate'])
            {
                $cart['total_amount'] -= $user_integral / $config['integral_rate'];
                $cart['integral_paid'] = $user_integral;
                $user_integral = 0;
            } else {
                $cart['integral_paid'] = $cart['total_amount'] * $config['integral_rate'];
                $cart['total_amount'] = 0;
                $user_integral -= $cart['integral_paid'];
            }
        }

        if ($use_reward && $cart['total_amount'] > 0 && $user_reward) {
            if ($cart['total_amount'] >= $user_reward / $config['reward_rate']) {
                $cart['total_amount'] -= $user_reward / $config['reward_rate'];
                $cart['reward_paid'] = $user_reward;
                $user_reward = 0;
            } else {
                $cart['reward_paid'] = $cart['total_amount'] * $config['reward_rate'];
                $cart['total_amount'] = 0;
                $user_reward -= $cart['reward_paid'];
            }
        }

        if ($use_balance && $cart['total_amount'] > 0 && $user_balance) {
            if ($cart['total_amount'] >= $user_balance) {
                $cart['total_amount'] -= $user_balance;
                $cart['balance_paid'] = $user_balance;
                $user_balance = 0;
            } else {
                $cart['balance_paid'] = $cart['total_amount'];
                $cart['total_amount'] = 0;
                $user_balance -= $cart['balance_paid'];
            }
        }
        $cart_list[$key] = $cart;
        $log->record_array($cart);
    }

    $response['count'] = 0;
    $response['order_status'] = 0;
    $response['status'] = 0;
    $log->record_array($cart_list);
    //插入订单
    foreach($cart_list as $cart)
    {
        foreach($cart_list as $cart_)
        {
            foreach($cart_['products'] as $od)
            {
                if($od['inventory'] < $od['count'])
                {
                    $response['status'] = 1;
                    $response['message'] = '部分产品缺货';
                    break;
                }
            }

            if($response['status'] == 1)
            {
                break;
            }
        }

        if($response['status'] == 1)
        {
            break;
        }

        $db->begin();
        $status = 1;
        $response['order_status'] = 1;
        if($cart['total_amount'] == 0)
        {
            $status = 4;
            $response['order_status'] = 4;
        }
        $business_account = $cart['business_account'];

        $order_sn = add_order($cart['total_integral'], $cart['total_product_amount'], $cart['total_delivery_fee'], $cart['delivery_id'],
            $business_account, $cart['total_integral_given'], $cart['total_given_integral'], $payment_id, $address_id, $cart['total_reward'],
            $current_user['account'], $cart['integral_paid'], $cart['reward_paid'], $cart['balance_paid'], $status, 0, $cart['remark'], 0, $message_notice);

        if($order_sn)
        {
            if($status == 4)
            {
                $order_data = array('pay_time'=>time());

                $db->autoUpdate('order', $order_data, '`order_sn`=\''.$order_sn.'\'');
                add_order_log($order_sn, $current_user['account'], 4, "使用余额/佣金/积分支付");
            }

            if($cart['balance_paid'] || $cart['reward_paid'] || $cart['integral_paid'])
            {
                add_memeber_exchange_log($current_user['account'], -1 * $cart['balance_paid'], -1 * $cart['reward_paid'], -1 * $cart['integral_paid'], 0, 0, $current_user['account'], "抵扣订单金额");
            }
            $flag = true;
            foreach($cart['products'] as $od)
            {
                if(!add_order_detail($od['product_sn'], $od['product_name'], $od['product_attributes'], $od['attributes'], $od['product_price'], $od['integral'], $od['integral_given'], $od['given_integral'], $od['reward'], $od['count'], $business_account, $order_sn, $od['is_virtual']))
                {
                    $flag = false;
                } else {
                    if($status == 4)
                    {
                        //扣减库存
                        consume_inventory($od['product_sn'], $od['attributes'], $od['count']);
                        if($od['is_virtual'])
                        {
                            //如果是虚拟产品，则生成预约券
                            $get_virtual_contents = 'select `content`,`count`,`total` from ' . $db->table('virtual_content') . ' where `product_sn`=\'' . $od['product_sn'] . '\'';

                            $virtual_contents = $db->fetchAll($get_virtual_contents);

                            $virtual_content = '';
                            if ($virtual_contents) {
                                $virtual_content = serialize($virtual_contents);
                            }

                            add_order_content($cart['business_account'], $current_user['account'], $address_info['mobile'], $order_sn, $od['product_sn'], $od['product_name'], $virtual_content, 2);
                        }
                    }
                }
            }

            if($flag) {
                //清理购物车
                $db->autoDelete('cart', '`business_account`=\''.$cart['business_account'].'\' and `account`=\''.$current_user['account'].'\' and `checked`=1');
                $db->commit();
                $response['count']++;
                $response['error'] = 0;
                $response['order_sn'] = $order_sn;
                $response['message'] = '下单成功';
                $current_user['order_sn'] = $order_sn;

                //订单结算
                if($status == 4)
                {
                    $log->record('计算三级分销');
                    //赠送积分
                    if($cart['total_given_integral'] > 0) {
                        add_memeber_exchange_log($user_info['account'], 0, 0, 0, $cart['total_given_integral'], 0, 'settle', $order_sn.'赠送积分');
                        add_member_reward($user_info['account'], 0, $cart['total_given_integral'], $order_sn);
                    }

                    //计算三级分销
                    $log->record('计算分销奖金:'.$cart['total_reward']);
                    distribution_settle($cart['total_reward'], $cart['total_integral_given'], $user_info['path'], $order_sn);

                    //计入商家收入
                    $business_income = $cart['total_product_amount'] + $cart['total_delivery_fee'] - $cart['total_reward'];
                    if(add_business_exchange($cart['business_account'], 0, $business_income, $current_user['account'], $order_sn.'支付成功'))
                    {
                        add_business_trade($cart['business_account'], $business_income, $order_sn);
                    } else {
                        //增加商家收入失败
                        $log->record($order_sn.'计入商家收入失败');
                    }
                    $log->record_array($cart);
                }
            } else {
                $db->rollback();
                $response['message'] = '提交订单信息失败，请稍后再试';
            }
        } else {
            $db->rollback();
            $response['message'] = '订单提交失败，请稍后再试';
        }
    }
}

//订单详情
if('detail' == $act) {
    $order_sn = trim(getGET('order_sn'));
    $id = intval(getGET('id'));

    if(empty($order_sn) || $id <= 0) {
        throw new RestFulException('参数错误', 550);
    }

    //检查订单归属
    $order = $db->find('order', ['order_sn'], ['order_sn' => $order_sn, 'account' => $current_user['account']]);

    if(empty($order)) {
        throw new RestFulException('订单不存在', 550);
    }

    $get_order_detail = 'select od.`product_sn`,od.`product_name`,od.`product_attributes`,od.`count`,p.`img` from '.$db->table('order_detail').' as od '.
                        ' left join '.$db->table('product').' as p using(`product_sn`) where od.`order_sn`=\''.$order_sn.'\' and od.`id`='.$id;

    $order_detail = $db->fetchRow($get_order_detail);

    if($order_detail) {
        $response['error'] = 0;
        $response['detail'] = [
            'name' => $order_detail['product_name'],
            'attributes' => $order_detail['product_attributes'],
            'img' => $order_detail['img'],
            'product_sn' => $order_detail['product_sn']
        ];
    } else {
        $response['message'] = '订单详情不存在';
    }
}

if('show' == $act)
{
    $status_str = array(
        1 => '待支付',
        2 => '支付中',
        3 => '支付完成',
        4 => '待发货',
        5 => '配货中',
        6 => '已发货',
        7 => '已收货',
        8 => '申请退单',
        9 => '退单中',
        10 => '已退单',
        11 => '无效订单',
        12 => '已完成',
    );

    $order_sn = getGET('sn');

    if($order_sn == '') {
        throw new RestFulException('参数错误', 500);
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select o.`remark`,o.`product_amount`,o.`integral_paid`,o.`balance_paid`,o.`reward_paid`,o.`add_time`,o.`delivery_fee`,o.`pay_time`,o.`order_sn`,b.`shop_name`,o.`status`,o.`amount`,o.`province`,o.`city`,o.`district`,o.`group`,o.`mobile`,o.`consignee`,o.`address`,o.`integral_amount` from '.$db->table('order').' as o join '.
        $db->table('business').' as b using(`business_account`) where o.`account`=\''.$current_user['account'].'\' and o.`order_sn`=\''.$order_sn.'\'';

    $order = $db->fetchRow($get_order);

    $get_order_detail = 'select od.`is_comment`,`od`.`integral`,od.`product_price` as price,od.`product_name` as name,od.`product_sn`,od.`id`,p.`id` as p_id,p.`img`,od.`count` from '.$db->table('order_detail').' as od '.
        ' left join '.$db->table('product').' as p using(`product_sn`) where od.`order_sn`=\''.$order_sn.'\'';

    $order['details'] = $db->fetchAll($get_order_detail);
    $order['status_str'] = $status_str[$order['status']];

    $get_province_name = 'select `province_name` from '.$db->table('province').' where `id`='.$order['province'];
    $get_city_name = 'select `city_name` from '.$db->table('city').' where `id`='.$order['city'];
    $get_district_name = 'select `district_name` from '.$db->table('district').' where `id`='.$order['district'];

    $province_name = $db->fetchOne($get_province_name);
    $city_name = $db->fetchOne($get_city_name);
    $district_name = $db->fetchOne($get_district_name);

    $order['address'] = $province_name.' '.$city_name.' '.$district_name.' '.$order['address'];

    $response['error'] = 0;
    $response['message'] = '获取订单详情成功';
    $response['order'] = $order;
}

if('view' == $act) {
    $status_str = array(
        1 => '待支付',
        2 => '支付中',
        3 => '支付完成',
        4 => '待发货',
        5 => '配货中',
        6 => '已发货',
        7 => '已收货',
        8 => '申请退单',
        9 => '退单中',
        10 => '已退单',
        11 => '无效订单',
        12 => '已完成'
    );

    $status = intval(getGET('status'));

    $page = intval(getGET('page'));
    $page = max($page, 1);

    $size = intval(getGET('size'));
    $size = max($size, 10);

    $limit = ($page - 1)*$size .','. $size;

    $get_order_list = 'select o.`order_sn`,b.`shop_name`,o.`status`,o.`amount`,o.`delivery_fee` from '.$db->table('order').' as o join '.
        $db->table('business').' as b using(`business_account`) where o.`account`=\''.$current_user['account'].'\'';

    if($status > 0 && $status < 8)
    {
        $get_order_list .= ' and o.`status`='.$status;
    }

    if($status > 0 && $status >= 8)
    {
        $get_order_list .= ' and o.`status`>='.$status;
    }

    $get_order_list .= ' order by o.`add_time` DESC limit '.$limit;

    $order_list = $db->fetchAll($get_order_list);

    if($order_list)
    {
        foreach ($order_list as &$ol)
        {
            $get_order_detail = 'select od.`product_name` as name,od.`product_sn`,p.`id` as p_id,od.`id`,p.`img`,od.`count`,od.`product_price` as price,od.`product_attributes` from ' . $db->table('order_detail') . ' as od ' .
                ' left join ' . $db->table('product') . ' as p using(`product_sn`) where od.`order_sn`=\'' . $ol['order_sn'] . '\'';
            $ol['details'] = $db->fetchAll($get_order_detail);

            $ol['status_str'] = $status_str[$ol['status']];
            if($ol['details']) {
                $detail_count = 0;

                foreach($ol['details'] as $order_detail) {
                    $detail_count += $order_detail['count'];
                }
                $ol['total_count'] = $detail_count;
            } else {
                $ol['order_detail'] = [];
                $ol['total_count'] = 0;
            }
        }
    }

    $response['error'] = 0;
    $response['order_list'] = empty($order_list) ? [] : $order_list;
}

echo json_encode($response);