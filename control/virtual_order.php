<?php
/**
 * 消费券管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-10-16
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'virtual_order/';
assign('subTitle', '消费券管理');

$action = 'view';
$operation = '';

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


//===========================================================================

if( 'view' == $act ) {
    if (!check_purview('pur_virtual_order_view', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $status = intval(getGET('status'));
    if( $status == 0 ) {
        assign('status', 0);
        assign('order_status', '');
        $and_where = '';
    } else {
        switch( $status ) {
            case 1: $and_where .= ' and a.status = 0';break;
            case 2: $and_where .= ' and a.status = 1';break;
            case 3: $and_where .= ' and a.status = 2';break;
            case 4: $and_where .= ' and a.status = 3';break;
            default: $and_where = '';break;
        }
        assign('status', $status);
        assign('order_status', $status_str[$status - 1]);
    }

//    $st = trim(getGET('st'));
//    $et = trim(getGET('et'));
//    $start_time = strtotime($st);
//    $end_time = strtotime($et);
//
//    $pattern = '#[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}#';
//    if( $st ) {
//        if( preg_match($pattern, $st) ) {
//            $and_where .= ' and a.add_time > ' . $start_time;
//        } else {
//            $st = '';
//        }
//    }
//    if( $et ) {
//        if( preg_match($pattern, $et) ) {
//            $and_where .= ' and a.add_time < ' . ($end_time + 3600 * 24);
//        } else {
//            $st = '';
//        }
//    }

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from '.$db->table('order_content').' as a';
    $get_total .= ' where 1';
    $get_total .= $and_where;
    $total = $db->fetchOne($get_total);

    $count = ( $count <= 0 ) ? 10 : $count;
    $total_page = ceil( $total / $count );
    $page = ( $page > $total_page ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $offset = ( $page - 1 ) * $count;

    $get_content_list = 'select a.*, p.img, b.shop_name from '.$db->table('order_content').' as a';
    $get_content_list .= ' left join '.$db->table('product').' as p on a.product_sn = p.product_sn';
    $get_content_list .= ' left join '.$db->table('business').' as b on a.business_account = b.business_account';
    $get_content_list .= ' where 1';
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
//    assign('st', $st);
//    assign('et', $et);
}



$template .= $act.'.phtml';
$smarty->display($template);

