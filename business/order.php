<?php
/**
 * 订单管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-24
 * @version 1.0.0
 */

include 'library/init.inc.php';

global $log, $db, $config, $smarty, $loader;

//商户管理后台初始化
business_base_init();
$template = 'order/';

$action = 'view|deliver|prepare|agree|refund|delete|detail|export|pay|preview|disagree';
$operation = 'deliver|express_info';

$act = check_action($action, getGET('act'));
$opera = check_action($operation, getPOST('opera'));
$act = ( $act == '' ) ? 'view' : $act;
//===============================================================================
if('express_info' == $opera)
{
    $order_sn = getPOST('order_sn');

    $response = array('error'=>1, 'msg'=>'');

    if($order_sn == '')
    {
        $response['msg'] = '参数错误';
    } else {
        $order_sn = $db->escape($order_sn);
    }

    $get_order_info = 'select `express_id`,`status`,`express_sn` from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';
    $order = $db->fetchRow($get_order_info);

    if($order && $order['status'] == 6)
    {
        $get_express_code = 'select `code` from '.$db->table('express').' where `id`='.$order['express_id'];
        $express_info = query_express($db->fetchOne($get_express_code   ), $order['express_sn']);
        $express_info = json_decode($express_info, true);
        assign('order_info', $express_info);
        $response['error'] = 0;
        $response['msg'] = $smarty->fetch('public/express_info.phtml');
    } else {
        $response['msg'] = '当前没有任何信息';
    }

    echo json_encode($response);
    exit;
}

if( 'deliver' == $opera ) {
    if( !check_purview('pur_order_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $express_sn = trim(getPOST('express_sn'));
    $express_id = intval(getPOST('express_id'));
    $order_sn = trim(getPOST('sn'));

    if($express_id <= 0)
    {
        show_system_message('请选择快递公司', array());
        exit;
    }

    if( '' == $express_sn ) {
        show_system_message('快递单号不能为空', array());
        exit;
    }
    $express_sn = $db->escape($express_sn);

    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select * from '.$db->table('order');
    $get_order .= ' where 1';
//    $get_order .= ' and is_virtual = 0';    //实体订单
    $get_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }

    if( $order['status'] != 5 && $order['status'] != 4 ) {
        show_system_message('参数错误', array());
        exit;
    }

    $update_order = 'update '.$db->table('order').' set';
    $update_order .= ' `status` = 6';
    $update_order .= ', `express_id`='.$express_id;
    $update_order .= ', `express_sn` = \''.$express_sn.'\'';
    $update_order .= ', `delivery_time` = '.time();
    $update_order .= ' where order_sn = \''.$order_sn.'\' limit 1';
    if( $db->update($update_order) ) {

        $log_data = array(
            'order_sn' => $order_sn,
            'operator' => $_SESSION['business_admin'],
            'status' => 6,
            'add_time' => time(),
            'remark' => '发货'
        );
        $db->autoInsert('order_log', array($log_data));
        //扣减库存
        $get_order_detail = 'select `product_sn`,`count`,`attributes` from '.$db->table('order_detail').' where `order_sn`=\''.$order_sn.'\'';
        $order_detail = $db->fetchAll($get_order_detail);
        foreach($order_detail as $od)
        {
            consume_inventory($od['product_sn'], $od['attributes'], $od['count'], 1);
        }

        $links = array(
            array('alt' => '已发货订单列表', 'link' => 'order.php?status=6'),
        );
        show_system_message('已发货', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}


//===============================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_order_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $status_str = array(
        1 => '待支付',
        2 => '支付中',
        3 => '已支付',
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

    $status = intval(getGET('status'));
    if( 0 >= $status || 12 < $status || 2 == $status ) {
        assign('status', 0);
        assign('order_status', '');
        $and_where = '';
    } else {
        assign('status', $status);
        assign('order_status', $status_str[$status]);
        $and_where = ' and status = '.$status;
    }

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);
    $order_sn = trim(getGET('order_sn'));
    $consignee = trim(getGET('consignee'));
    $mobile = trim(getGET('mobile'));

    if(!empty($mobile)) {
        $and_where .= ' and `mobile`=\''.intval($mobile).'\'';
    }

    if(!empty($consignee)) {
        $and_where .= ' and `consignee`=\''.$db->escape($consignee).'\'';
    }

    if(!empty($order_sn)) {
        $and_where .= ' and `order_sn`=\''.$db->escape($order_sn).'\'';
    }

    $pattern = '#[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}#';
    if( $st ) {
        if( preg_match($pattern, $st) ) {
            $and_where .= ' and add_time > ' . $start_time;
        } else {
            $st = '';
        }
    }
    if( $et ) {
        if( preg_match($pattern, $et) ) {
            $and_where .= ' and add_time < ' . ($end_time + 3600 * 24);
        } else {
            $st = '';
        }
    }

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from '.$db->table('order');
    $get_total .= ' where 1';
    $get_total .= $and_where;
//    $get_total .= ' and is_virtual = 0';    //实体产品
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    //判断是否有实体产品
    $check_contain_real = ' (select count(detail.id) from '.$db->table('order_detail').' as detail where detail.order_sn = a.order_sn and is_virtual = 0)';

    $get_order_list = 'select a.*, p.province_name, city.city_name, d.district_name, g.group_name,';
    $get_order_list .= $check_contain_real.' as `real`';
    $get_order_list .= ' from '.$db->table('order').' as a';
    $get_order_list .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_order_list .= ' left join '.$db->table('city').' as city on a.city = city.id';
    $get_order_list .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_order_list .= ' left join '.$db->table('group').' as g on a.group = g.id';

    $get_order_list .= ' where 1';
    $get_order_list .= $and_where;
//    $get_order_list .= ' and a.is_virtual = 0';
    $get_order_list .= ' order by add_time desc';
    $get_order_list .= ' limit '.$offset.','.$count;

    $order_list = $db->fetchAll($get_order_list);
//    echo $get_order_list;exit;

    if( $order_list ) {
        foreach ($order_list as $key => $order) {
            $order_list[$key]['add_time_str'] = $order['add_time'] ? date('Y-m-d H:i:s', $order['add_time']) : '';
            $order_list[$key]['delivery_time_str'] = $order['delivery_time'] ? date('Y-m-d H:i:s', $order['delivery_time']) : '未发货';
            $order_list[$key]['receive_time_str'] = $order['receive_time'] ? date('Y-m-d H:i:s', $order['receive_time']) : '未收货';
            $order_list[$key]['pay_time_str'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '未支付';
            $order_list[$key]['status_str'] = $status_str[$order['status']];
        }
    }

    assign('order_list', $order_list);
    create_pager($page, $total_page, $total);

    assign('count', $count);
    assign('st', $st);
    assign('et', $et);
    assign('order_sn', $order_sn);
    assign('consignee', $consignee);
    assign('mobile', $mobile);
}

//配货
if( 'prepare' == $act ) {
    if( !check_purview('pur_order_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $order_sn = trim(getGET('sn'));
    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select * from '.$db->table('order');
    $get_order .= ' where 1';
//    $get_order .= ' and is_virtual = 0';    //实体订单
    $get_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }

    if( $order['status'] != 4 ) {
        show_system_message('参数错误', array());
        exit;
    }

    $update_order = 'update '.$db->table('order').' set';
    $update_order .= ' `status` = 5';
    $update_order .= ' where order_sn = \''.$order_sn.'\' limit 1';
    if( $db->update($update_order) ) {

        $log_data = array(
            'order_sn' => $order_sn,
            'operator' => $_SESSION['business_admin'],
            'status' => 5,
            'add_time' => time(),
            'remark' => '配货'
        );
        $db->autoInsert('order_log', array($log_data));

        $links = array(
            array('alt' => '配货中订单列表', 'link' => 'order.php?status=5'),
        );
        show_system_message('请前往配货，别让顾客久等', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//发货
if( 'deliver' == $act ) {
    if( !check_purview('pur_order_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $order_sn = trim(getGET('sn'));
    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select * from '.$db->table('order');
    $get_order .= ' where 1';
//    $get_order .= ' and is_virtual = 0';    //实体订单
    $get_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }

    if( $order['status'] != 5 && $order['status'] != 4 ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_express_list = 'select `id`,`name` from '.$db->table('express');

    assign('express_list', $db->fetchAll($get_express_list));
    assign('order', $order);
}

//同意退单
if( 'agree' == $act ) {
    if( !check_purview('pur_order_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $order_sn = trim(getGET('sn'));
    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select * from '.$db->table('order');
    $get_order .= ' where 1';
//    $get_order .= ' and is_virtual = 0';    //实体订单
    $get_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }


    $db->begin();
    $transaction = true;

    //将所有未消费的消费券置为无效
    $update_order_content = 'update '.$db->table('order_content').' set';
    $update_order_content .= ' status = 3';
    $update_order_content .= ' where order_sn = \''.$order_sn.'\'';
    $update_order_content .= ' and status = \'0\'';

    if( $db->update($update_order_content) === false ) {
        $transaction = false;
    }

    $update_order = 'update '.$db->table('order').' set';
    $update_order .= ' status = 9';
    $update_order .= ' where 1';
    $update_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    if( $db->update($update_order) === false ) {
        $transaction = false;
    }


    $log_data = array(
        'order_sn' => $order_sn,
        'operator' => $_SESSION['business_admin'],
        'status' => 9,
        'add_time' => time(),
        'remark' => '同意申请退单'
    );

    if( !$db->autoInsert('order_log', array($log_data)) ) {
        $transaction = false;
    }
    if( $transaction ) {
        $db->commit();
        $links = array(
            array('alt' => '退单中订单列表', 'link' => 'order.php?status=9'),
        );
        show_system_message('您已同意退单', $links);
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//不同意退单
if( 'disagree' == $act ) {
    if( !check_purview('pur_order_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $order_sn = trim(getGET('sn'));
    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select * from '.$db->table('order');
    $get_order .= ' where 1';
    $get_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }

    $last_status = $db->all('order_log', ['status'], [
        'order_sn' => $order_sn,
        'status' => ['neq', 8]
    ], '1', [['add_time', 'DESC']])[0]['status'];

    $db->begin();

    $transaction = $db->upgrade('order', ['status' => $last_status], ['order_sn' => $order_sn, 'status' => 8]);
    if( $transaction ) {
        $db->commit();
        add_order_log($order_sn, $_SESSION['business_account'], $last_status, '驳回用户退单');
        $links = array(
            array('alt' => '订单列表', 'link' => 'order.php'),
        );
        show_system_message('您已驳回退单', $links);
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//退单成功
if( 'refund' == $act ) {
    if( !check_purview('pur_order_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $order_sn = trim(getGET('sn'));
    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select * from '.$db->table('order');
    $get_order .= ' where 1';
//    $get_order .= ' and is_virtual = 0';    //实体订单
    $get_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }
    if( 9 != $order['status'] ) {
        show_system_message('参数错误', array());
        exit;
    }

    //订单中已消费的虚拟产品金额
    $consumed_amount = 0;   //已消费的金额
    $consumed_integral = 0; //已消费购买积分
    $consumed_integral_given = 0;   //已消费赠送积分

    $get_virtual_product_list = 'select a.product_sn, d.product_price, d.integral, d.integral_given, d.reward, d.count from '.$db->table('order_content').' as a';
    $get_virtual_product_list .= ' left join '.$db->table('product').' as p on a.product_sn = p.product_sn';
    $get_virtual_product_list .= ' left join '.$db->table('order_detail').' as d on a.product_sn = d.product_sn and a.order_sn = d.order_sn';
    $get_virtual_product_list .= ' where a.order_sn = \''.$order_sn.'\'';
    $get_virtual_product_list .= ' and a.status <> 0';
    $virtual_product_list = $db->fetchAll($get_virtual_product_list);
    if( $virtual_product_list ) {
        foreach( $virtual_product_list as $virtual_product ) {
            $consumed_amount += $virtual_product['product_price'] * $virtual_product['count'];
            $consumed_integral += $virtual_product['integral'];
            $consumed_integral_given += $virtual_product['integral_given'];
        }
    }
    //会员推荐路径
    $get_path = 'select `path` from '.$db->table('member').' where account = \''.$order['account'].'\' limit 1';
    $path = $db->fetchOne($get_path);

    //事务开始
    $db->begin();
    $transaction = true;

    //更新订单状态
    $update_order = 'update '.$db->table('order').' set';
    $update_order .= ' status = 10 ';
    $update_order .= ' where 1';
    $update_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    if( $db->update($update_order) === false ) {
        $transaction = false;
    } else {
        $log_data = array(
            'order_sn' => $order_sn,
            'operator' => $_SESSION['business_admin'],
            'status' => 10,
            'add_time' => time(),
            'remark' => '退单成功'
        );
        $db->autoInsert('order_log', array($log_data));
    }

    //是否支付过
    $check_paid = 'select * from '.$db->table('order_log').' where order_sn = \''.$order_sn.'\' and status = 3';
    $paid = $db->fetchRow($check_paid);

    $check_received = 'select * from '.$db->table('order_log').' where order_sn = \''.$order_sn.'\' and status = 7';
    $received = $db->fetchRow($check_received);

    $balance_paid = $order['balance_paid'];
    $reward_paid = $order['reward_paid'];
    $integral_paid = $order['integral_paid'] - $consumed_integral;
    $order_amount = $order['product_amount'] + $order['delivery_fee'] - $consumed_amount;
    $order_amount_paid = $order_amount - $reward_paid/$config['reward_rate'] + $consumed_amount;

    if( empty($paid) ) {    //未支付
        add_memeber_exchange_log($order['account'], $balance_paid, $reward_paid, $integral_paid, 0, 0, $_SESSION['business_admin'], $order_sn.'退单');
    } else {    //已支付
        if($order['payment_id'] == 1) {
            //微信退款
            $refund_response = refund($config['appid'], $config['mch_id'], $config['mch_key'], $order_sn, $order_amount_paid, $config['wx_ssl_cert'], $config['wx_ssl_key']);

            if(is_string($refund_response)) {
                if (!empty($config['mini_appid'])) {
                    $refund_response = refund($config['mini_appid'], $config['mch_id'], $config['mch_key'], $order_sn, $order_amount_paid, $config['wx_ssl_cert'], $config['wx_ssl_key']);
                }
            }

            if(is_string($refund_response)) {
                $transaction = false;
                $db->rollback();
                show_system_message($refund_response, array());
                exit;
            }

            add_memeber_exchange_log($order['account'], 0, $reward_paid, $integral_paid, 0, 0, $_SESSION['business_admin'], $order_sn.'退单');
        } else if($order['payment_id'] == 2) {
            //支付宝退款
            $loader->includeClass('Alipay');
            $alipay = new Alipay(ROOT_PATH.'/'.$config['alipay_private_key'], ROOT_PATH.'/'.$config['alipay_public_key']);
            $refund_response = $alipay->refund($config['alipay_app_id'], $order_sn, $order_amount_paid);
            $log->record($refund_response);

            if(is_string($refund_response)) {
                $transaction = false;
                $db->rollback();
                show_system_message($refund_response, array());
                exit;
            }

            add_memeber_exchange_log($order['account'], 0, $reward_paid, $integral_paid, 0, 0, $_SESSION['business_admin'], $order_sn.'退单');
        } else {
            add_memeber_exchange_log($order['account'], $order_amount_paid, $reward_paid, $integral_paid, 0, 0, $_SESSION['business_admin'], $order_sn.'退单');
        }

        if(empty($received))
        {
            //未收货
            $update_business = 'update '.$db->table('business').' set '.
                '`trade`=`trade`-'.($order_amount - $order['reward_amount']).
                ' where 1';

            if($db->update($update_business) !== false)
            {
                $business_log_data = array(
                    'business_account' => $order['business_account'],
                    'balance' => 0,
                    'trade' => -1*($order_amount - $order['reward_amount']),
                    'add_time' => time(),
                    'operator' => $_SESSION['business_admin'],
                    'remark' => $order_sn.'退单'
                );

                if($db->autoInsert('business_exchange_log', array($business_log_data)))
                {
                    $db->autoDelete('trade', '`remark`=\''.$order_sn.'\'');
                }
            }
        } else {
            //已收货
            $update_business = 'update '.$db->table('business').' set '.
                '`balance`=`balance`-'.($order_amount - $order['reward_amount']).
                ' where 1';

            if($db->update($update_business) !== false)
            {
                $business_log_data = array(
                    'business_account' => $order['business_account'],
                    'trade' => 0,
                    'balance' => -1*($order_amount - $order['reward_amount']),
                    'add_time' => time(),
                    'operator' => $_SESSION['business_admin'],
                    'remark' => $order_sn.'退单'
                );

                $db->autoInsert('business_exchange_log', array($business_log_data));
            }
        }
    }

    distribution_settle(-($order['reward_amount']), -($order['integral_given_amount']), $path, $order_sn);

    if( $transaction ) {
        $db->commit();
        $links = array(
            array('alt' => '已退单订单列表', 'link' => 'order.php?status=10'),
        );
        show_system_message('退单已完成', $links);
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'detail' == $act ) {
    if( !check_purview('pur_order_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

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


    $order_sn = trim(getGET('sn'));

    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select a.*, p.province_name, city.city_name, d.district_name, g.group_name, e.name as express_name from '.$db->table('order').' as a';
    $get_order .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_order .= ' left join '.$db->table('city').' as city on a.city = city.id';
    $get_order .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_order .= ' left join '.$db->table('group').' as g on a.group = g.id';
    $get_order .= ' left join '.$db->table('express').' as e on a.express_id = e.id';

    $get_order .= ' where 1';
//    $get_order .= ' and a.is_virtual = 0';    //实体订单
    $get_order .= ' and order_sn = \''.$order_sn.'\'';
    $get_order .= ' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }

    $order['add_time_str'] = $order['add_time'] ? date('Y-m-d H:i:s', $order['add_time']) : '';
    $order['delivery_time_str'] = $order['delivery_time'] ? date('Y-m-d H:i:s', $order['delivery_time']) : '未发货';
    $order['receive_time_str'] = $order['receive_time'] ? date('Y-m-d H:i:s', $order['receive_time']) : '未收货';
    $order['pay_time_str'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '未支付';
    $order['status_str'] = $status_str[$order['status']];

    $get_order_detail = 'select o.*, p.img from '. $db->table('order_detail').' as o';
    $get_order_detail .= ' left join '.$db->table('product').' as p on o.product_sn = p.product_sn';
    $get_order_detail .= ' where 1';
    $get_order_detail .= ' and o.order_sn = \''.$order_sn.'\'';

    $order_detail = $db->fetchAll($get_order_detail);

    assign('order', $order);
    assign('order_detail', $order_detail);
}

//导出数据
if( 'export' == $act ) {
    if( !check_purview('pur_order_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
}

//付款
if( 'pay' == $act ) {
    if( !check_purview('pur_order_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $order_sn = trim(getGET('sn'));
    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select * from '.$db->table('order');
    $get_order .= ' where 1';
//    $get_order .= ' and is_virtual = 0';    //实体订单
    $get_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        show_system_message('订单不存在', array());
        exit;
    }

    $update_order = 'update '.$db->table('order').' set';
    $update_order .= ' status = 4';
    $update_order .= ', pay_time = '.time();
    $update_order .= ', payment_name = \'线下支付\'';
    $update_order .= ', payment_id = 0';
    $update_order .= ' where 1';
    $update_order .= ' and order_sn = \''.$order_sn.'\' limit 1';

    if( $db->update($update_order) ) {
        $log_data = array(
            'order_sn' => $order_sn,
            'operator' => $_SESSION['business_admin'],
            'status' => 4,
            'add_time' => time(),
            'remark' => '确认已付款'
        );
        $db->autoInsert('order_log', array($log_data));

        /* 付款成功相关业务 */
        $log->record($order_sn.'支付成功');
        //2. 订单结算
        $get_path = 'select `path` from '.$db->table('member').' where `account`=\''.$order['account'].'\'';
        $path = $db->fetchOne($get_path);
        distribution_settle($order['reward_amount'], $order['integral_given_amount'], $path, $order_sn);

        //赠送积分
        if($order['given_integral_amount'] > 0) {
            add_memeber_exchange_log($order['account'], 0, 0, 0, $order['given_integral_amount'], 0, 'settle', $order_sn.'赠送积分');
            add_member_reward($order['account'], 0, $order['given_integral_amount'], $order_sn);
        }
        //3. 新增商家收入 线下付款不计入商家收入
        /*
        $business_income = $order['product_amount'] + $order['delivery_fee'] - $order['reward_amount'];
        if(add_business_exchange($order['business_account'], 0, $business_income, $order['account'], '用户在线支付'))
        {
            add_business_trade($order['business_account'], $business_income, $order_sn);
        } else {
            //增加商家收入失败
        }
        */

        $get_order_detail = 'select `product_sn`,`product_name`,`count`,`is_virtual`,`attributes` from '.$db->table('order_detail').' where `order_sn`=\''.$order_sn.'\'';
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

                add_order_content($order['business_account'], $order['account'], $order['mobile'], $order_sn, $od['product_sn'], $od['product_name'], $virtual_content, 2);
            } else{
                $delivery = true;
            }
        }

        if( $delivery ) {
            $order_data = array(
                'status' => 4,
            );
            $db->autoUpdate('order', $order_data, '`order_sn`=\''.$order_sn.'\' and `status`<>4');
        }

        $links = array(
            array('alt' => '待发货订单列表', 'link' => 'order.php?status=4'),
        );
        show_system_message('确认客户已付款', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//预览
if( 'preview' == $act ) {
    if( !check_purview('pur_order_view', $_SESSION['business_purview']) ) {
        echo '权限不足';
        exit;
    }
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


    $order_sn = trim(getGET('sn'));

    if( '' == $order_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select a.*, p.province_name, city.city_name, d.district_name, g.group_name, e.name as express_name from '.$db->table('order').' as a';
    $get_order .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_order .= ' left join '.$db->table('city').' as city on a.city = city.id';
    $get_order .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_order .= ' left join '.$db->table('group').' as g on a.group = g.id';
    $get_order .= ' left join '.$db->table('express').' as e on a.express_id = e.id';

    $get_order .= ' where 1';
//    $get_order .= ' and a.is_virtual = 0';    //实体订单
    $get_order .= ' and order_sn = \''.$order_sn.'\'';
    $get_order .= ' limit 1';

    $order = $db->fetchRow($get_order);
    if( empty($order) ) {
        echo '订单不存在';
        exit;
    }

    $order['add_time_str'] = $order['add_time'] ? date('Y-m-d H:i:s', $order['add_time']) : '';
    $order['delivery_time_str'] = $order['delivery_time'] ? date('Y-m-d H:i:s', $order['delivery_time']) : '未发货';
    $order['receive_time_str'] = $order['receive_time'] ? date('Y-m-d H:i:s', $order['receive_time']) : '未收货';
    $order['pay_time_str'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '未支付';
    $order['status_str'] = $status_str[$order['status']];

    $get_order_detail = 'select o.*, p.img from '. $db->table('order_detail').' as o';
    $get_order_detail .= ' left join '.$db->table('product').' as p on o.product_sn = p.product_sn';
    $get_order_detail .= ' where 1';
    $get_order_detail .= ' and o.order_sn = \''.$order_sn.'\'';

    $order_detail = $db->fetchAll($get_order_detail);
    if( $order_detail ) {
        foreach( $order_detail as $key => $detail ) {
            $order_detail[$key]['product_amount'] = $detail['product_price'] * $detail['count'];
        }
    }

    assign('order', $order);
    assign('order_detail', $order_detail);
    assign('product_count', count($order_detail));
}
$template .= $act.'.phtml';
$smarty->display($template);
