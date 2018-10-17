<?php
/* *
 * 功能：支付宝服务器异步通知页面
 * 版本：3.3
 * 日期：2012-07-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究支付宝接口使用，只是提供一个参考。


 *************************页面功能说明*************************
 * 创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 该页面不能在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 该页面调试工具请使用写文本函数logResult，该函数已被默认关闭，见alipay_notify_class.php中的函数verifyNotify
 * 如果没有收到该页面返回的 success 信息，支付宝会在24小时内按一定的时间策略重发通知
 */
include 'library/init.inc.php';

global $log, $config, $db, $loader;

$verify_result = false;
if(count($_POST)) {
    //计算得出通知验证结果
    ksort($_POST);
    $param_str = '';
    foreach($_POST as $key => $value) {
        if($key == 'sign' || $key == 'sign_type') {
            continue;
        }

        if($param_str != '') {
            $param_str .= '&';
        }

        $param_str .= $key.'='.$value;
    }

    $loader->includeClass('Alipay');

    $alipay = new Alipay();
    $verify_sign = $alipay->verifySign($param_str, $_POST['sign'], $config['alipay_public_key']);
    $app_id = $config['alipay_app_id'];

    if($verify_sign == $_POST['sign'] && $app_id == $_POST['app_id']) {
        $verify_result = true;
    }
    $log->record_array($_POST);
}

if($verify_result) {//验证成功
    $log->record('验证成功');
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//请在这里加上商户的业务逻辑程序代

	
	//——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
	
    //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
	
	//商户订单号

	$out_trade_no = $_POST['out_trade_no'];

	//交易状态
	$trade_status = $_POST['trade_status'];

    $total_fee = $_POST['total_amount'];


    if($_POST['trade_status'] == 'TRADE_SUCCESS') {
		//判断该笔订单是否在商户网站中已经做过处理
        //支付成功
        $sn = $out_trade_no;
        $sn = $db->escape($sn);

        $pattern = '/R.*/';
        $copartner_pattern = '/SJ.*/';

        if(preg_match($pattern, $sn)) {
            //充值订单
            $get_recharge_info = 'select `account`,`amount` from ' . $db->table('recharge') . ' where `recharge_sn`=\'' . $sn . '\'';
            $recharge = $db->fetchRow($get_recharge_info);

            if ($recharge && $recharge['amount'] == $total_fee) {
                $log->record($sn . '支付成功');
                //验证充值金额正确
                $recharge_data = array(
                    'status' => 1
                );
                $flag = $db->autoUpdate('recharge', $recharge_data, '`recharge_sn`=\'' . $sn . '\'');
                if ($flag) {
                    add_memeber_exchange_log($recharge['account'], $recharge['amount'], 0, 0, 0, 0, $recharge['account'], $recharge['account'] . '在线充值');

                    add_recharge_log($sn, $recharge['account'], $recharge['account'], 0, 1, '在线充值');
                    $log->record('充值成功,成功更新充值记录');
                }
            } else {
                //充值金额不正确或返回不正确
            }
        } else {
            //支付成功
            $sn = $out_trade_no;
            $sn = $db->escape($sn);

            //产品订单
            $get_order_info = 'select * from '.$db->table('order').' where `order_sn`=\''.$sn.'\'';

            $order = $db->fetchRow($get_order_info);

            add_order_log($sn, $order['account'], 3, "在线支付");

            if($order && $order['amount'] == $_POST['total_amount'])
            {
                //验证订单金额正确
                //1. 设置订单为已付款
                $order_data = array(
                    'status' => 3,
                    'pay_time' => time(),
                    'payment_id' => 2
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

                    //如果会员购买了activity=1的产品且店铺已通过审核，则升级
                    $check_can_levelup = 'select am.`activity_id` from '.$db->table('activity_mapper').' as am left join '.
                        $db->table('order_detail').' using (`product_sn`) where `order_sn`=\''.$sn.'\' and `activity_id`=1';

                    $user_info = $db->fetchRow('select `level_id`,`nickname`,`headimg`,`openid` from '.$db->table('member').' where `account`=\''.$order['account'].'\'');

                    if($db->fetchOne($check_can_levelup) && $user_info && $user_info['level_id'] <= 0)
                    {
                        $member_data = array(
                            'level_id' => 1
                        );

                        $db->autoUpdate('member', $member_data, '`account`=\''.$order['account'].'\'');

                        $member_shop = $db->fetchRow('select `id` from '.$db->table('member_shop').' where `account`=\''.$order['account'].'\'');

                        if(empty($member_shop)) {
                            /**
                             * 创建会员店铺
                             */
                            $member_shop_data = array(
                                'account' => $order['account'],
                                'name' => $user_info['nickname'].'的店铺',
                                'logo' => $user_info['headimg'],
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
                        $db->autoUpdate('order', $order_data, '`order_sn`=\''.$sn.'\'');
                    }
                }
            } else {
                //金额不正确
                $log->record($out_trade_no.'支付金额不正确');
                $log->record_array($_POST);
            }
        }
				
		//注意：
		//退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

        //调试用，写文本函数记录程序运行情况是否正常
        //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
    }
    else if ($_POST['trade_status'] == 'TRADE_FINISHED') {
    }

	echo "success";
}
else {
    echo "fail";
}