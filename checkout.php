<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/11
 * Time: 上午9:59
 */
include 'library/init.inc.php';
global $config, $log, $db, $smarty;

$operation = 'checkout|caculate_fee|submit_order';

$opera = check_action($operation, getPOST('opera'));

if('submit_order' == $opera)
{
    $address_id = intval(getPOST('address_id'));
    $payment_id = intval(getPOST('payment_id'));
    $use_integral = getPOST('use_integral') == 'true' ? true : false;
    $use_balance = getPOST('use_balance') == 'true' ? true : false;
    $use_reward = getPOST('use_reward') == 'true' ? true : false;
    $delivery_list = getPOST('delivery_list');
    $comments = getPOST('comments');

    $response = array('error' => 1, 'msg' => '');

    if(!check_cross_domain())
    {
        $get_address_detail = 'select p.`province_name`,c.`city_name`,d.`district_name`,g.`group_name`,a.`address`,a.`consignee`,'.
            'a.`province`,a.`city`,a.`district`,a.`group`,'.
            'a.`mobile`,a.`zipcode`,a.`id` from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
            $db->table('city').' as c, '.$db->table('district').' as d, '.$db->table('group').' as g where '.
            'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` and a.`group`=g.`id` and a.`id`='.$address_id.
            ' and `account`=\''.$_SESSION['account'].'\'';

        $address_info = $db->fetchRow($get_address_detail);
        //获取待购买产品
        $get_cart_list = 'select p.`given_integral`,p.`reward`,p.`integral_given`,b.`business_account`,c.`checked`,p.`img`,p.`product_type_id`,c.`id`,c.`attributes`,c.`product_sn`,c.`price`,c.`integral`,c.`number`,b.`shop_name`,b.`id` as b_id,p.`name`,p.`weight`,p.`is_virtual` from ('.
            $db->table('cart').' as c join '.$db->table('product').' as p using(`product_sn`)) join '.$db->table('business').
            ' as b on (c.`business_account`=b.`business_account`) where c.`account`=\''.$_SESSION['account'].'\' and c.`checked`=1 order by c.`business_account`';

        $cart_list_tmp = $db->fetchAll($get_cart_list);

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
                    'integral_paid' => 0,
                    'balance_paid' => 0,
                    'reward_paid' => 0,
                    'remark' => $db->escape($comments[$cart['b_id']])
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
            $cart_list[$cart['b_id']]['total_reward'] += $cart['reward'] * $cart['number'];
        }

        //获取物流信息
        $delivery_id_str = '';
        foreach($delivery_list as $d)
        {
            foreach($d as $sd)
            {
                if(isset($sd['selected']) && $sd['selected'] == 1)
                {
                    $delivery_id_str .= $sd['delivery_id'].',';
                }
            }
        }
        $delivery_id_str = substr($delivery_id_str, 0, strlen($delivery_id_str)-1);
        $get_product_weight = 'select b.`id` as b_id, sum(p.`weight`*c.`number`) as `total_weight`,b.`business_account` from '.$db->table('cart').' as c'.
            ' join '.$db->table('product').' as p using(`product_sn`) join '.$db->table('business').' as b '.
            ' on c.`business_account`=b.`business_account` where c.`checked`=1 and c.`account`=\''.$_SESSION['account'].'\' '.
            ' and p.`free_delivery`=0 group by c.`business_account`';
        $product_weight = $db->fetchAll($get_product_weight);

        $get_delivery_sql = 'select DISTINCT da.`first_weight`,da.`next_weight`,da.`free`,da.`delivery_id`,d.`name` from '.$db->table('delivery_area_mapper').
            ' as dam join '.$db->table('delivery_area').' as da on dam.`area_id`=da.`id` join '.$db->table('delivery').
            ' as d on da.`delivery_id`=d.`id` where da.`id` in ('.$delivery_id_str.') ';

        if($product_weight)
        {
            foreach ($product_weight as $weight)
            {
                $get_delivery = $get_delivery_sql . ' and da.`business_account`=\'' . $weight['business_account'] . '\'';
                $log->record($get_delivery);
                $delivery = $db->fetchRow($get_delivery);
                $log->record_array($delivery);

                $tmp = array(
                    'delivery_id' => $delivery['delivery_id'],
                    'delivery_name' => $delivery['name'],
                    'delivery_fee' => caculate_delivery_fee($delivery['first_weight'], $delivery['next_weight'], $delivery['free'], $weight['total_weight'])
                );

                $log->record_array($tmp);
                //$cart_list[$weight['b_id']]['delivery_list'] = $delivery_list_mapper;
                $cart_list[$weight['b_id']]['total_delivery_fee'] = $tmp['delivery_fee'];
                $cart_list[$weight['b_id']]['total_amount'] += $tmp['delivery_fee'];
                $cart_list[$weight['b_id']]['delivery_id'] = $tmp['delivery_id'];
            }
        }
        //检查是否存在没有运费设置的商家
        foreach($cart_list as $key=>$cart)
        {
            if(empty($cart['delivery_id']))
            {
                $cart_list[$key]['delivery_id'] = $delivery_list[$key][0]['delivery_id'];
            }
        }

        //读取用户信息
        $get_user_info = 'select `integral`,`reward`,`balance`,`path`,`account`,`level_id`,`nickname`,`headimg`,`openid` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
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
                        $response['msg'] = '部分产品缺货';
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
                                  $_SESSION['account'], $cart['integral_paid'], $cart['reward_paid'], $cart['balance_paid'], $status, 0, $cart['remark']);

            if($order_sn)
            {
                if($status == 4)
                {
                    $order_data = array('pay_time'=>time());

                    $db->autoUpdate('order', $order_data, '`order_sn`=\''.$order_sn.'\'');
                    add_order_log($order_sn, $_SESSION['account'], 4, "使用余额/佣金/积分支付");
                }

                if($cart['balance_paid'] || $cart['reward_paid'] || $cart['integral_paid'])
                {
                    add_memeber_exchange_log($_SESSION['account'], -1 * $cart['balance_paid'], -1 * $cart['reward_paid'], -1 * $cart['integral_paid'], 0, 0, $_SESSION['account'], "抵扣订单金额");
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

                                add_order_content($cart['business_account'], $_SESSION['account'], $address_info['mobile'], $order_sn, $od['product_sn'], $od['product_name'], $virtual_content, 2);
                            }
                        }
                    }
                }

                if($flag) {
                    //清理购物车
                    $db->autoDelete('cart', '`business_account`=\''.$cart['business_account'].'\' and `account`=\''.$_SESSION['account'].'\'');
                    $db->commit();
                    $response['count']++;
                    $response['error'] = 0;
                    $response['order_sn'] = $order_sn;
                    $_SESSION['order_sn'] = $order_sn;

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
                        if(add_business_exchange($cart['business_account'], 0, $business_income, $_SESSION['account'], $order_sn.'支付成功'))
                        {
                            add_business_trade($cart['business_account'], $business_income, $order_sn);
                        } else {
                            //增加商家收入失败
                            $log->record($order_sn.'计入商家收入失败');
                        }
                        //如果会员购买了activity=1的产品且店铺已通过审核，则升级
                        $check_can_levelup = 'select am.`activity_id` from '.$db->table('activity_mapper').' as am left join '.
                                             $db->table('order_detail').' using (`product_sn`) where `order_sn`=\''.$order_sn.'\' and `activity_id`=1';

                        $log->record_array($cart);
                        if($db->fetchOne($check_can_levelup) && $cart['total_product_amount'] >= 10000 && $user_info['level_id'] <= 0)
                        {
                            $member_data = array(
                                'level_id' => 1
                            );

                            $db->autoUpdate('member', $member_data, '`account`=\''.$_SESSION['account'].'\'');

                            $member_shop = $db->fetchRow('select `id` from '.$db->table('member_shop').' where `account`=\''.$_SESSION['account'].'\'');

                            if(empty($member_shop)) {
                                /**
                                 * 创建会员店铺
                                 */
                                $member_shop_data = array(
                                    'account' => $_SESSION['account'],
                                    'name' => $user_info['nickname'].'的店铺',
                                    'logo' => $user_info['logo'],
                                    'add_time' => time()
                                );

                                $db->autoInsert('member_shop', array($member_shop_data));
                            }

                            if($user_info['openid'] != '') {
                                notify_member($user_info['openid'], '您的商业会员申请已通过审核');
                            }

                            $order_data = array(
                                'type' => 1,
                            );
                            $db->autoUpdate('order', $order_data, '`order_sn`=\''.$order_sn.'\'');
                        }
                    }
                } else {
                    $db->rollback();
                    $response['msg'] = '提交订单信息失败，请稍后再试';
                }
            } else {
                $db->rollback();
                $response['msg'] = '订单提交失败，请稍后再试';
            }
        }
    } else {
        $response['msg'] = '404:参数错误';
    }

    echo json_encode($response);
    exit;
}

if('caculate_fee' == $opera)
{
    $use_integral = getPOST('use_integral') == 'true' ? true : false;
    $use_balance = getPOST('use_balance') == 'true' ? true : false;
    $use_reward = getPOST('use_reward') == 'true' ? true : false;

    $response = array('error' => 1, 'msg' => '');

    if(!check_cross_domain())
    {
        //读取用户信息
        $get_user_info = 'select `integral`,`reward`,`balance` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
        $user_info = $db->fetchRow($get_user_info);

        $total_amount = $_SESSION['total_amount'];
        if($use_integral && $total_amount > 0)
        {
            if($total_amount >= $user_info['integral']/$config['integral_rate'])
            {
                $total_amount -= $user_info['integral']/$config['integral_rate'];
            } else {
                $total_amount = 0;
            }
        }

        if($use_reward && $total_amount > 0)
        {
            if($total_amount >= $user_info['reward']/$config['reward_rate'])
            {
                $total_amount -= $user_info['reward']/$config['reward_rate'];
            } else {
                $total_amount = 0;
            }
        }

        if($use_balance && $total_amount > 0)
        {
            if($total_amount >= $user_info['balance'])
            {
                $total_amount -= $user_info['balance'];
            } else {
                $total_amount = 0;
            }
        }

        $response['error'] = 0;
        $response['total_amount'] = $total_amount;
    } else {
        $response['msg'] = '404:参数错误';
    }

    echo json_encode($response);
    exit;
}

if('checkout' == $opera)
{
    $cart = getPOST('cart');

    $response = array('error'=>1, 'msg'=>'');

    if(!check_cross_domain()) {
        //过滤要购买的产品
        $buy_number = 0;
        $cart_data = array();
        foreach ($cart as $c)
        {
            if ($c['number'] > 0 && $c['checked'])
            {
                $buy_number += intval($c['number']);
            }

            $log->record_array($c);

            $cart_data[] = array(
                'id' => intval($c['c_id']),
                'number' => intval($c['number']),
                'checked' => $c['checked'] == 'true' ? 1 : 0
            );
        }

        if($buy_number == 0)
        {
            $response['msg'] = '请选择要购买的产品';
        } else {
            //更新购物车数据
            $db->begin();

            $commit = true;
            foreach($cart_data as $cd)
            {
                $data = array('number'=>$cd['number'], 'checked'=>$cd['checked']);

                if($db->autoUpdate('cart', $data, '`id`='.$cd['id'].' and `account`=\''.$_SESSION['account'].'\'') === false)
                {
                    $db->rollback();
                    $responsep['msg'] = '000:系统繁忙，请稍后再试';
                    $commit = false;
                    break;
                }
            }

            //检查积分
            if($commit)
            {
                $db->commit();
                $get_cart_list = 'select * from '.$db->table('cart').' where `account`=\''.$_SESSION['account'].'\' and `checked`=1';

                $cart_list = $db->fetchAll($get_cart_list);

                $total_integral = 0;
                foreach($cart_list as $c)
                {
                    $total_integral += $c['integral']*$c['number'];
                }

                $get_user_integral = 'select `integral` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
                $user_integral = $db->fetchOne($get_user_integral);
                if($total_integral > $user_integral)
                {
                    $response['msg'] = '您的积分不足，不能购买积分兑换产品';
                    $commit = false;
                }
            }

            if($commit)
            {
                //检查库存
                $get_inventory_list = 'select c.`id`,c.`number`,i.`inventory_logic` from '.$db->table('cart').' as c join '.
                                 $db->table('inventory').' as i on c.`product_sn`=i.`product_sn` and c.`attributes`=i.`attributes`'.
                                 ' where c.`account`=\''.$_SESSION['account'].'\' and c.`checked`=1';

                $inventory_list = $db->fetchAll($get_inventory_list);
                $success = true;
                foreach($inventory_list as $inventory)
                {
                    if($inventory['inventory_logic'] < $inventory['number'] || $inventory['number'] <= 0)
                    {
                        $success = false;
                        $data = array('number'=>$inventory['inventory_logic']);

                        $db->autoUpdate('cart', $data, '`id`='.$inventory['id']);
                    }
                }

                if($success)
                {
                    $response['error'] = 0;
                    $response['msg'] = '正在进入结算页面';
                    $response['refresh'] = false;
                } else {
                    $response['msg'] = '部分产品库存不足';
                    $response['refresh'] = true;
                }
            }
        }
    } else {
        $response['msg'] = '404:参数错误';
    }

    echo json_encode($response);
    exit;
}

//检查如果没有绑定手机号码的话，先绑定一个
/*
$check_user_mobile = 'select `mobile` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$mobile = $db->fetchOne($check_user_mobile);

if(!$mobile)
{
    redirect('bind_mobile.php');
}
*/

//检查如果没有默认地址的话，先设置一个
$check_address_id = 'select `id` from '.$db->table('address').' where `account`=\''.$_SESSION['account'].'\' and `is_default`=1';
$address_id = $db->fetchOne($check_address_id);

/*
if(!$address_id)
{
    redirect('address.php?act=add');
}
*/

//读取地址信息
if(!isset($_SESSION['address_id']) || $_SESSION['address_id'] <= 0)
{
    $_SESSION['address_id'] = $address_id;
} else {
    $address_id = $_SESSION['address_id'];
}

//$get_address_detail = 'select p.`province_name`,c.`city_name`,d.`district_name`,g.`group_name`,a.`address`,a.`consignee`,'.
//                      'a.`province`,a.`city`,a.`district`,a.`group`,'.
//                      'a.`mobile`,a.`zipcode`,a.`id` from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
//                      $db->table('city').' as c, '.$db->table('district').' as d, '.$db->table('group').' as g where '.
//                      'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` and a.`group`=g.`id` and a.`id`='.$address_id.
//                      ' and `account`=\''.$_SESSION['account'].'\'';
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
$_SESSION['total_amount'] = $total_amount;

//读取用户信息
$get_user_info = 'select `integral`,`reward`,`balance`,`level_id` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user_info = $db->fetchRow($get_user_info);

assign('user_info', $user_info);
assign('delivery_list_json', json_encode($delivery_list_json));
assign('total_amount', $total_amount);
assign('total_product_amount', $total_product_amount);
assign('total_delivery_fee', $total_delivery_fee);
assign('total_integral', $total_integral);
assign('total_number', $total_number);
assign('cart_list', $cart_list);

$smarty->display('checkout.phtml');