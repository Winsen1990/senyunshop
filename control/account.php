<?php
/**
 * 账户明细
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-10
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'account/';
assign('subTitle', '账户明细');

$action = 'view|detail';
$operation = '';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================


//===========================================================================

if( 'view' == $act ) {
    if (!check_purview('pur_account_view', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }
    $type = intval(getGET('type'));
    if ($type == 0) {
        $table = 'member_exchange_log';
    } else {
        $table = 'business_exchange_log';
    }
    assign('type', $type);

    $search = trim(getGET('search'));
    $search = $db->escape(htmlspecialchars($search));
    $and_where = '';
    if( '' != $search ) {
        $and_where .= ($type == 0) ? ' and account like \'%'.$search.'%\'' : ' and business_account like \'%'.$search.'%\'';
    }
    assign('search', $search);

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);
    $pattern = '#[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}#';
    if ($st) {
        if (preg_match($pattern, $st)) {
            $and_where .= ' and add_time > ' . $start_time;
        } else {
            $st = '';
        }
    }
    if ($et) {
        if (preg_match($pattern, $et)) {
            $and_where .= ' and add_time < ' . ($end_time + 3600 * 24);
        } else {
            $st = '';
        }
    }

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from ' . $db->table($table);
    $get_total .= ' where 1';
    $get_total .= $and_where;
    $total = $db->fetchOne($get_total);

    $page = ($page > $total) ? $total : $page;
    $page = ($page <= 0) ? 1 : $page;
    $count = ($count <= 0) ? 10 : $count;
    $offset = ($page - 1) * $count;
    $total_page = ceil($total / $count);

    $get_exchange_list = 'select * from '.$db->table($table);
    $get_exchange_list .= ' where 1';
    $get_exchange_list .= $and_where;
    $get_exchange_list .= ' order by add_time desc';
    $get_exchange_list .= ' limit '.$offset.','.$count;
    $exchange_list = $db->fetchAll($get_exchange_list);
//    echo $get_exchange_list;exit;
    if( $exchange_list ) {
        foreach( $exchange_list as $key => $value ) {
            $exchange_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $value['add_time']);
        }
    }
    assign('exchange_list', $exchange_list);

    assign('count', $count);
    assign('st', $st);
    assign('et', $et);

    create_pager($page, $total_page, $total);
}

if( 'detail' == $act ) {
    if (!check_purview('pur_account_view', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getGET('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }
    assign('account', $account);
    $account = $db->escape($account);
    $where = '';

    $type = intval(getGET('type'));
    if ($type == 0) {
        $table = 'member_exchange_log';
        $exists_table = 'member';
        $where = ' where account = \''.$account.'\'';
    } else {
        $table = 'business_exchange_log';
        $exists_table = 'business';
        $where = ' where business_account = \''.$account.'\'';
    }
    assign('type', $type);

    $get_account = 'select * from '.$db->table($exists_table).$where.' limit 1';
    $account = $db->fetchRow($get_account);
    if( empty($account) ) {
        show_system_message('帐号不存在', array());
        exit;
    }

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);
    $and_where = '';
    $pattern = '#[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}#';
    if ($st) {
        if (preg_match($pattern, $st)) {
            $and_where .= ' and add_time > ' . $start_time;
        } else {
            $st = '';
        }
    }
    if ($et) {
        if (preg_match($pattern, $et)) {
            $and_where .= ' and add_time < ' . ($end_time + 3600 * 24);
        } else {
            $st = '';
        }
    }

    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from ' . $db->table($table);
    $get_total .= $where;
    $total = $db->fetchOne($get_total);

    $page = ($page > $total) ? $total : $page;
    $page = ($page <= 0) ? 1 : $page;
    $count = ($count <= 0) ? 10 : $count;
    $offset = ($page - 1) * $count;
    $total_page = ceil($total / $count);

    $get_exchange_list = 'select * from '.$db->table($table);
    $get_exchange_list .= $where;
    $get_exchange_list .= $and_where;
    $get_exchange_list .= ' order by add_time desc';
    $get_exchange_list .= ' limit '.$offset.','.$count;

    $exchange_list = $db->fetchAll($get_exchange_list);

    if( $exchange_list ) {
        foreach( $exchange_list as $key => $value ) {
            $exchange_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $value['add_time']);
        }
    }
    assign('exchange_list', $exchange_list);

    assign('count', $count);
    assign('st', $st);
    assign('et', $et);

    create_pager($page, $total_page, $total);
}

$template .= $act.'.phtml';
$smarty->display($template);
