<?php
/**
 * 商户管理后台，财务管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-21
 * @version 1.0.0
 */

include 'library/init.inc.php';

//商户管理后台初始化
business_base_init();
$template = 'finance/';

$action = 'view|record|withdraw|trade';
$operation = 'withdraw';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

//============================================================

if( 'withdraw' == $opera ) {
    if( !check_purview('pur_finance_withdraw', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $bank = trim(getPOST('bank'));
    $bank_account = trim(getPOST('bank_account'));
    $bank_card = trim(getPOST('bank_card'));
    $amount = floatval(getPOST('amount'));
    $remark = trim(getPOST('remark'));
    $add_time = time();
    $withdraw_sn = 'W'.time().rand(100, 999);
    $fee = 0.00;

    if( '' == $bank ) {
        show_system_message('银行不能为空', array());
        exit;
    }
    $bank = $db->escape($bank);

    if( '' == $bank_account ) {
        show_system_message('收款人不能为空', array());
        exit;
    }
    $bank_account = $db->escape($bank_account);

    if( '' == $bank_card ) {
        show_system_message('银行卡号不能为空', array());
        exit;
    }
    if( !luhm_check($bank_card) ) {
        show_system_message('请填写正确的银行卡号', array());
        exit;
    }
    $bank_card = $db->escape($bank_card);

    if( 0 >= $amount ) {
        show_system_message('请填写正确的金额', array());
        exit;
    }

    $remark = $db->escape($remark);

    $fee = $amount*$config['fee_rate'];
    //余额是否足够提现
    $get_business = 'select * from '.$db->table('business');
    $get_business .= ' where business_account = \''.$_SESSION['business_account'].'\' limit 1';
    $business = $db->fetchRow($get_business);
    $business['balance'] = floatval($business['balance']);
    if( $business['balance'] < ($amount + $fee) ) {
        show_system_message('余额不足', array());
        exit;
    }
    //事物开始
    $db->begin();
    $transaction = true;

    $data = array(
        'withdraw_sn' => $withdraw_sn,
        'bank' => $bank,
        'bank_account' => $bank_account,
        'bank_card' => $bank_card,
        'amount' => $amount,
        'fee' => $fee,
        'add_time' => $add_time,
        'remark' => $remark,
        'business_account' => $_SESSION['business_account'],
    );
    $table = 'business_withdraw';
    if( !$db->autoInsert($table, array($data))) {
        $transaction = false;
    }

    $update_business = 'update '.$db->table('business').' set';
    $update_business .= ' `balance` = `balance` - '.($amount + $fee);
    $update_business .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $update_business .= ' limit 1';
    if( !$db->update($update_business) ) {
        $transaction = false;
    }


    if( $transaction ) {

        $db->commit();

        $data = array(
            'add_time' => $add_time,
            'operator' => $_SESSION['business_admin'],
            'withdraw_sn' => $withdraw_sn,
            'status' => 0,
            'remark' => '申请提现',
        );
        $table = 'business_withdraw_log';
        $db->autoInsert($table, array($data));

        $data = array(
            'business_account' => $_SESSION['business_account'],
            'balance' => -1*($amount + $fee),
            'trade' => 0,
            'add_time' => time(),
            'remark' => '冻结提现资金:'.($amount + $fee),
            'operator' => $_SESSION['business_admin'],
        );
        $table= 'business_exchange_log';
        $db->autoInsert($table, array($data));

        show_system_message('您的提现申请已提交', array());
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//============================================================

if( 'view' == $act ) {
    if( !check_purview('pur_finance_view', $_SESSION['business_purview']) ) {
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
    $get_total = 'select count(*) from '.$db->table('business_exchange_log');
    $get_total .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    $get_business = 'select `balance`, `trade` from '.$db->table('business');
    $get_business .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_business .= $and_where;
    $get_business .= ' limit 1';
    $business = $db->fetchRow($get_business);
    assign('business', $business);

    $get_exchange_list = 'select * from '.$db->table('business_exchange_log');
    $get_exchange_list .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_exchange_list .= $and_where;
    $get_exchange_list .= ' order by id desc';
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

if( 'trade' == $act ) {
    if( !check_purview('pur_finance_trade', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

}


if( 'withdraw' == $act ) {
    if( !check_purview('pur_finance_withdraw', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $get_business = 'select `balance`, `trade` from '.$db->table('business');
    $get_business .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_business .= ' limit 1';
    $business = $db->fetchRow($get_business);
    assign('business', $business);

}

if( 'record' == $act ) {
    if( !check_purview('pur_finance_withdraw', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_business = 'select * from '.$db->table('business');
    $get_business .= ' where business_account = \''.$_SESSION['business_account'].'\' limit 1';
    $business = $db->fetchRow($get_business);
    assign('business', $business);

    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    $getTotal = 'select count(*) from '.$db->table('business_withdraw');
    $getTotal .= ' where account = \''.$_SESSION['business_account'].'\'';
    $total = $db->fetchOne($getTotal);

    $count = intval(getGET('count'));
    $count = ( $count <= 0 ) ? 10 : $count;

    $total_page = ceil($total / $count);
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;
    $offset = ( $page - 1 ) * $count;

    $get_withdraw_list = 'select * from '.$db->table('business_withdraw');
    $get_withdraw_list .= ' where `business_account` = \''.$_SESSION['business_account'].'\'';
    $get_withdraw_list .= ' order by add_time desc';
    $get_withdraw_list .= ' limit '.$offset.','.$count;
    $withdraw_list = $db->fetchAll($get_withdraw_list);

    if( $withdraw_list ) {
        foreach( $withdraw_list as $key => $withdraw ) {
            $withdraw_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $withdraw['add_time']);
            $withdraw_list[$key]['status_str'] = ( $withdraw['status'] == 1 ) ? '是' : '否';
        }
    }

    assign('withdraw_list', $withdraw_list);

    create_pager($page, $total_page, $total);
    assign('count', $count);
}

$template .= $act.'.phtml';
$smarty->display($template);
