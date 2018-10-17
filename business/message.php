<?php
/**
 * 系统消息
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-22
 * @version 1.0.0
 */

include 'library/init.inc.php';
business_base_init();

$template = 'message/';
assign('subTitle', '系统消息');

$action = 'view|read';
$operation = '';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

//===========================================================================

if( 'view' == $act ) {

    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('message');
    $get_total .= ' where business_account = \''.$_SESSION['business_account'].'\' and status = 0';
    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);

    $offset = ($page - 1) * $count;

    $get_message_list = 'select * from '.$db->table('message');
    $get_message_list .= ' where business_account = \''.$_SESSION['business_account'].'\' and status = 0';
    $get_message_list .= ' order by add_time desc';
    $get_message_list .= ' limit '.$offset.','.$count;
    $message_list = $db->fetchAll($get_message_list);

    if( $message_list ) {
        foreach( $message_list as $key => $value ) {
            $message_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $value['add_time']);
            //置为已读
            $update_message = 'update '.$db->table('message').' set status = 1';
            $update_message .= ' where business_account = \''.$_SESSION['business_account'].'\' and status = 0 and id = '.$value['id'].' limit 1';
            $db->update($update_message);
        }
    }
    assign('message_list', $message_list);
}

if( 'read' == $act ) {
    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('message');
    $get_total .= ' where business_account = \''.$_SESSION['business_account'].'\' and status = 1';
    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);

    $offset = ($page - 1) * $count;

    $get_message_list = 'select * from '.$db->table('message');
    $get_message_list .= ' where business_account = \''.$_SESSION['business_account'].'\' and status = 1';
    $get_message_list .= ' order by add_time desc';
    $get_message_list .= ' limit '.$offset.','.$count;
    $message_list = $db->fetchAll($get_message_list);

    if( $message_list ) {
        foreach( $message_list as $key => $value ) {
            $message_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $value['add_time']);
        }
    }
    assign('message_list', $message_list);
}

$template .= $act.'.phtml';
$smarty->display($template);

