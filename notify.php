<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/20
 * Time: 下午12:04
 */
include 'library/init.inc.php';

$mch_key = $config['mch_key'];
//仅对微信支付的异步通知
//$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
$xml = file_get_contents('php://input');
$log->record($xml);
$data = simplexml_load_string($xml);
$success_response =<<<XML
<xml>
  <return_code><![CDATA[SUCCESS]]></return_code>
  <return_msg><![CDATA[OK]]></return_msg>
</xml>
XML;
$log->record('request notify.php');
if($data)
{
    if($data->return_code == 'SUCCESS')
    {
        //支付成功
        $sn = $data->out_trade_no;
        $sn = $db->escape($sn);

        $pattern = '/R.*/';
        $copartner_pattern = '/SJ.*/';
        $data->sign = strtolower($data->sign);

        if(preg_match($pattern, $sn)) {
            //充值订单
            $get_recharge_info = 'select `account`,`amount` from ' . $db->table('recharge') . ' where `recharge_sn`=\'' . $sn . '\'';
            $recharge = $db->fetchRow($get_recharge_info);

            if ($recharge && $recharge['amount'] * 100 == $data->total_fee && $data->sign == tenpay_sign($data, $mch_key)) {
                $log->record($sn . '支付成功');
                //验证充值金额正确
                $recharge_data = array(
                    'status' => 1
                );
                $flag = $db->autoUpdate('recharge', $recharge_data, '`recharge_sn`=\'' . $sn . '\'');
                if ($flag && $db->get_affect_rows()) {
                    add_memeber_exchange_log($recharge['account'], $recharge['amount'], 0, 0, 0, 0, $recharge['account'], $recharge['account'] . '在线充值');

                    add_recharge_log($sn, $recharge['account'], $recharge['account'], 0, 1, '在线充值');
                    $log->record('充值成功,成功更新充值记录');
                }
            } else {
                //充值金额不正确或返回不正确
            }

        } else {
            //支付成功
            $sn = $data->out_trade_no;
            $sn = $db->escape($sn);

            //产品订单
            $get_order_info = 'select * from '.$db->table('order').' where `order_sn`=\''.$sn.'\'';

            $order = $db->fetchRow($get_order_info);

            add_order_log($sn, $order['account'], 3, "在线支付");

            if($order && $order['amount'] == $data->total_fee/100 && strtolower($data->sign) == tenpay_sign($data, $mch_key))
            {
                //验证订单金额正确
                //1. 设置订单为已付款
                $order_data = array(
                    'status' => 3,
                    'pay_time' => time(),
                    'payment_id' => 1
                );

                $flag = $db->autoUpdate('order', $order_data, '`order_sn`=\''.$sn.'\' and `status`<3');
                if($flag && $db->get_affect_rows())
                {
                    $log->record($sn.'支付成功');
                    //2. 订单结算
                    $get_path = 'select `path` from '.$db->table('member').' where `account`=\''.$order['account'].'\'';
                    $path = $db->fetchOne($get_path);
                    distribution_settle($order['reward_amount'], $order['integral_given_amount'], $path, $sn);

                    //赠送积分
                    if($order['given_integral_amount'] > 0) {
                        add_memeber_exchange_log($order['account'], 0, 0, 0, $order['given_integral_amount'], 0, 'settle', $sn.'赠送积分');
                        add_member_reward($order['account'], 0, $order['given_integral_amount'], $order_sn);
                    }
                    //3. 新增商家收入
                    $business_income = $order['product_amount'] + $order['delivery_fee'] - $order['reward_amount'];
                    if(add_business_exchange($order['business_account'], 0, $business_income, $order['account'], '用户在线支付'))
                    {
                        add_business_trade($order['business_account'], $business_income, $sn);
                    } else {
                        //增加商家收入失败
                    }

                    $get_order_detail = 'select `product_sn`,`product_name`,`count`,`is_virtual`,`attributes` from '.$db->table('order_detail').' where `order_sn`=\''.$sn.'\'';
                    $order_detail = $db->fetchAll($get_order_detail);

                    //状态变为已发货
                    $delivery = false;
                    foreach($order_detail as $od)
                    {
                        //扣减库存
                        consume_inventory($od['product_sn'], $od['attributes'], $od['count']);
                        //如果是虚拟产品，则生成预约券
                        if($od['is_virtual'])
                        {
                            $get_virtual_contents = 'select `content`,`count`,`total` from ' . $db->table('virtual_content') . ' where `product_sn`=\'' . $od['product_sn'] . '\'';

                            $virtual_contents = $db->fetchAll($get_virtual_contents);

                            $virtual_content = '';
                            if ($virtual_contents)
                            {
                                $virtual_content = serialize($virtual_contents);
                            }

                            add_order_content($order['business_account'], $order['account'], $order['mobile'], $sn, $od['product_sn'], $od['product_name'], $virtual_content, 2);
                        } else{
                            $delivery = true;
                        }
                    }

                    if( $delivery ) {
                        $order_data = array(
                            'status' => 4,
                        );
                        $db->autoUpdate('order', $order_data, '`order_sn`=\''.$sn.'\' and `status`<>4');
                    }

                    //更新用户升级经验
                    $db->upgrade('member', ['experience' => ['exp', '`experience`+'.$order['amount']]], ['account' => $order['account']]);

                    $levels = $db->all('level', ['id', 'experience', 'experience_type', 'expired', 'birthday_integral_rate',
                        'birthday_given_integral', 'is_special'], null, [['experience', 'DESC']]);


                    $user_info = $db->find('member', ['account', 'openid', 'level_id', 'nickname', 'level_id',
                        'experience', 'level_expired', 'birthday'], ['account' => $order['account']]);

                    $member_level = null;
                    if($levels) {
                        foreach($levels as $i => $_level) {
                            if($_level['id'] == $user_info['level_id']) {
                                $member_level = $_level;
                            }

                            if($_level['is_special'] == 1) {
                                unset($levels[$i]);
                            }
                        }
                    }

                    //消费多少钱就获得多少积分
                    $given_integral_amount = $order['amount'];
                    $integral_remark = $sn.'赠送积分';
                    //有生日则需要计算赠送积分倍数
                    if(!empty($user_info['birthday'])) {
                        $user_info['birthday'] = new DateTime($user_info['birthday']);
                        $user_info['birthday'] = $user_info['birthday']->format('m-d');
                        if($user_info['birthday'] == date('m-d') && $member_level) {
                            $given_integral_amount *= $member_level['birthday_integral_rate'];
                            $integral_remark = $sn.'生日当天'.$member_level['birthday_integral_rate'].'倍赠送积分';
                        }
                    }

                    add_memeber_exchange_log($order['account'], 0, 0, 0, $given_integral_amount, 0, 'settle', $integral_remark);
                    add_member_reward($order['account'], 0, $given_integral_amount, $order['order_sn']);

                    //非特殊等级考虑升级
                    if($levels && (empty($member_level) || $member_level['is_special'] == 0))
                    {
                        $current_level = $user_info['level_id'];
                        $member_data = [
                            'level_id' => $current_level
                        ];
                        $prev_experience = empty($member_level) ? 0 : $member_level['experience'];
                        $level_up = false;
                        while($level = array_shift($levels)) {
                            switch($level['experience_type']) {
                                case 1:
                                    //单次消费
                                    if($order['amount'] >= $level['experience'] && $current_level < $level['id']) {
                                        $level_up = true;
                                        $member_data['level_id'] = $level['id'];
                                        $member_data['level_expired'] = $level['expired'];
                                        $member_data['experience'] = abs($level['experience']);
                                        break;
                                    }
                                    break;

                                case 2:
                                    //累计消费
                                    $total_experience = $user_info['experience'] + $prev_experience;
                                    if($total_experience >= $level['experience'] && $current_level < $level['id']) {
                                        $level_up = true;
                                        $member_data['level_id'] = $level['id'];
                                        $member_data['level_expired'] = $level['expired'];
                                        $member_data['experience'] = abs($level['experience'] - $prev_experience);
                                        break;
                                    }
                                    break;
                            }
                        }

                        if($level_up) {
                            if($member_data['level_expired'] != -1) {
                                $member_data['level_expired'] = date('Y-m-d H:i:s', time() + $member_data['level_expired']);
                            } else {
                                $member_data['level_expired'] = '9999-12-31 23:59:59';
                            }
                            $member_data['experience'] = ['exp', '`experience`-'.$member_data['experience']];
                            $db->upgrade('member', $member_data, ['account' => $order['account']]);
                            $log->record($order['account'].'升级:'.$current_level.'=>'.$member_data['level_id']);
                        }
                    }
                }
            } else {
                //金额不正确
                $log->record($data->out_trade_no.'支付金额不正确');
                $log->record($data->asXML());
            }
        }
    } else {
        //支付失败
        $log->record($data->out_trade_no.'支付失败');
        $log->record_array($data);
    }
} else {
    //没有接收结果
    $log->record('没有接收到信息');
}

echo $success_response;
