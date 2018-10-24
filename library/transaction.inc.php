<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/7
 * Time: 上午8:27
 */

/**
 * @param $order_sn
 * @param $product_sn
 * @param $product_name
 * @param $content
 * @param int $keep_alive
 * @return mixed
 */
function add_order_content($business_account, $account, $mobile, $order_sn, $product_sn, $product_name, $content, $keep_alive = 2)
{
    global $db;

    $content_data = array(
        'business_account' => $business_account,
        'account' => $account,
        'mobile' => $mobile,
        'order_sn' => $order_sn,
        'product_sn' => $product_sn,
        'product_name' => $product_name,
        'content' => $content,
        'begin_time' => time(),
        'end_time' => (time()+3600*24*$keep_alive)
    );

    $code = '';
    do
    {
        $code = date('Y md ').rand(1000, 9999).' '.rand(1000, 9999);

        $check_code = 'select `id` from '.$db->table('order_content').' where `code`=\''.$code.'\'';
    } while($db->fetchOne($check_code));

    $content_data['code'] = $code;

    if($db->autoInsert('order_content', array($content_data)))
    {
        return $code;
    } else {
        return false;
    }
}
/**
 * 新增商家交易记录
 */
function add_business_exchange($business_account, $balance, $trade, $operator, $remark = '')
{
    global $db;

    $update_business = 'update '.$db->table('business').' set `balance`=`balance`+'.$balance.',`trade`=`trade`+'.$trade.
                       ' where `business_account`=\''.$business_account.'\'';

    if($db->update($update_business))
    {
        $log_data = array(
            'business_account' => $business_account,
            'balance' => $balance,
            'trade' => $trade,
            'operator' => $operator,
            'remark' => $remark,
            'add_time' => time()
        );

        $db->autoInsert('business_exchange_log', array($log_data));

        return true;
    } else {
        return false;
    }
}

/**
 * 新增商家担保交易
 */
function add_business_trade($business_account, $trade, $remark, $status = 0)
{
    global $db;

    $trade_data = array(
        'business_account' => $business_account,
        'trade' => $trade,
        'remark' => $remark,
        'status' => $status,
        'settle_time' => time()
    );

    return $db->autoInsert('trade', array($trade_data));
}

/**
 * 新增充值记录
 */
function add_recharge($account, $amount, $type = 0, $bank = '', $card_num = '', $remark = '')
{
    global $db;

    $recharge_data = array(
        'account' => $account,
        'amount' => $amount,
        'status' => 0,
        'add_time' => time(),
        'type' => $type,
        'bank' => $bank,
        'card_num' => $card_num
    );

    $recharge_sn = '';
    do
    {
        $recharge_sn = 'R'.time().rand(100, 999);

        $check_recharge_sn = 'select `recharge_sn` from '.$db->table('recharge').' where `recharge_sn`=\''.$recharge_sn.'\'';
    } while($db->fetchOne($check_recharge_sn));

    $recharge_data['recharge_sn'] = $recharge_sn;

    if($db->autoInsert('recharge', array($recharge_data)))
    {
        add_recharge_log($recharge_sn, $account, $_SESSION['account'], $type, 0, $remark);
        return $recharge_sn;
    } else {
        return false;
    }
}

/**
 * 充值日志记录
 */
function add_recharge_log($recharge_sn, $account, $operator, $type, $status, $remark = '')
{
    global $db;

    $log_data = array(
        'recharge_sn' => $recharge_sn,
        'account' => $account,
        'add_time' => time(),
        'operator' => $operator,
        'type' => $type,
        'status' => $status,
        'remark' => $remark
    );

    return $db->autoInsert('recharge_log', array($log_data));
}
/**
 * 检查产品库存
 * @param string $product_sn
 * @param string $attributes
 * @param int $number
 * @return bool
 */
function check_inventory($product_sn, $attributes, $number)
{
    global $db;
    $check_inventory = 'select `inventory_logic` from '.$db->table('inventory').' where `product_sn`=\''.$product_sn.'\' '.
                       ' and `attributes`=\''.$attributes.'\' and `inventory_logic`>='.$number;

    return $check_inventory;
}

/**
 * 扣减产品库存
 * @param string $product_sn
 * @param string $attributes
 * @param int $number
 * @param int $mode
 * @return bool|resource
 */
function consume_inventory($product_sn, $attributes, $number, $mode = 0)
{
    global $db;
    global $log;

    $sql = 'update '.$db->table('inventory').' set ';
    switch($mode)
    {
        case 1:
            //扣减物理库存
            $sql .= ',`inventory`=`inventory`-'.$number.', `inventory_await`=`inventory_await`+'.$number;
            break;
        default:
            //扣减逻辑库存
            $sql .= ' `inventory_logic`=`inventory_logic`-'.$number.', `inventory_await`=`inventory_await`+'.$number;
            break;
    }

    $sql .= ' where `product_sn`=\''.$product_sn.'\' and `attributes`=\''.$attributes.'\'';

    $log->record($sql);
    if($db->update($sql) !== false) {
        if($mode == 1) {
            //更新产品销售数量
            $update_sale_count = 'update '.$db->table('product').' set `sale_count`=`sale_count`+'.$number.
                                ' where `product_sn`=\''.$product_sn.'\'';

            $db->update($update_sale_count);
        }
        return true;
    } else {
        return false;
    }
}

/**
 * 修改产品库存
 * @param string $product_sn
 * @param string $attributes
 * @param int $number
 * @return bool|resource
 */
function modify_inventory($product_sn, $attributes, $number)
{
    global $db;
    global $log;

    //检查库存，如果待发库存高于写入库存，则不进行操作
    $check_inventory = 'select `id` from '.$db->table('inventory').
                       ' where `product_sn`=\''.$product_sn.'\' and `attributes`=\''.$attributes.'\' and '.
                       ' `inventory_await`<='.$number;

    $inventory_id = $db->fetchOne($check_inventory);
    $log->record($check_inventory);

    if($inventory_id)
    {
        $update_inventory = 'update '.$db->table('inventory').' set `inventory`='.$number.',`inventory_logic`='.$number.'-`inventory_await` '.
                            ' where `id`='.$inventory_id;

        $log->record($update_inventory);
        return $db->update($update_inventory);
    } else {
        return false;
    }
}
/**
 * 三级分销结算
 * @param float $reward
 * @param string $path
 * @return bool
 */
function distribution_settle($reward, $integral, $path, $order_sn = '')
{
    global $db;
    global $config;
    global $log;

    $log->record('结算开始');
    $ids_arr = explode(',', $path);
    array_pop($ids_arr);
    array_pop($ids_arr);

    /*
     * $level = array(
     *      0 => '普通会员',
     *      1 => '商业会员',
     *      2 => '合伙会员'
     * );
     */
    $ids_arr_str = implode(',', $ids_arr);
    //获取推荐人
    $get_senior_member = 'select `id`,`account`,`level_id`,`openid` from '.$db->table('member').' where `id` in ('.$ids_arr_str.') order by `path` DESC';
    $senior_member = $db->fetchAll($get_senior_member);

    if($senior_member)
    {
        $layer = 1;
        $ab_reward_cnt = 3;
        $member_reward_cnt = 2;
        foreach($senior_member as $member)
        {
            if($member['level_id'] >= 1)
            {
                if($ab_reward_cnt <= 0) {
                    break;
                }

                $member_reward_cnt = 0;
                $_layer = min($layer, 3);

                $reward_tmp = $reward * $config['level_'.$_layer];

                if ($reward_tmp) {
                    add_memeber_exchange_log($member['account'], 0, 0, 0, 0, $reward_tmp, 'settle', $order_sn . '推荐奖');
                    add_member_reward($member['account'], $reward_tmp, 0, $order_sn);
                    reward_exchange_notify($member['openid'], '', $reward_tmp);
                    $ab_reward_cnt--;
                }
            } else {
                if($member_reward_cnt > 0) {
                    $integral_reward = $integral * $config['member_level_'.$layer];
                    if($integral_reward) {
                        add_memeber_exchange_log($member['account'], 0, 0, 0, $integral_reward, 0, 'settle', $order_sn . '推广奖');
                        add_member_reward($member['account'], 0, $integral_reward, $order_sn);
                    }

                    $member_reward_cnt--;
                }
            }

            $layer++;
        }
    }
    $log->record('结算结束');

    return true;
}

/**
 * 新增订单操作记录
 */
function add_order_log($order_sn, $operator, $status, $remark = '')
{
    global $db;

    $log_data = array(
        'order_sn' => $order_sn,
        'operator' => $operator,
        'status' => $status,
        'remark' => $remark,
        'add_time' => time()
    );

    return $db->autoInsert('order_log', array($log_data));
}

 /**
 * 新增订单详情
 * @param string $product_sn
 * @param string $product_name
 * @param string $product_attributes
 * @param string $attributes
 * @param float $product_price
 * @param float $integral
 * @param float $integral_given
 * @param float $reward
 * @param int $count
 * @param string $business_account
 * @param string $order_sn
 * @return bool
 */
 function add_order_detail($product_sn, $product_name, $product_attributes, $attributes, $product_price, $integral,
                           $integral_given, $given_integral, $reward, $count, $business_account, $order_sn, $is_virtual = 0)
 {
    global $db;

    $order_detail_data = array(
        'product_sn' => $product_sn,
        'product_name' => $product_name,
        'product_attributes' => $product_attributes,
        'attributes' => $attributes,
        'product_price' => $product_price,
        'integral' => $integral,
        'integral_given' => $integral_given,
        'given_integral' => $given_integral,
        'reward' => $reward,
        'count' => $count,
        'business_account' => $business_account,
        'order_sn' => $order_sn,
        'is_virtual' => $is_virtual
    );

    if($db->autoInsert('order_detail', array($order_detail_data)))
    {
        return true;
    } else {
        return false;
    }
 }

 /**
 * 新增订单
 * @param float $integral_amount
 * @param float $product_amount
 * @param float $delivery_fee
 * @param int $delivery_id
 * @param string $business_account
 * @param float $integral_given_amount
 * @param int $payment_id
 * @param int $address_id
 * @param float $reward_amount
 * @param string $account
 * @param float $integral_paid
 * @param float $reward_paid
 * @param float $balance_paid
 * @param int $status
 * @param int $self_delivery
 * @param string $remark
 * @return mixed
 */
 function add_order($integral_amount, $product_amount, $delivery_fee, $delivery_id, $business_account, $integral_given_amount, $given_integral_amount,
                    $payment_id, $address_id, $reward_amount, $account, $integral_paid = 0.0, $reward_paid = 0.0, $balance_paid = 0.0,
                    $status = 1, $self_delivery = 0, $remark = '', $is_virtual = 0, $message_notice = 0)
 {
    global $db;
    global $config;

    $amount = $product_amount + $delivery_fee - $balance_paid - $reward_paid/$config['reward_rate'] - $integral_paid/$config['integral_rate'];

    if($integral_amount > 0)
    {
        $amount += $integral_amount/$config['integral_rate'];
    }

    $order_data = array(
        'integral_amount' => $integral_amount,
        'product_amount' => $product_amount,
        'delivery_fee' => $delivery_fee,
        'integral_paid' => $integral_paid,
        'reward_paid' => $reward_paid,
        'balance_paid' => $balance_paid,
        'amount' => $amount,
        'business_account' => $business_account,
        'account' => $account,
        'integral_given_amount' => $integral_given_amount,
        'given_integral_amount' => $given_integral_amount,
        'reward_amount' => $reward_amount,
        'self_delivery' => $self_delivery,
        'status' => $status,
        'add_time' => time(),
        'remark' => $remark,
        'is_virtual' => $is_virtual,
        'message_notice' => $message_notice
    );

    //获取地址信息
    $get_address = 'select `province`,`city`,`district`,`group`,`address`,`consignee`,`mobile`,`zipcode` from '.
                   $db->table('address').' where `id`='.$address_id.' and `account`=\''.$account.'\'';
    if($address = $db->fetchRow($get_address))
    {
        $order_data['address_id'] = $address_id;
        $order_data['province'] = $address['province'];
        $order_data['city'] = $address['city'];
        $order_data['district'] = $address['district'];
        $order_data['group'] = $address['group'];
        $order_data['address'] = $address['address'];
        $order_data['consignee'] = $address['consignee'];
        $order_data['mobile'] = $address['mobile'];
        $order_data['zipcode'] = $address['zipcode'];
    } else {
        return false;
    }

    //获取物流信息
    $get_delivery = 'select `name` from '.$db->table('delivery').' where `id`='.$delivery_id.' and `business_account`=\''.$business_account.'\'';
    if($delivery = $db->fetchRow($get_delivery))
    {
        $order_data['delivery_id'] = $delivery_id;
        $order_data['delivery_name'] = $delivery['name'];
    } else {
        return false;
    }

    //获取支付方式
    $get_payment = 'select `name` from '.$db->table('payment').' where `id`='.$payment_id;
    if($payment = $db->fetchRow($get_payment))
    {
        $order_data['payment_name'] = $payment['name'];
        $order_data['payment_id'] = $payment_id;
    }  else {
        return false;
    }

    $db->begin();

    $order_sn = '';

    do
    {
        $order_sn = time().rand(1000, 9999);
        $check_order_sn = 'select `id` from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';
    } while($db->fetchOne($check_order_sn));

    $order_data['order_sn'] = $order_sn;

    if($db->autoInsert('order', array($order_data)))
    {
        $db->commit();
        return $order_sn;
    }

    $db->rollback();
    return false;
 }

 /**
 * 计算运费
 * @param float $first_weight
 * @param float $next_weight
 * @param float $free
 * @param float $total_weight
 * @return float
 */
 function caculate_delivery_fee($first_weight, $next_weight, $free, $total_weight)
 {
    $delivery_fee = 0;

    if($total_weight >= 1000)
    {
        $delivery_fee += $first_weight;
        $total_weight -= 1000;
    } else {
        $delivery_fee += $first_weight;
        $total_weight = 0;
    }

    $weight_count = intval($total_weight/1000);

    if($total_weight%1000)
    {
        $weight_count++;
    }

    $delivery_fee += $weight_count*$next_weight;

    if($delivery_fee > $free) {
        return $delivery_fee - $free;
    } else {
        return 0;
    }
 }