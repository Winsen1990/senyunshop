<?php
/**
 * 帖子管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'forum/';
assign('subTitle', '帖子管理');

$action = 'edit|add|view|delete|top';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

//添加帖子
if( 'add' == $opera ) {
    if( !check_purview('pur_forum_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $content = trim(getPOST('content'));
    $is_top = intval(getPOST('is_top'));
    $topic_id = intval(getPOST('topic_id'));

    if('' == $content) {
        show_system_message('内容不能为空', array());
        exit;
    } else {
        $content = $db->escape(htmlspecialchars($content));
    }

    $data = array(
        'account' => 'admin',
        'content' => $content,
        'topic_id' => $topic_id,
        'is_top' => $is_top,
        'add_time' => time()
    );

    if( $db->autoInsert('forum', array($data)) ) {
        $links = array(
            array('alt'=>'查看帖子', 'link'=>'forum.php?act=list'),
            array('alt'=>'继续添加帖子', 'link'=>'forum.php?act=add')
        );
        show_system_message('添加帖子成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }

}

//编辑帖子
if( 'edit' == $opera ) {

    if( !check_purview('pur_forum_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = getPOST('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_section = 'select * from `'.DB_PREFIX.'forum` where `id`='.$id.' limit 1';
    $section = $db->fetchRow($get_section);

    if( empty($section) ) {
        show_system_message('帖子不存在', array());
        exit;
    }

    $status = intval(getPOST('status'));
    $integral = floatval(getPOST('integral'));

    if($integral <= 0)
    {
        $integral = 0;
    }

    $data = array(
        'status' => $status,
        'integral' => $integral
    );

    $where = 'id = '.$id;
    $order = '';
    $limit = '1';

    $db->begin();
    $transaction = true;

    if( !$db->autoUpdate('forum', $data, $where, $order, $limit) ) {
        $transaction = false;
    } else {
        if($status == 1 && $integral > 0)
        {
            $get_account = 'select `account` from '.$db->table('forum').' where `id`='.$id;
            $account = $db->fetchOne($get_account);
            add_memeber_exchange_log($account, 0, 0, $integral, 0, 0, $_SESSION['account'], '分享帖子赠送积分');
        }
    }

    if( $transaction ) {
        $db->commit();
        $links = array('alt'=>'帖子列表', 'link'=>'forum.php');
        show_system_message('帖子审核成功', array($links));
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//===========================================================================

//帖子列表
if( 'view' == $act ) {

    if( !check_purview('pur_forum_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_total = 'select count(*) from '.$db->table('forum').' as a';
    $get_total .= ' left join '.$db->table('topic').' as s on a.topic_id = s.id';

    $page = getGET('page');
    $count = getGET('count');
    $keyword = getGET('keyword');

    $count_expected = array(10, 25, 50, 100);
    $page = intval($page);
    $count = intval($count);
    if( !in_array($count, $count_expected) ) {
        $count = 10;
    }
    $keyword = $db->escape(htmlspecialchars(trim($keyword)));
    if( !empty($keyword) ) {
        $orWhere = ' and (a.`content` like \'%'.$keyword.'%\') ';
    } else {
        $orWhere = '';
    }

    $total = $db->fetchOne($get_total.$orWhere);

    $total_page = ceil($total / $count);

    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( $page <= 0 ) ? 1 : $page;

    $offset = ($page - 1) * $count;

    create_pager($page, $total_page, $total);
    assign('count', $count);
    assign('keyword', $keyword);


    $get_content_list = 'select a.*, s.name as topic_name from '.$db->table('forum').' as a';
    $get_content_list .= ' left join '.$db->table('topic').' as s on s.id = a.topic_id';
    $get_content_list .= ' where 1 '.$orWhere;
    $get_content_list .= ' order by a.add_time desc';
    $get_content_list .= ' limit '.$offset.','.$count;

    $content_list = $db->fetchAll($get_content_list);

    if( $content_list ) {
        foreach ($content_list as $key => $content) {
            $content_list[$key]['add_time'] = date('Y-m-d H:i:s', $content['add_time']);
        }
    }

    assign('contentList', $content_list);
}

//添加帖子
if( 'add' == $act ) {

    if( !check_purview('pur_forum_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $get_topic_list = 'select * from '.$db->table('topic').' where 1';
    $topic_list = $db->fetchAll($get_topic_list);
    assign('topic_list', $topic_list);
}

//审核帖子
if( 'edit' == $act ) {
    if( !check_purview('pur_forum_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = getGET('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_section = 'select * from `'.DB_PREFIX.'forum` where `id`='.$id.' limit 1';
    $section = $db->fetchRow($get_section);

    if( empty($section) ) {
        show_system_message('帖子不存在', array());
        exit;
    }

    assign('section', $section);
}


//删除帖子
if( 'delete' == $act ) {
    if( !check_purview('pur_forum_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getGET('id');
    $id = intval($id);

    if(0 >= $id) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_content = 'select `id` from '.$db->table('forum_comment').' where topic_id = '.$id;
    $content = $db->fetchRow($get_content);
    if( $content ) {
        show_system_message('当前帖子下有回复，不能删除', array());
        exit;
    }

    $delete_section = 'delete from `'.DB_PREFIX.'forum` where `id`='.$id.' limit 1';
    if($db->delete($delete_section)) {
        show_system_message('删除帖子成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

if( 'top' == $act ) {
    if( !check_purview('pur_forum_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getGET('id');
    $id = intval($id);

    $delete_section = 'update `'.DB_PREFIX.'forum` set `is_top`=abs(1-`is_top`) where `id`='.$id.' limit 1';
    if($db->update($delete_section)) {
        show_system_message('修改帖子成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

$template .= $act.'.phtml';
$smarty->display($template);
