<?php
/**
 * 消费券管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-10-16
 * @version 1.0.0
 */

include 'library/init.inc.php';
business_base_init();

$template = 'virtual_order/';
assign('subTitle', '消费券管理');

$action = 'view|consume';
$operation = 'consume';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

$status_str = array(
    0 => '有效',
    1 => '已使用',
    2 => '已过期',
    3 => '失效',
);

//===========================================================================
if( 'consume' == $opera ) {
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());
    if (!check_purview('pur_virtual_order_edit', $_SESSION['business_purview'])) {
        $response['msg'] = '权限不足';
        echo json_encode($response);
        exit;
    }
    $mobile = trim(getPOST('mobile'));
    $code = trim(getPOST('code'));

    if( '' == $mobile || 11 != strlen($mobile) ) {
        $response['msg'] = '参数错误';
        $response['errmsg']['mobile'] = '-请输入手机号码';
    }

    if( '' == $code ) {
        $response['msg'] = '参数错误';
        $response['errmsg']['code'] = '-请输入消费码';
    }

    if( 0 != count($response['errmsg']) ) {
        echo json_encode($response);
        exit;
    }
    $mobile = $db->escape($mobile);
    $code = $db->escape($code);

    $get_content = 'select * from '.$db->table('order_content');
    $get_content .= ' where `mobile` = \''.$mobile.'\'';
    $get_content .= ' and `business_account` = \''.$_SESSION['business_account'].'\'';
    $get_content .= ' and `code` = \''.$code.'\'';
    $get_content .= ' limit 1';

    $content = $db->fetchRow($get_content);
    if( empty($content) ) {
        $response['msg'] = '消费券不存在';
        echo json_encode($response);
        exit;
    }

    if( 0 != $content['status'] ) {
        $response['msg'] = '消费券无法使用';
        echo json_encode($response);
        exit;
    }

    if( time() < $content['begin_time'] || time() > $content['end_time'] ) {
        $response['msg'] = '消费券超过有效期';
        echo json_encode($response);
        exit;
    }


    //存在一个实体产品则不改变订单状态
    $need_change_order_status = true;

    $get_detail_list = 'select * from '.$db->table('order_detail').' where order_sn = \''.$content['order_sn'].'\'';
    $detail_list = $db->fetchAll($get_detail_list);

    $product = '';
    if( $detail_list ) {
        foreach( $detail_list as $key => $detail ) {
            if( $detail['is_virtual'] == 0 ) {
                $need_change_order_status = false;
            }
            if( $detail['product_sn'] == $content['product_sn'] ) {
                $product = $detail;
            }
        }
    }

    //同张订单的所有卡券都消费完则改变订单状态
    if( $need_change_order_status ) {
        $get_content_list = 'select * from '.$db->table('order_content').' where order_sn = \''.$content['order_sn'].'\' and id <> '.$content['id'];
        $content_list = $db->fetchAll($get_content_list);

        $all_use = true;
        if( $content_list ) {
            foreach( $content_list as $key => $value ) {
                if( $value['status'] == 0 ) {   //还存在另一张消费券未消费
                    $all_use = false;
                    break;
                }
            }
            $need_change_order_status = $all_use ? true : false;

        }
    }

    $data = array(
        'status' => 1,
    );

    $db->begin();
    $transaction = true;
    if( !$db->autoUpdate('order_content', $data, 'id = '.$content['id']) ) {
        $transaction = false;
    }

    if( $need_change_order_status ) {
        $data = array(
            'status' => 12,
        );

        if ( !$db->autoUpdate('order', $data, 'order_sn = \''.$content['order_sn'].'\'') ) {
            $transaction = false;
        }
    }

    //将当前产品的消费额打到商家余额，扣除担保交易
    $update_business = 'update '.$db->table('business').' set';
    $update_business .= ' trade = trade - '.$product['product_price'] * $product['count'];
    $update_business .= ', balance = balance + '.$product['product_price'] * $product['count'];
    $update_business .= ' where business_account = \''.$_SESSION['business_account'].'\' limit 1';

    $log_data = array(
        'business_account' => $_SESSION['business_account'],
        'trade' => -$product['product_price'] * $product['count'],
        'balance' => $product['product_price'] * $product['count'],
        'operator' => $_SESSION['business_admin'],
        'remark' => '虚拟产品消费',
        'add_time' => time()
    );
    $db->autoInsert('business_exchange_log', $log_data);

    if( $transaction ) {
        $db->commit();
        $response['error'] = 0;
        $response['msg'] = '消费成功';
    } else {
        $db->rollback();
        $response['msg'] = '系统繁忙，请稍后重试';
    }

    echo json_encode($response);
    exit;
}

//===========================================================================

if( 'view' == $act ) {
    if (!check_purview('pur_virtual_order_view', $_SESSION['business_purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $status = intval(getGET('status'));
    $and_where = '';
    if( $status == 0 ) {
        assign('status', 0);
        assign('order_status', '');
    } else {
        switch( $status ) {
            case 1: $and_where .= ' and a.status = 0';break;
            case 2: $and_where .= ' and a.status = 1';break;
            case 3: $and_where .= ' and a.status = 2';break;
            case 4: $and_where .= ' and a.status = 3';break;
            default: $and_where .= '';break;
        }
        assign('status', $status);
        assign('order_status', $status_str[$status - 1]);
    }

    $order_sn = trim(getGET('order_sn'));
    if( $order_sn ) {
        $order_sn = $db->escape($order_sn);
        $and_where .= ' and order_sn like \'%'.$order_sn.'%\'';
    }
    assign('order_sn', $order_sn);

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from '.$db->table('order_content').' as a';
    $get_total .= ' where a.business_account = \''.$_SESSION['business_account'].'\'';
    $get_total .= $and_where;
    $total = $db->fetchOne($get_total);

    $count = ( $count <= 0 ) ? 10 : $count;
    $total_page = ceil( $total / $count );
    $page = ( $page > $total_page ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $offset = ( $page - 1 ) * $count;

    $get_content_list = 'select a.*, p.img from '.$db->table('order_content').' as a';
    $get_content_list .= ' left join '.$db->table('product').' as p on a.product_sn = p.product_sn';
    $get_content_list .= ' where a.business_account = \''.$_SESSION['business_account'].'\'';
    $get_content_list .= $and_where;
    $get_content_list .= ' order by id desc';
    $get_content_list .= ' limit '.$offset.','.$count;
    $content_list = $db->fetchAll($get_content_list);
//    echo $get_content_list;exit;

    if( $content_list ) {
        foreach( $content_list as $key => $content ) {
            $content_list[$key]['status_str'] = $status_str[$content['status']];
            $content_list[$key]['begin_time_str'] = $content['begin_time'] ? date('Y-m-d H:i:s', $content['begin_time']) : '';
            $content_list[$key]['end_time_str'] = $content['end_time'] ? date('Y-m-d H:i:s', $content['end_time']) : '';
        }
    }

    assign('content_list', $content_list);
    create_pager($page, $total_page, $total);

    assign('count', $count);
}


if( 'consume' == $act ) {
    if (!check_purview('pur_virtual_order_edit', $_SESSION['business_purview'])) {
        show_system_message('权限不足', array());
        exit;
    }
}


$template .= $act.'.phtml';
$smarty->display($template);

