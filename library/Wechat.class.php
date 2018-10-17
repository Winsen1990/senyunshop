<?php
/**
 *
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 15-12-10
 * @version 
 */

class Wechat {

    private $mch_id = '1269390401';
    private $mch_key = 'CeciliaZhengWinsenPengwrhltx2015';

    private $mapper = array(
        array('pattern' => '/R.*/', 'func' => '_recharge'),
        array('pattern' => '/SJ.*/', 'func' => '_partner'),
    );

    public function run($open_id, $total_fee, $body, $detail, $out_trade_no) {

        $response = array('error' => 1, 'msg' => '');

        $_SESSION['payment'] = 'wechat';

        global $config;
        $res = create_prepay($config['appid'], $this->mch_id, $this->mch_key, $open_id, $total_fee, $body, $detail, $out_trade_no);

        $res = simplexml_load_string($res);

        if($res->prepay_id)
        {
            $response['error'] = 0;
        } else {
            $response['msg'] = $res->return_code.','.$res->return_msg;
        }

        $nonce_str = get_nonce_str();
        $response['nonce_str'] = $nonce_str;
        $time_stamp = time();

        //最后参与签名的参数有appId, timeStamp, nonceStr, package, signType。
        $sign = 'appId='.$config['appid'].'&nonceStr='.$nonce_str.'&package=prepay_id='.$res->prepay_id.'&signType=MD5&timeStamp='.$time_stamp.'&key='.$this->mch_key;
        $sign = md5($sign);
        $sign = strtoupper($sign);
        $response['timestamp'] = $time_stamp;
        $response['sign'] = $sign;
        $response['prepay_id'] = "".$res->prepay_id;

        return $response;

    }

    public function notify($xml) {
        $data = simplexml_load_string($xml);
        if($data) {
            if ($data->return_code == 'SUCCESS') {
                //支付成功
                $sn = $data->out_trade_no;
                global $db;
                $sn = $db->escape($sn);

                $data->sign = strtolower($data->sign);

                $flag = true;
                foreach ($this->mapper as $m) {
                    if (preg_match($m['pattern'], $sn)) {
                        $flag = false;
                        $this->$m['func']($sn, $data);
                        break;
                    }
                }
                if ($flag) {
                    $this->_pay_order($sn, $data);
                }
            } else {
                //支付失败
            }
        } else {
            //没有接收结果
        }
    }

    private function _pay_order($sn, $data) {
        global $db, $log;
        //产品订单
        $get_order_info = 'select `amount`,`account`,`business_account`,`product_amount`,`mobile`,`delivery_fee` from '.$db->table('order').' where `order_sn`=\''.$sn.'\'';

        $order = $db->fetchRow($get_order_info);

        add_order_log($sn, $order['account'], 3, "在线支付");

        if($order && $order['amount']*100 == $data->total_fee && $data->sign == tenpay_sign($data, $this->mch_key))
        {
            //验证订单金额正确
            //1. 设置订单为已付款
            $order_data = array(
                'status' => 3,
                'pay_time' => time()
            );

            $flag = $db->autoUpdate('order', $order_data, '`order_sn`=\''.$sn.'\' and `status`<>3');
            if($flag && $db->get_affect_rows())
            {
                $log->record($sn.'支付成功');
                //2. 订单结算
                $get_path = 'select `path` from '.$db->table('member').' where `account`=\''.$order['account'].'\'';
                $path = $db->fetchOne($get_path);
                distribution_settle($order['reward_amount'], $path, $sn);
                //3. 新增商家收入
                $business_income = $order['product_amount']+$order['delivery_fee'] - $order['reward_amount'];
                if(add_business_exchange($order['business_account'], 0, $business_income, $order['account'], '用户在线支付'))
                {
                    add_business_trade($order['business_account'], $business_income, $sn);
                } else {
                    //增加商家收入失败
                }

                $get_order_detail = 'select `product_sn`,`product_name`,`count`,`is_virtual`,`attributes` from '.$db->table('order_detail').' where `order_sn`=\''.$sn.'\'';
                $order_detail = $db->fetchAll($get_order_detail);
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
                    }
                }

                //如果会员购买了activity=4的产品，则升级
                $check_can_levelup = 'select am.`activity_id` from '.$db->table('activity_mapper').' as am left join '.
                    $db->table('order_detail').' using (`product_sn`) where `order_sn`=\''.$sn.'\' and `activity_id`=4';
                if($db->fetchOne($check_can_levelup))
                {
                    $member_data = array(
                        'level_id' => 1
                    );

                    $db->autoUpdate('member', $member_data, '`account`=\''.$order['account'].'\'');
                }
            }
        } else {
            //金额不正确
        }
    }

    private function _recharge($sn, $data) {
        global $db, $log;
        //充值订单
        $get_recharge_info = 'select `account`,`amount` from ' . $db->table('recharge') . ' where `recharge_sn`=\'' . $sn . '\'';
        $recharge = $db->fetchRow($get_recharge_info);

        if ($recharge && $recharge['amount'] * 100 == $data->total_fee && $data->sign == tenpay_sign($data, $this->mch_key)) {
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
    }

    private function _partner($sn, $data) {
        global $db, $config;
        $account = explode('-', $sn);
        $account = $account[0];
        $account = $db->escape($account);
        //支付成功
        if( $config['partner_fee'] * 100 == $data->total_fee  && $data->sign == tenpay_sign($data, $this->mch_key) ) {
            //是否已是合伙人
            $get_level_id = 'select `level_id` from '.$db->table('member').' where account = \''.$account.'\' limit 1';
            $level_id = $db->fetchOne($get_level_id);

            if( $level_id == 0 ) {
                $update_member = 'update '.$db->table('member').' set level_id = 1';
                $update_member .= ' where account = \''.$account.'\' limit 1';
                $db->update($update_member);
                //赠送积分给合伙人
                add_memeber_exchange_log($account, 0, 0, $config['level_integral'], 0, 0, $account, '成为合伙人赠送积分');

                $get_path = 'select `path` from '.$db->table('member').' where `account`=\''.$account.'\'';
                $path = $db->fetchOne($get_path);

                distribution_settle($config['partner_fee'] , $path, $account.'成为合伙人');
            }
        } else {
            //金额不正确或返回不正确
        }
    }

}
