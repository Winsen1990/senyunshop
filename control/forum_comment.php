<?php
/**
 * 评论管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'forum_comment/';
assign('subTitle', '评论管理');

$action = 'edit|add|view|delete|top';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

//添加评论
if( 'add' == $opera ) {
    if( !check_purview('pur_forum_comment_add', $_SESSION['purview']) ) {
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
            array('alt'=>'查看评论', 'link'=>'forum.php?act=list'),
            array('alt'=>'继续添加评论', 'link'=>'forum.php?act=add')
        );
        show_system_message('添加评论成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }

}

//编辑评论
if( 'edit' == $opera ) {

    if( !check_purview('pur_forum_comment_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = getPOST('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_section = 'select * from `'.DB_PREFIX.'forum_comment` where `id`='.$id.' limit 1';
    $section = $db->fetchRow($get_section);

    if( empty($section) ) {
        show_system_message('评论不存在', array());
        exit;
    }

    $status = intval(getPOST('status'));
    $integral = floatval(getPOST('integral'));

    if($integral <= 0)
    {
        $integral = 0;
    }

    $data = array(
        'status' => $status
    );

    $where = 'id = '.$id;
    $order = '';
    $limit = '1';

    $db->begin();
    $transaction = true;

    if( !$db->autoUpdate('forum_comment', $data, $where, $order, $limit) ) {
        $transaction = false;
    }
    
    if( $transaction ) {
        $db->commit();
        $link = array('alt'=>'评论列表', 'link'=>'forum_comment.php');
        show_system_message('评论审核成功', array($link));
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//===========================================================================

//评论列表
if( 'view' == $act ) {

    if( !check_purview('pur_forum_comment_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_total = 'select count(*) from '.$db->table('forum_comment').' as a where 1 ';

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
        $orWhere = ' and (a.`comment` like \'%'.$keyword.'%\') ';
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


    $get_content_list = 'select a.* from '.$db->table('forum_comment').' as a';
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

//审核评论
if( 'edit' == $act ) {
    if( !check_purview('pur_forum_comment_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = getGET('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_section = 'select * from `'.DB_PREFIX.'forum_comment` where `id`='.$id.' limit 1';
    $section = $db->fetchRow($get_section);

    if( empty($section) ) {
        show_system_message('评论不存在', array());
        exit;
    }

    assign('section', $section);
}


//删除评论
if( 'delete' == $act ) {
    if( !check_purview('pur_forum_comment_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getGET('id');
    $id = intval($id);

    if(0 >= $id) {
        show_system_message('参数错误', array());
        exit;
    }

    $delete_section = 'delete from `'.DB_PREFIX.'forum_comment` where `id`='.$id.' limit 1';
    if($db->delete($delete_section)) {
        show_system_message('删除评论成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

$template .= $act.'.phtml';
$smarty->display($template);
