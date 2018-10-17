<?php
/**
 * 提现管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-10
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'withdraw/';
assign('subTitle', '提现管理');

$action = 'view|edit|delete|log|reject';
$operation = 'delete';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================
if( 'delete' == $opera ) {
    if( !check_purview('pur_withdraw_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $content = trim(getPOST('content'));
    if($content == '')
    {
        show_system_message('驳回理由不能为空');
        exit;
    }

    $type = intval(getPOST('type'));
    if( $type == 0 ) {
        $table = 'withdraw';
        $exchange_table = 'member_exchange_log';
    } else {
        $table = 'business_withdraw';
        $exchange_table = 'business_exchange_log';
    }

    $sn = trim(getPOST('sn'));
    if( '' == $sn ) {
        show_system_message('参数错误', array());
        exit;
    }

    $sn = $db->escape($sn);

    $get_withdraw = 'select * from '.$db->table($table).' where withdraw_sn = \''.$sn.'\' limit 1';
    $withdraw = $db->fetchRow($get_withdraw);
    if( empty($withdraw) ) {
        show_system_message('提现记录不存在', array());
        exit;
    }

    if( $withdraw['status'] == 1 ) {
        show_system_message('提现已到帐，无法驳回', array());
        exit;
    }

    $db->begin();
    $transaction = true;

    $delete_withdraw = 'update '.$db->table($table).' set `status`=2 where withdraw_sn = \''.$sn.'\' limit 1';
    if( !$db->update($delete_withdraw) ) {
        $transaction = false;
    }

    if( $withdraw['status'] == 0 ) {
        if( $type == 0 ) {
            $update_member = 'update ' . $db->table('member') . ' set';
            $update_member .= ' `reward` = `reward` + ' . ($withdraw['amount'] + $withdraw['fee']);
            $update_member .= ' where account = \'' . $withdraw['account'] . '\'';
            $update_member .= ' limit 1';
            if (!$db->update($update_member)) {
                $transaction = false;
            }
        } else {
            $update_business = 'update ' . $db->table('business') . ' set';
            $update_business .= ' `reward` = `reward` + ' . ($withdraw['amount'] + $withdraw['fee']);
            $update_business .= ' where business_account = \'' . $withdraw['business_account'] . '\'';
            $update_business .= ' limit 1';
            if (!$db->update($update_business)) {
                $transaction = false;
            }
        }
    }

    if( $transaction ) {
        $db->commit();
        $data = array(
            'add_time' => time(),
            'operator' => $_SESSION['admin_account'],
            'withdraw_sn' => $sn,
            'status' => $withdraw['status'],
            'remark' => '驳回提现申请记录，金额：'.$withdraw['amount'],
        );
        $db->autoInsert($table.'_log', array($data));
        //添加账户明细记录
        $get_newest_log = 'select * from '.$db->table($exchange_table);
        $get_newest_log .= $type == 0 ? ' where account = \''.$withdraw['account'].'\'' : ' where business_account = \''.$withdraw['business_account'].'\'';
        $get_newest_log .= ' order by add_time desc limit 1';
        $newest_log = $db->fetchRow($get_newest_log);

        unset($newest_log['id']);
        $newest_log['add_time'] = time();
        $newest_log['balance'] = $withdraw['amount'] + $withdraw['fee'];
        $newest_log['remark'] = '取消提现，返还金额：'.$withdraw['amount'] + $withdraw['fee'];
        $newest_log['operator'] = $_SESSION['account'];

        $db->autoInsert($exchange_table, array($newest_log));
        //添加系统信息
        $message_data = array(
            'account' => $withdraw['business_account'],
            'business_account' => $withdraw['business_account'],
            'title' => '提现驳回',
            'content' => $content,
            'status' => 0,
            'add_time' => time()
        );

        $db->autoInsert('message', array($message_data));

        show_system_message('操作成功', array(array('link'=>'withdraw.php', 'alt'=>'提现管理')));
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}
//===========================================================================
if( 'delete' == $act ) {
    if( !check_purview('pur_withdraw_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $type = intval(getGET('type'));
    if( $type == 0 ) {
        $table = 'withdraw';
        $exchange_table = 'member_exchange_log';
    } else {
        $table = 'business_withdraw';
        $exchange_table = 'business_exchange_log';
    }

    $sn = trim(getGET('sn'));
    if( '' == $sn ) {
        show_system_message('参数错误', array());
        exit;
    }

    $sn = $db->escape($sn);

    $get_withdraw = 'select * from '.$db->table($table).' where withdraw_sn = \''.$sn.'\' limit 1';
    $withdraw = $db->fetchRow($get_withdraw);
    if( empty($withdraw) ) {
        show_system_message('提现记录不存在', array());
        exit;
    }

    if( $withdraw['status'] == 1 ) {
        show_system_message('提现已到帐，无法删除', array());
        exit;
    }

    $db->begin();
    $transaction = true;

    $delete_withdraw = 'delete from '.$db->table($table).' where withdraw_sn = \''.$sn.'\' limit 1';
    if( !$db->delete($delete_withdraw) ) {
        $transaction = false;
    }

    if( $transaction ) {
        $db->commit();
        //添加系统信息
        $message_data = array(
            'account' => $withdraw['business_account'],
            'business_account' => $withdraw['business_account'],
            'title' => '提现删除',
            'content' => $content,
            'status' => 0,
            'add_time' => time()
        );

        $db->autoInsert('message', array($message_data));

        show_system_message('操作成功', array());
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if('reject' == $act)
{
    if( !check_purview('pur_withdraw_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $type = intval(getGET('type'));
    if( $type == 0 ) {
        $table = 'withdraw';
        $exchange_table = 'member_exchange_log';
    } else {
        $table = 'business_withdraw';
        $exchange_table = 'business_exchange_log';
    }
    assign('type', $type);

    $sn = trim(getGET('sn'));
    if( '' == $sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    assign('sn', $sn);

    $sn = $db->escape($sn);

    $get_withdraw = 'select * from '.$db->table($table).' where withdraw_sn = \''.$sn.'\' limit 1';
    $withdraw = $db->fetchRow($get_withdraw);
    if( empty($withdraw) ) {
        show_system_message('提现记录不存在', array());
        exit;
    }
}

if( 'view' == $act ) {
    if( !check_purview('pur_withdraw_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $type = intval(getGET('type'));
    if( $type == 0 ) {
        $table = 'withdraw';
    } else {
        $table = 'business_withdraw';
    }
    assign('type', $type);

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

    $get_withdraw_list = 'select * from '.$db->table($table);
    $get_withdraw_list .= ' where 1 ';
    $get_withdraw_list .= $and_where;
    $get_withdraw_list .= ' order by add_time desc';
    $get_withdraw_list .= ' limit '.$offset.','.$count;

    $withdraw_list = $db->fetchAll($get_withdraw_list);

    if( $withdraw_list ) {
        foreach( $withdraw_list as $key => $value ) {
            $withdraw_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $value['add_time']);
            $withdraw_list[$key]['solve_time_str'] = date('Y-m-d H:i:s', $value['solve_time']);
            if($value['status'] <= 1) {
                $withdraw_list[$key]['status_str'] = ($value['status'] == 0) ? '否' : '是';
            } else {
                $withdraw_list[$key]['status_str'] = '申请驳回';
            }
        }
    }
    assign('withdraw_list', $withdraw_list);

    assign('count', $count);
    assign('st', $st);
    assign('et', $et);

    create_pager($page, $total_page, $total);
}

if( 'edit' == $act ) {
    if( !check_purview('pur_withdraw_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $type = intval(getGET('type'));
    if( $type == 0 ) {
        $table = 'withdraw';
        $exchange_table = 'member_exchange_log';
    } else {
        $table = 'business_withdraw';
        $exchange_table = 'business_exchange_log';
    }
    assign('type', $type);


    $sn = trim(getGET('sn'));
    if( '' == $sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $sn = $db->escape($sn);

    $get_withdraw = 'select * from '.$db->table($table).' where withdraw_sn = \''.$sn.'\' limit 1';
    $withdraw = $db->fetchRow($get_withdraw);
    if( empty($withdraw) ) {
        show_system_message('提现记录不存在', array());
        exit;
    }
    if( $withdraw['status'] == 1 ) {
        show_system_message('该笔提现已到帐', array());
        exit;
    }

    $update_withdraw = 'update '.$db->table($table).' set status = 1, solve_time = '.time().'  where withdraw_sn = \''.$sn.'\' limit 1';
    if( $db->update($update_withdraw) ) {

        $data = array(
            'add_time' => time(),
            'operator' => $_SESSION['account'],
            'withdraw_sn' => $sn,
            'status' => 1,
            'remark' => '金额到帐',
        );
        $db->autoInsert($table.'_log', array($data));

        //添加账户明细记录
        add_memeber_exchange_log($withdraw['account'], 0, -1*($withdraw['amount'] + $withdraw['fee']), 0, 0, 0, $_SESSION['account'], '提现到帐');

        show_system_message('操作成功', array());
        exit;

    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'log' == $act ) {
    if( !check_purview('pur_withdraw_log', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $type = intval(getGET('type'));
    if( $type == 0 ) {
        $table = 'withdraw_log';
    } else {
        $table = 'business_withdraw_log';
    }
    assign('type', $type);

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

    create_pager($page, $total_page, $total);
}

$template .= $act.'.phtml';
$smarty->display($template);