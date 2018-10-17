<?php
/**
 * 评价管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-24
 * @version 1.0.0
 */

include 'library/init.inc.php';

//商户管理后台初始化
business_base_init();
$template = 'eval/';

$action = 'view|response|delete';
$operation = 'response';
$act = check_action($action, getGET('act'));
$opera = check_action($operation, getPOST('opera'));
$act = ( $act == '' ) ? 'view' : $act;
//===============================================================================

if( 'response' == $opera ) {
    if( !check_purview('pur_eval_response', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit();
    }

    $get_comment = 'select a.* from '.$db->table('comment').' as a';
    $get_comment .= ' left join '.$db->table('product').' as b on a.product_sn = b.product_sn';
    $get_comment .= ' where b.business_account = \''.$_SESSION['business_account'].'\'';
    $get_comment .= ' and a.id = \''.$id.'\' and a.parent_id = 0 limit 1';
    $comment = $db->fetchRow($get_comment);
    if( !$comment ) {
        show_system_message('评论不存在', array());
        exit();
    }
    $response = trim(getPOST('response'));
    if( '' == $response ) {
        show_system_message('回复不能为空', array());
        exit();
    }
    $response = $db->escape($response);

    $check_response = 'select * from '.$db->table('comment');
    $check_response .= ' where parent_id = '.$id.' limit 1';
    $response_exists = $db->fetchRow($check_response);
    if( $response_exists ) {    //更新

        $data = array(
            'comment' => $response,
        );
        $where = 'id = '.$response_exists['id'];
        if( $db->autoUpdate('comment', $data, $where) ) {
            show_system_message('更新回复成功', array());
            exit();
        } else {
            show_system_message('系统繁忙，请稍后重试', array());
            exit();
        }


    } else {    //添加
        $data = array(
            'add_time' => time(),
            'comment' => $response,
            'product_sn' => $comment['product_sn'],
            'account' => $_SESSION['business_account'],
            'parent_id' => $id,
        );

        if( $db->autoInsert('comment', array($data)) ) {
            $id = $db->get_last_id();
            $path = $comment['path'].$id.',';
            $update_comment = 'update '.$db->table('comment').' set path = \''.$path.'\' where id = '.$id.' limit 1';
            if( !$db->update($update_comment) ) {
                show_system_message('添加回复失败', array());
                exit();
            }
            $links = array(
                array('alt' => '评价列表', 'link' => 'eval.php'),
            );
            show_system_message('回复成功', $links);
            exit();
        } else {
            show_system_message('系统繁忙，请稍后重试', array());
            exit();
        }
    }



}

//===============================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_eval_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $group = trim(getGET('group'));
    $order = intval(getGET('order'));

    $order = ( $order == 0 ) ? 'asc' : 'desc';
    $order_num = ( $order == 0 ) ? 0 : 1;

    switch($group) {
        case 'time':
            $order_by = ' order by add_time '.$order;
            assign('group_str', 'time');
            break;
        case 'star':
            $order_by = ' order by star '.$order;
            assign('group_str', 'star');
            break;
        default:
            $order_by = ' order by add_time '.$order;
            assign('group_str', 'time');
            break;
    }

    assign('group', $group);
    assign('order', $order);
    assign('order_num', $order_num);


    //分页参数
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    //获取总数
    $get_total = 'select count(*) from '.$db->table('comment').' as a';
    $get_total .= ' left join '.$db->table('product').' as b on a.product_sn = b.product_sn';
    $get_total .= ' where a.parent_id = 0 and b.business_account = \''.$_SESSION['business_account'].'\'';
    $total = $db->fetchOne($get_total);

    $page = ( $page > $total ) ? $total : $page;
    $page = ( $page <= 0 ) ? 1 : $page;
    $count = ( $count <= 0 ) ? 10 : $count;
    $offset = ( $page - 1 ) * $count;
    $total_page = ceil( $total / $count );

    create_pager($page, $total_page, $total);
    assign('count', $count);

    $get_comment_list = 'select a.*,d.comment as response from '.$db->table('comment').'as a';
    $get_comment_list .= ' left join '.$db->table('product').' as b on a.product_sn = b.product_sn';
    $get_comment_list .= ' left join '.$db->table('comment').' as d on a.id = d.parent_id';
    $get_comment_list .= ' where a.parent_id = 0 and b.business_account = \''.$_SESSION['business_account'].'\'';
    $get_comment_list .= $order_by;
    $get_comment_list .= ' limit '.$offset.','.$count;
    $comment_list = $db->fetchAll($get_comment_list);
    if( $comment_list ) {
        foreach( $comment_list as $key => $comment ) {
            $comment_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $comment['add_time']);
            $comment_list[$key]['responsed'] = ( $comment['response']) ? '是' : '否';
        }
    }
    assign('comment_list', $comment_list);
}

if( 'response' == $act ) {
    if( !check_purview('pur_eval_response', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit();
    }

    $get_comment = 'select a.*,d.comment as response from '.$db->table('comment').' as a';
    $get_comment .= ' left join '.$db->table('product').' as b on a.product_sn = b.product_sn';
    $get_comment .= ' left join '.$db->table('comment').' as d on a.id = d.parent_id';
    $get_comment .= ' where b.business_account = \''.$_SESSION['business_account'].'\'';
    $get_comment .= ' and a.id = \''.$id.'\' and a.parent_id = 0 limit 1';
    $comment = $db->fetchRow($get_comment);
    if( !$comment ) {
        show_system_message('评论不存在', array());
        exit();
    }

    assign('comment', $comment);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_eval_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误');
    }

    $get_comment = 'select * from '.$db->table('comment').' as a';
    $get_comment .= ' left join '.$db->table('product').' as b on a.product_sn = b.product_sn';
    $get_comment .= ' where a.id = \''.$id.'\' and b.business_account = \''.$_SESSION['business_account'].'\' limit 1';

    $comment = $db->fetchRow($get_comment);
    if( empty($comment) ) {
        show_system_message('评论不存在');
    }

    $delete_comment = 'delete from '.$db->table('comment').' where id = \''.$id.'\' limit 1';
    if( $db->delete($delete_comment) ) {
        $delete_response = 'delete from '.$db->table('comment').' where parent_id = \''.$id.'\'';
        $db->delete($delete_response);
        show_system_message('评论已删除');
    } else {
        show_system_message('系统繁忙，请稍后重试');
    }
}

$template .= $act.'.phtml';
$smarty->display($template);

