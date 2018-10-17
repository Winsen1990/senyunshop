<?php
/**
 * 充值管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-10-16
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'recharge/';
assign('subTitle', '充值管理');

$action = 'view|edit|log';
$operation = '';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

$status_str = array(
    0 => '待支付',
    1 => '已到帐',
    2 => '未到帐',
    3 => '已取消',
);

$type_str = array(
    0 => '线上',
    1 => '线下',
);


//===========================================================================


//===========================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_recharge_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $table = 'recharge';

    $type = intval(getGET('type'));
    $and_where = ( 0 == $type ) ? ' and type = 0' : ' and type = 1';

    $status = intval(getGET('status'));
    $status = ( 0 > $status || 3 < $status ) ? 0 : $status;
    if( 0 == $status ) {
        $and_where .= '';
    } else {
        $and_where .= ' and status = '.$status;
    }

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);
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
    $get_total = 'select count(*) from '.$db->table($table);
    $get_total .= ' where 1';
    $get_total .= $and_where;
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    $get_recharge_list = 'select `recharge_sn`, `account`, `add_time`, `amount`, `status`, `type`, `bank`, `card_num`, `solve_time`';
    $get_recharge_list .= ' from '.$db->table($table);
    $get_recharge_list .= ' where 1';
    $get_recharge_list .= $and_where;
    $get_recharge_list .= ' order by add_time desc';
    $get_recharge_list .= ' limit '.$offset.','.$count;

    $recharge_list = $db->fetchAll($get_recharge_list);
    if( $recharge_list ) {
        foreach( $recharge_list as $key => $recharge ) {
            $recharge_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $recharge['add_time']);
            $recharge_list[$key]['solve_time_str'] = intval($recharge['solve_time']) ? date('Y-m-d H:i:s', $recharge['solve_time']) : '';
            $recharge_list[$key]['status_str'] = $status_str[$recharge['status']];
        }
    }
    assign('recharge_list', $recharge_list);
    create_pager($page, $total_page, $total);

    assign('type', $type);
    assign('status', $status);
    assign('count', $count);
    assign('st', $st);
    assign('et', $et);
}


if( 'edit' == $act ) {
    if( !check_purview('pur_recharge_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $table = 'recharge';
    $sn = trim(getGET('sn'));
    if( '' == $sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $sn = $db->escape($sn);

    $get_recharge = 'select * from '.$db->table($table).' where recharge_sn = \''.$sn.'\' and `type` = 1 limit 1';
    $recharge = $db->fetchRow($get_recharge);
    if( empty($recharge) ) {
        show_system_message('充值记录不存在', array());
        exit;
    }
    if( $recharge['status'] == 1 ) {
        show_system_message('该笔充值已到帐', array());
        exit;
    }

    if( $recharge['status'] == 3 ) {
        show_system_message('该笔充值已取消', array());
        exit;
    }

    $update_recharge = 'update '.$db->table($table).' set status = 1, solve_time = '.time().'  where recharge_sn = \''.$sn.'\' limit 1';
    if( $db->update($update_recharge) ) {


        //添加充值明细
        $data = array(
            'add_time' => time(),
            'operator' => $_SESSION['account'],
            'recharge_sn' => $sn,
            'status' => 1,
            'remark' => '线下充值到帐，金额：'.$recharge['amount'],
        );
        $db->autoInsert($table.'_log', array($data));

        //添加账户明细记录
        add_memeber_exchange_log($recharge['account'], $recharge['amount'], 0, 0, 0, 0, $_SESSION['account'], '线下充值到帐');
        show_system_message('操作成功', array());
        exit;

    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'log' == $act ) {
    if( !check_purview('pur_recharge_log', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $table = 'recharge_log';

    $st = trim(getGET('st'));
    $et = trim(getGET('et'));
    $start_time = strtotime($st);
    $end_time = strtotime($et);
    $and_where = '';
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
    $get_total = 'select count(*) from '.$db->table($table);
    $get_total .= ' where 1';
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    $get_log_list = 'select * from '.$db->table($table);
    $get_log_list .= ' where 1';
    $get_log_list .= $and_where;
    $get_log_list .= ' order by add_time desc';
    $get_log_list .= ' limit '.$offset.','.$count;

    $log_list = $db->fetchAll($get_log_list);
    if( $log_list ) {
        foreach( $log_list as $key => $value ) {
            $log_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $value['add_time']);
            $log_list[$key]['status_str'] = ( $value['status'] == 0 ) ? '否' : '是';
        }
    }
    assign('log_list', $log_list);

    assign('count', $count);
    assign('st', $st);
    assign('et', $et);
    assign('type', '');

    create_pager($page, $total_page, $total);

}

$template .= $act.'.phtml';
$smarty->display($template);
