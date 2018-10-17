<?php
/**
 * 交易管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-10
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'exchange/';
assign('subTitle', '交易管理');

$action = 'view|reward';
$operation = '';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================


//===========================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_exchange_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);
    $and_where = '';
    $pattern = '#[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}#';
    if( $st ) {
        if( preg_match($pattern, $st) ) {
            $and_where .= ' and settle_time > ' . $start_time;
        } else {
            $st = '';
        }
    }
    if( $et ) {
        if( preg_match($pattern, $et) ) {
            $and_where .= ' and settle_time < ' . ($end_time + 3600 * 24);
        } else {
            $st = '';
        }
    }

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from '.$db->table('trade');
    $get_total .= ' where 1';
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    $get_trade_list = 'select * from '.$db->table('trade');
    $get_trade_list .= ' where 1';
    $get_trade_list .= $and_where;
    $get_trade_list .= ' order by settle_time desc';
    $get_trade_list .= ' limit '.$offset.','.$count;

    $trade_list = $db->fetchAll($get_trade_list);
    if( $trade_list ) {
        foreach( $trade_list as $key => $value ) {
            $trade_list[$key]['settle_time_str'] = date('Y-m-d H:i:s', $value['settle_time']);
            $trade_list[$key]['solve_time_str'] = empty($value['solve_time']) ? '' : date('Y-m-d H:i:s', $value['solve_time']);
            $trade_list[$key]['status_str'] = ( $value['status'] == 0 ) ? '待收' : '已收';
        }
    }
    assign('trade_list', $trade_list);

    assign('count', $count);
    assign('st', $st);
    assign('et', $et);

    create_pager($page, $total_page, $total);
}

if( 'reward' == $act ) {
    if( !check_purview('pur_exchange_reward', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);
    $and_where = '';
    $pattern = '#[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}#';
    if( $st ) {
        if( preg_match($pattern, $st) ) {
            $and_where .= ' and settle_time > ' . $start_time;
        } else {
            $st = '';
        }
    }
    if( $et ) {
        if( preg_match($pattern, $et) ) {
            $and_where .= ' and settle_time < ' . ($end_time + 3600 * 24);
        } else {
            $st = '';
        }
    }

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from '.$db->table('trade');
    $get_total .= ' where 1';
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    $get_member_reward_list = 'select * from '.$db->table('member_reward');
    $get_member_reward_list .= ' where 1';
    $get_member_reward_list .= $and_where;
    $get_member_reward_list .= ' order by settle_time desc';
    $get_member_reward_list .= ' limit '.$offset.','.$count;

    $member_reward_list = $db->fetchAll($get_member_reward_list);
    if( $member_reward_list ) {
        foreach( $member_reward_list as $key => $value ) {
            $member_reward_list[$key]['settle_time_str'] = date('Y-m-d H:i:s', $value['settle_time']);
            $member_reward_list[$key]['solve_time_str'] = empty($value['solve_time']) ? '' : date('Y-m-d H:i:s', $value['solve_time']);
            $member_reward_list[$key]['status_str'] = ( $value['status'] == 0 ) ? '待收' : '已收';
        }
    }
    assign('member_reward_list', $member_reward_list);

    assign('count', $count);
    assign('st', $st);
    assign('et', $et);

    create_pager($page, $total_page, $total);
}

$template .= $act.'.phtml';
$smarty->display($template);

