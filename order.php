<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午4:47
 */
include 'library/init.inc.php';

$template = 'order-list.phtml';
$action = 'list|detail|comment|express_info|product_comment';
$act = check_action($action, getGET('act'));
$operation = 'pay_now|cancel|rollback|receive|comment|product_comment|sort';
$opera = check_action($operation, getPOST('opera'));

if('' == $act)
{
    $act = 'list';
}

if('sort' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

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

    $status = intval(getPOST('mode'));

    $get_order_list = 'select o.`order_sn`,b.`shop_name`,o.`status`,o.`amount`,o.`business_account` from '.$db->table('order').' as o join '.
        $db->table('business').' as b using(`business_account`) where o.`account`=\''.$_SESSION['account'].'\'';

    if($status > 0 && $status < 8)
    {
        $get_order_list .= ' and o.`status`='.$status;
    }

    if($status > 0 && $status >= 8)
    {
        $get_order_list .= ' and o.`status`>='.$status;
    }

    $get_order_list .= ' order by o.`add_time` DESC';

    $response['sql'] = $get_order_list;

    $order_list = $db->fetchAll($get_order_list);

    if($order_list)
    {
        foreach ($order_list as $key => $ol)
        {
            $get_order_detail = 'select od.`product_attributes`,od.`product_name`,od.`product_sn`,p.`id`,p.`img`,od.`count` from ' . $db->table('order_detail') . ' as od ' .
                ' join ' . $db->table('product') . ' as p using(`product_sn`) where od.`order_sn`=\'' . $ol['order_sn'] . '\'';
            $order_list[$key]['order_detail'] = $db->fetchAll($get_order_detail);
            $order_list[$key]['show_status'] = $status_str[$ol['status']];
        }
    }

    assign('order_list', $order_list);

    $response['error'] = 0;
    $response['content'] = $smarty->fetch('order-list-item.phtml');

    echo json_encode($response);
    exit;
}

if('product_comment' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $product_sn = getPOST('product_sn');
    $star = getPOST('star');
    $comment = getPOST('comment');

    if($product_sn == '')
    {
        $response['msg'] = '参数错误';
    } else {
        $product_sn = $db->escape($product_sn);
    }

    $star = intval($star);
    if($star <= 0)
    {
        $response['msg'] .= '-请选择星级<br/>';
    }

    if($comment == '')
    {
        $response['msg'] .= '-请填写评价<br/>';
    } else {
        $comment = htmlspecialchars($comment);
        $comment = $db->escape($comment);
    }

    if($response['msg'] == '')
    {
        $comment_data = array(
            'add_time' => time(),
            'comment' => $comment,
            'star' => $star,
            'product_sn' => $product_sn,
            'account' => $_SESSION['account'],
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
            $product_data = array('star'=>$comment_info['star']/$comment_info['c']);

            $db->autoUpdate('product', $product_data, '`product_sn`=\''.$product_sn.'\'');

            $response['error'] = 0;
            $response['msg'] = '评论成功';
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
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

    $response = array('error'=>1, 'msg'=>'');

    if($order_sn == '')
    {
        $response['msg'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_business_account = 'select `business_account` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$_SESSION['account'].'\'';
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
                    add_order_log($order_sn, $_SESSION['account'], 12, '用户评价订单');
                    $response['msg'] = '服务评价成功';
                    $response['error'] = 0;
                } else {
                    $response['msg'] = '系统繁忙，请稍后再试';
                }
            } else {
                $response['msg'] = '系统繁忙，请稍后再试';
            }
        } else {
            $response['msg'] = '订单错误';
        }
    }

    echo json_encode($response);
    exit;
}

if('rollback' == $opera)
{
    $order_sn = getPOST('order_sn');

    $response = array('error'=>1, 'msg'=>'');

    if($order_sn == '')
    {
        $response['msg'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_order_sn = 'select `order_sn` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$_SESSION['account'].'\'';
        $order_sn = $db->fetchOne($check_order_sn);

        if($order_sn)
        {
            $data = array('status'=>8);

            if($db->autoUpdate('order', $data, '`order_sn`=\''.$order_sn.'\''))
            {
                add_order_log($order_sn, $_SESSION['account'], 8, '用户申请退单');
                //将款项打到商家账户中
                $response['error'] = 0;
                $response['msg'] = '申请退单成功，稍后商家客服将直接与您联系';
            } else {
                $response['msg'] = '系统繁忙，请稍后再试';
            }
        } else {
            $response['msg'] = '订单错误';
        }
    }

    echo json_encode($response);
    exit;
}

if('receive' == $opera)
{
    $order_sn = getPOST('order_sn');

    $response = array('error'=>1, 'msg'=>'');

    if($order_sn == '')
    {
        $response['msg'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_order_sn = 'select `order_sn` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$_SESSION['account'].'\'';
        $order_sn = $db->fetchOne($check_order_sn);

        if($order_sn)
        {
            $data = array(
                'status'=>7,
                'receive_time'=>time()
            );

            if($db->autoUpdate('order', $data, '`order_sn`=\''.$order_sn.'\'') && $db->get_affect_rows())
            {
                add_order_log($order_sn, $_SESSION['account'], 7, '用户确认收货');
                //将款项打到商家账户中
                //读取担保交易记录，并将款项打入到商家的余额中
                $get_trade = 'select `id`,`business_account`,`trade` from '.$db->table('trade').' where `remark`=\''.$order_sn.'\' and `status`=0';
                $trade = $db->fetchRow($get_trade);

                if(add_business_exchange($trade['business_account'], $trade['trade'], -1*$trade['trade'], $_SESSION['account'], '用户确认收货'))
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
                        if (add_memeber_exchange_log($reward['account'], 0, $reward['reward'], $reward['integral'], -1 * $reward['integral'], -1 * $reward['reward'], $_SESSION['account'], $order_sn . '奖金发放')) {
                            $reward_status = array(
                                'status' => 1,
                                'solve_time' => time()
                            );

                            $db->autoUpdate('member_reward', $reward_status, '`id`=' . $reward['id']);
                        }
                    }
                }

                $response['error'] = 0;
                $response['msg'] = '确认收货成功';
            } else {
                $response['msg'] = '系统繁忙，请稍后再试';
            }
        } else {
            $response['msg'] = '订单错误';
        }
    }

    echo json_encode($response);
    exit;
}

if('cancel' == $opera)
{
    $order_sn = getPOST('order_sn');

    $response = array('error'=>1, 'msg'=>'');

    if($order_sn == '')
    {
        $response['msg'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_order_sn = 'select `order_sn` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$_SESSION['account'].'\'';
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
                $response['msg'] = '订单取消成功';
                $db->commit();

            } else {
                $response['msg'] = '001:取消订单失败';
                $db->rollback();
            }
        } else {
            $response['msg'] = '订单错误';
        }
    }

    echo json_encode($response);
    exit;
}

if('pay_now' == $opera)
{
    $order_sn = getPOST('order_sn');

    $response = array('error'=>1, 'msg'=>'');

    if($order_sn == '')
    {
        $response['msg'] = '订单编号为空';
    } else {
        $order_sn = $db->escape($order_sn);
        $check_order_sn = 'select `order_sn` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$_SESSION['account'].'\'';
        $order_sn = $db->fetchOne($check_order_sn);

        if($order_sn)
        {
            $_SESSION['order_sn'] = $order_sn;
            $response['error'] = 0;
            $response['order_sn'] = $order_sn;
        } else {
            $response['msg'] = '订单错误';
        }
    }

    echo json_encode($response);
    exit;
}

if('product_comment' == $act)
{
    $product_sn = getGET('product_sn');

    if($product_sn == '')
    {
        redirect('index.php');
    }

    $template = 'product_comment.phtml';

    $get_product = 'select `name`,`product_sn`,`img` from '.$db->table('product').' where `product_sn`=\''.$product_sn.'\'';

    $product = $db->fetchRow($get_product);

    assign('product', $product);
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
    assign('express_state', $express_state);

    $order_sn = getGET('order_sn');

    if($order_sn == '')
    {
        $response['msg'] = '参数错误';
    } else {
        $order_sn = $db->escape($order_sn);
    }

    $get_order_info = 'select * from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';
    $order = $db->fetchRow($get_order_info);

    if($order && $order['status'] == 6)
    {
        $get_express_info = 'select `code`,`name` from '.$db->table('express').' where `id`='.$order['express_id'];
        $express_info = $db->fetchRow($get_express_info);
        $express_flow = query_express($express_info['code'], $order['express_sn']);
        $express_flow = json_decode($express_flow, true);
        assign('express_flow', $express_flow);
        assign('express_info', $express_info);
    }
    assign('order', $order);

    $get_order_detail = 'select p.`img` from '.$db->table('order_detail').' as od join '.$db->table('product').
                        ' as p using(`product_sn`) where od.`order_sn`=\''.$order_sn.'\'';
    assign('product_img', $db->fetchOne($get_order_detail));

    $template = 'track.phtml';
}

if('comment' == $act)
{
    $template = 'comment.phtml';

    $order_sn = getGET('sn');

    if($order_sn == '')
    {
        redirect('order.php');
    }
    $order_sn = $db->escape($order_sn);
    $check_order_sn = 'select `order_sn` from ' . $db->table('order') . ' where `order_sn`=\'' . $order_sn . '\' and `account`=\''.$_SESSION['account'].'\'';
    $order_sn = $db->fetchOne($check_order_sn);
    if(!$order_sn)
    {
        redirect('order.php');
    }


    $check_order_comment = 'select `is_comment` from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';
    $is_comment = $db->fetchOne($check_order_comment);
    assign('is_comment', $is_comment);

    $get_order_detail = 'select DISTINCT od.`product_name`,od.`product_sn`,p.`img` from '.$db->table('order_detail').' as od '.
                        ' join '.$db->table('product').' as p using(`product_sn`) where od.`order_sn`=\''.$order_sn.'\'';

    $order_detail = $db->fetchAll($get_order_detail);

    foreach($order_detail as $key=>$od)
    {
        $check_product_comment = 'select `id` from '.$db->table('comment').' where `product_sn`=\''.$od['product_sn'].
                                 '\' and `account`=\''.$_SESSION['account'].'\'';
        $order_detail[$key]['has_comment'] = $db->fetchOne($check_product_comment);
    }

    assign('order_sn', $order_sn);

    assign('order_detail', $order_detail);
}

if('detail' == $act)
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

    if($order_sn == '')
    {
        redirect('order.php');
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select o.`remark`,o.`product_amount`,o.`integral_paid`,o.`balance_paid`,o.`reward_paid`,o.`add_time`,o.`delivery_fee`,o.`pay_time`,o.`order_sn`,b.`shop_name`,o.`status`,o.`amount`,o.`province`,o.`city`,o.`district`,o.`group`,o.`mobile`,o.`consignee`,o.`address`,o.`integral_amount` from '.$db->table('order').' as o join '.
                 $db->table('business').' as b using(`business_account`) where o.`account`=\''.$_SESSION['account'].'\' and o.`order_sn`=\''.$order_sn.'\'';

    $order = $db->fetchRow($get_order);

    $get_order_detail = 'select `od`.`integral`,od.`product_attributes`,od.`product_price`,od.`product_name`,od.`product_sn`,p.`id`,p.`img`,od.`count` from '.$db->table('order_detail').' as od '.
            ' join '.$db->table('product').' as p using(`product_sn`) where od.`order_sn`=\''.$order_sn.'\'';

    $order['order_detail'] = $db->fetchAll($get_order_detail);
    $order['show_status'] = $status_str[$order['status']];

    $get_province_name = 'select `province_name` from '.$db->table('province').' where `id`='.$order['province'];
    $get_city_name = 'select `city_name` from '.$db->table('city').' where `id`='.$order['city'];
    $get_district_name = 'select `district_name` from '.$db->table('district').' where `id`='.$order['district'];
    $get_group_name = 'select `group_name` from '.$db->table('group').' where `id`='.$order['group'];

    $order['province_name'] = $db->fetchOne($get_province_name);
    $order['city_name'] = $db->fetchOne($get_city_name);
    $order['district_name'] = $db->fetchOne($get_district_name);
    $order['group_name'] = $db->fetchOne($get_group_name);

    assign('order', $order);
    $template = 'order-detail.phtml';
    $_SESSION['order_sn'] = $order_sn;
}

if('list' == $act)
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
        12 => '已完成'
    );

    $status = intval(getGET('status'));

    assign('status', $status);

    $get_order_list = 'select o.`order_sn`,b.`shop_name`,o.`status`,o.`amount`,o.`business_account` from '.$db->table('order').' as o join '.
                      $db->table('business').' as b using(`business_account`) where o.`account`=\''.$_SESSION['account'].'\'';

    if($status > 0 && $status < 8)
    {
        $get_order_list .= ' and o.`status`='.$status;
    }

    if($status > 0 && $status >= 8)
    {
        $get_order_list .= ' and o.`status`>='.$status;
    }

    $get_order_list .= ' order by o.`add_time` DESC';

    $order_list = $db->fetchAll($get_order_list);

    if($order_list)
    {
        foreach ($order_list as $key => $ol)
        {
            $get_order_detail = 'select od.`product_attributes`,od.`product_name`,od.`product_sn`,p.`id`,p.`img`,od.`count` from ' . $db->table('order_detail') . ' as od ' .
                ' join ' . $db->table('product') . ' as p using(`product_sn`) where od.`order_sn`=\'' . $ol['order_sn'] . '\'';
            $order_list[$key]['order_detail'] = $db->fetchAll($get_order_detail);
            $order_list[$key]['show_status'] = $status_str[$ol['status']];
        }
    }

    assign('order_list', $order_list);
}

$smarty->display($template);