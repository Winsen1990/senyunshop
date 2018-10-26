<?php
/**
 * 内容管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-10
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $db, $log, $smarty, $config;
business_base_init();

$template = 'content/';
assign('subTitle', '内容管理');

$action = 'edit|add|view|delete|cycle|revoke|remove|empty|comment_view|comment_show|comment_delete|comment_review|comment_top';
$operation = 'edit|add|comment_reply';

$act = check_action($action, getGET('act'), 'view');

$opera = check_action($operation, getPOST('opera'));

//======================================================================
if('comment_reply' == $opera) {
    $response = ['error' => -1, 'message' => ''];

    if( !check_purview('pur_content_edit', $_SESSION['business_purview']) ) {
        throw new RestFulException('权限不足', 550);
    } else {
        $id = intval(getPOST('id'));
        $comment_id = intval(getPOST('comment_id'));
        $comment_content = trim(getPOST('comment_content'));

        if($id <= 0 || $comment_id <= 0) {
            throw new RestFulException('参数错误', 550);
        }

        $content = $db->find('content', ['id'], ['id' => $id]);

        if(empty($content)) {
            throw new RestFulException('资讯不存在', 403);
        }

        $comment = $db->find('content_comment', ['id', 'path'], ['id' => $comment_id]);
        if(empty($comment)) {
            throw new RestFulException('留言不存在', 403);
        }

        if(empty($comment_content)) {
            throw new RestFulException('回复内容不能为空', 403);
        }

        $comment_data = [
            'content_id' => $id,
            'comment' => $comment_content,
            'add_time' => time(),
            'account' => $_SESSION['business_account'],
            'status' => 1,
            'parent_id' => $comment_id
        ];

        if($db->create('content_comment', $comment_data)) {
            $comment_id = $db->get_last_id();

            $comment_assoc_data = [
                'path' => $comment['path'].$comment_id.','
            ];

            $db->upgrade('content_comment', $comment_assoc_data, ['id' => $comment_id]);

            $response['error'] = 0;
            $response['message'] = '回复成功';
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

//添加内容
if( 'add' == $opera ) {
    if( !check_purview('pur_content_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $title = trim(getPOST('title'));
    $author = trim(getPOST('author'));
    $section_id = trim(getPOST('articleCatId'));
    $keywords = trim(getPOST('keywords'));
    $description = trim(getPOST('description'));
    $content = trim(getPOST('content'));
    $wap_content = trim(getPOST('wap_content'));
    $publishTime = trim(getPOST('publishTime'));
    $isAutoPublish = trim(getPOST('isAutoPublish'));
    $add_time = time();
    $original_url = trim(getPOST('original-url'));
    $order_view = trim(getPOST('order-view'));

    $original = trim(getPOST('img'));

    if( '' != $original ) {
        $original = $db->escape(htmlspecialchars($original));
        if( file_exists('../'.$original) ) {
            $thumb = str_replace('image', 'thumb', $original);
        } else {
            $thumb = '';
        }
    } else {
        $thumb = '';
    }

    if( '' == $title ) {
        show_system_message('标题不能为空', array());
        exit;
    } else {
        $title = $db->escape(htmlspecialchars($title));
    }

    if( '' == $author ) {
        $author = $_SESSION['name'];
    } else {
        $author = $db->escape(htmlspecialchars($author));
    }

    $keywords = $db->escape(htmlspecialchars($keywords));
    $description = $db->escape(htmlspecialchars($description));

    if( '' == $section_id || 0 >= intval($section_id) ) {
        show_system_message('参数错误', array());
        exit;
    } else {
        $section_id = intval($section_id);
    }

    $isAutoPublish = intval($isAutoPublish);
    if('' == $isAutoPublish || 0 == intval($isAutoPublish) )
    {
        $isAutoPublish = 0;
        $publishTime = $add_time;
    } else {
        if(preg_match('^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)\ \d{1,2}:\d{1,2}:\d{1,2}$', $publishTime)) {
            $dateTime = explode(' ', $publishTime);
            $date = explode('-', $dateTime[0]);
            $time = explode(':', $dateTime[1]);

            $publishTime = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        } else {
            show_system_message('发布时间格式不正确', array());
            exit;
        }
    }

    if( '' != $original_url ) {
        $original_url = $db->escape($original_url);
    } else {
        $original_url = '';
    }

    $order_view = intval($order_view);
    if( 0 > $order_view ) {
        $order_view = 50;
    }

    $data = array(
        'title' => $title,
        'content' => $content,
        'wap_content' => $wap_content,
        'author' => $author,
        'add_time' => $add_time,
        'keywords' => $keywords,
        'description' => $description,
        'thumb' => $thumb,
        'original' => $original,
        'order_view' => $order_view,
        'original_url' => $original_url,
        'section_id' => $section_id,
    );
    if( $db->autoInsert('content', array($data)) ) {
        $links = array(
            array('alt'=>'返回列表', 'link'=>'content.php'),
            array('alt'=>'继续添加', 'link'=>'content.php?act=add'),
        );
        show_system_message('添加内容成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

if( 'edit' == $opera ) {
    if( !check_purview('pur_content_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getPOST('id');
    $id = intval($id);
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $title = trim(getPOST('title'));
    $author = trim(getPOST('author'));
    $section_id = trim(getPOST('articleCatId'));
    $keywords = trim(getPOST('keywords'));
    $description = trim(getPOST('description'));
    $content = trim(getPOST('content'));
    $wap_content = trim(getPOST('wap_content'));
    $publishTime = trim(getPOST('publishTime'));
    $isAutoPublish = trim(getPOST('isAutoPublish'));
    $original_url = trim(getPOST('original-url'));
    $order_view = trim(getPOST('order-view'));
    $add_time = '';

    $original = trim(getPOST('img'));

    if( '' != $original ) {
        $original = $db->escape(htmlspecialchars($original));
        if( file_exists('../'.$original) ) {
            $thumb = str_replace('image', 'thumb', $original);
        } else {
            $thumb = '';
        }
    } else {
        $thumb = '';
    }

    if( '' == $title ) {
        show_system_message('标题不能为空', array());
        exit;
    } else {
        $title = $db->escape(htmlspecialchars($title));
    }

    if( '' == $author ) {
        $author = $_SESSION['name'];
    } else {
        $author = $db->escape(htmlspecialchars($author));
    }

    $keywords = $db->escape(htmlspecialchars($keywords));
    $description = $db->escape(htmlspecialchars($description));

    if( '' == $section_id || 0 >= intval($section_id) ) {
        show_system_message('参数错误', array());
        exit;
    } else {
        $section_id = intval($section_id);
    }

    $isAutoPublish = intval($isAutoPublish);
    if('' == $isAutoPublish || 0 == intval($isAutoPublish) ) {
        $isAutoPublish = 0;
        $publishTime = $add_time;
    } else {
        if(preg_match('^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)\ \d{1,2}:\d{1,2}:\d{1,2}$', $publishTime)) {
            $dateTime = explode(' ', $publishTime);
            $date = explode('-', $dateTime[0]);
            $time = explode(':', $dateTime[1]);

            $publishTime = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        } else {
            show_system_message('发布时间格式不正确', array());
            exit;
        }
    }

    if( '' != $original_url ) {
        $original_url = $db->escape($original_url);
    } else {
        $original_url = '';
    }

    $order_view = intval($order_view);
    if( 0 > $order_view ) {
        $order_view = 50;
    }

    $data = array(
        'title' => $title,
        'wap_content' => $wap_content,
        'content' => $content,
        'author' => $author,
        'keywords' => $keywords,
        'description' => $description,
        'thumb' => $thumb,
        'original' => $original,
        'order_view' => $order_view,
        'original_url' => $original_url,
        'section_id' => $section_id,
    );

    if( intval($isAutoPublish) == 1 ) {
        $data['add_time'] = $publishTime;
    }

    $where = 'id = '.$id;
    $order = '';
    $limit = '1';

    if( $db->autoUpdate('content', $data, $where, $order, $limit) !== false ) {
        $links = array(
            array('alt'=>'返回列表', 'link'=>'content.php'),
            array('alt'=>'添加内容', 'link'=>'content.php?act=add'),
        );
        show_system_message('更新内容成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }


}


//======================================================================

//内容列表
if( 'view' == $act ) {
    if( !check_purview('pur_content_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_total = 'select count(*) from '.$db->table('content').' as a';
    $get_total .= ' left join '.$db->table('section').' as s on a.section_id = s.id';
    $get_total .= ' where a.status = 1';

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
        $orWhere = ' and (a.`title` like \'%'.$keyword.'%\' or a.author like \'%'.$keyword.'%\' or s.section_name like \'%'.$keyword.'%\')';
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


    $get_content_list = 'select a.*, s.section_name from '.$db->table('content').' as a';
    $get_content_list .= ' left join '.$db->table('section').' as s on s.id = a.section_id';
    $get_content_list .= ' where a.status = 1'.$orWhere;
    $get_content_list .= ' order by a.order_view asc, a.add_time desc';
    $get_content_list .= ' limit '.$offset.','.$count;

    $content_list = $db->fetchAll($get_content_list);

    if( $content_list ) {
        foreach ($content_list as $key => $content) {
            $content_list[$key]['add_time'] = date('Y-m-d H:i:s', $content['add_time']);
        }
    }

    assign('contentList', $content_list);

}

//添加内容
if( 'add' == $act ) {
    if( !check_purview('pur_content_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_section_list = 'select `section_name`,`id`,`path` from `'.DB_PREFIX.'section` order by `path`';
    $section_list = $db->fetchAll($get_section_list);
    if( empty($section_list) ) {
        show_system_message('系统尚未有栏目', array(array('alt' => '添加栏目', 'link' => 'section.php?act=add')));
        exit;
    }

    foreach( $section_list as $key => $section ) {
        $count = count(explode(',', $section['path']));

        if( $count > 1 ) {
            $temp = '|--';
            while( $count-- ) {
                $temp = '&nbsp;&nbsp;'.$temp;
            }

            $section['name'] = $temp.$section['section_name'];
        }

        $section_list[$key] = $section;
    }

    assign('defaultAuthor', $_SESSION['admin_name']);
    assign('sectionList', $section_list);

    $get_configs = 'select * from '.$db->table('sysconf').' where 1';
    $configs = $db->fetchAll($get_configs);

    foreach( $configs as $config ) {
        $target[$config['key']] = $config['value'];
    }

    assign('configs', $target);

}

//编辑内容
if( 'edit' == $act ) {

    if( !check_purview('pur_content_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getGET('id');
    $id = intval($id);
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_content = 'select * from `'.DB_PREFIX.'content` where `id`='.$id.' limit 1';
    $content = $db->fetchRow($get_content);

    if( empty($content) ) {
        show_system_message('参数错误', array());
        exit;
    }

    $content['publishTime'] = date('Y-m-d H:i:s', $content['add_time']);
    $content['isAutoPublish'] = $content['add_time'] > time() ? 1 : 0;

    assign('content', $content);

    $get_section_list = 'select `section_name`,`id`,`path` from `'.DB_PREFIX.'section` order by `path`';
    $section_list = $db->fetchAll($get_section_list);
    if( empty($section_list) ) {
        show_system_message('系统尚未有栏目', array(array('alt' => '添加栏目', 'link' => 'section.php?act=add')));
        exit;
    }

    foreach( $section_list as $key => $section ) {
        $count = count(explode(',', $section['path']));

        if( $count > 1 ) {
            $temp = '|--';
            while( $count-- ) {
                $temp = '&nbsp;&nbsp;'.$temp;
            }

            $section['name'] = $temp.$section['section_name'];
        }

        $section_list[$key] = $section;
    }

    assign('sectionList', $section_list);

    $get_configs = 'select * from '.$db->table('sysconf').' where 1';
    $configs = $db->fetchAll($get_configs);

    foreach( $configs as $config ) {
        $target[$config['key']] = $config['value'];
    }

    assign('configs', $target);
}

//删除内容
if( 'delete' == $act ) {
    if( !check_purview('pur_content_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $id = intval($id);
    $delete_content = 'update `'.DB_PREFIX.'content` set `status`=0 where `id`='.$id.' limit 1';

    if( $db->update($delete_content) ) {
        show_system_message('已放入回收站', array(array('alt'=>'返回列表', 'link'=>'content.php')));
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

//回收站列表
if( 'cycle' == $act ) {
    if( !check_purview('pur_content_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_total = 'select count(*) from '.$db->table('content').' as a';
    $get_total .= ' left join '.$db->table('section').' as s on a.section_id = s.id';
    $get_total .= ' where a.status = 0';

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
        $orWhere = ' and a.`title` like \'%'.$keyword.'%\' or a.author like \'%'.$keyword.'%\' or s.section_name like \'%'.$keyword.'%\'';
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


    $get_content_list = 'select a.*, s.section_name from '.$db->table('content').' as a';
    $get_content_list .= ' left join '.$db->table('section').' as s on s.id = a.section_id';
    $get_content_list .= ' where a.status = 0'.$orWhere;
    $get_content_list .= ' order by a.order_view asc, a.add_time desc';
    $get_content_list .= ' limit '.$offset.','.$count;
    $content_list = $db->fetchAll($get_content_list);

    if( $content_list ) {
        foreach( $content_list as $key => $content ) {
            $content_list[$key]['add_time'] = date('Y-m-d H:i:s', $content['add_time']);
        }
    }

    assign('contentList', $content_list);
}

//从回收站撤销
if( 'revoke' == $act ) {
    if( !check_purview('pur_content_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $id = intval($id);
    $revoke_content = 'update `'.DB_PREFIX.'content` set `status`=1 where `id`='.$id.' limit 1';

    if( $db->update($revoke_content) ) {
        $links = array(
            array('alt'=>'返回列表', 'link'=>'content.php'),
            array('alt'=>'回收站', 'link'=>'content.php?act=cycle'),
        );
        show_system_message('已撤销删除', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

//完全删除
if( 'remove' == $act ) {
    if( !check_purview('pur_content_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $id = intval($id);
    $delete_content = 'delete from `'.DB_PREFIX.'content` where `id`='.$id.' limit 1';
    if( $db->delete($delete_content) ) {
        $links = array(
            array('alt'=>'返回列表', 'link'=>'content.php'),
            array('alt'=>'回收站', 'link'=>'content.php?act=cycle'),
        );
        show_system_message('已彻底删除', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

//清空回收站
if( 'empty' == $act ) {
    if( !check_purview('pur_content_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $empty_trash = 'delete from '.$db->table('content').' where status = 0';
    if( $db->delete($empty_trash) ) {
        show_system_message('回收站已被清空', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

//评论列表
if('comment_view' == $act) {
    if( !check_purview('pur_content_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $content = $db->find('content', ['id'], ['id' => $id]);

    if(empty($content)) {
        show_system_message('资讯不存在');
    }

    //获取评论
    $get_comments = 'select c.`id`,c.`top`,c.`thumb_up`,c.`comment`,c.`parent_id`,m.`headimg`,m.`nickname`,m.`account`,c.`add_time`,c.`status` from '.
        $db->table('content_comment').' as c left join '.$db->table('member').' as m '.
        'using(`account`) where c.`content_id`='.$id.' order by c.`path` ASC,c.`top` DESC,c.`add_time` DESC';

    $comments = $db->fetchAll($get_comments);

    if($comments) {
        foreach($comments as &$_comment) {
            if($_comment['parent_id'] > 0) {
                $_comment['nickname'] = '管理员';
            }

            $_comment['add_time_str'] = date('Y-m-d H:i:s', $_comment['add_time']);
            switch($_comment['status']) {
                case 0:
                    $_comment['status_str'] = '待审核';
                    break;

                case 1:
                    $_comment['status_str'] = '审核通过';
                    break;

                case 2:
                    $_comment['status_str'] = '审核不通过';
                    break;
            }
        }
    }

    assign('comments', $comments);
}

//评论详情
if('comment_show' == $act) {
    if( !check_purview('pur_content_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $comment = $db->find('content_comment', ['id', 'comment', 'account', 'content_id'], ['id' => $id]);

    if(empty($comment)) {
        show_system_message('评论不存在');
    }

    assign('comment', $comment);
}

//评论置顶/取消置顶
if('comment_top' == $act) {
    if( !check_purview('pur_content_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $comment = $db->find('content_comment', ['id', 'content_id', 'top', 'status'], ['id' => $id, 'parent_id' => 0]);

    if(empty($comment)) {
        show_system_message('评论不存在');
    }

    if($comment['status'] == 0) {
        show_system_message('评论未审核，不能置顶');
    }

    if($comment['status'] == 2) {
        show_system_message('评论审核不通过，不能置顶');
    }

    $data = [
        'top' => 1
    ];

    if($comment['top'] == 1) {
        $data['top'] = 0;
    }

    if($db->upgrade('content_comment', $data, ['id' => $id])) {
        if($data['top']) {
            show_system_message('评论置顶成功');
        } else {
            show_system_message('评论已取消置顶');
        }
    } else {
        show_system_message('系统繁忙，请稍后再试');
    }
}

//评论审核
if('comment_review' == $act) {
    if( !check_purview('pur_content_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
    }

    $id = intval(getGET('id'));
    $status = intval(getGET('status'));
    $status = min(2, $status);
    $status = max(1, $status);

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $comment = $db->find('content_comment', ['id', 'content_id', 'status'], ['id' => $id, 'parent_id' => 0]);

    if(empty($comment)) {
        show_system_message('评论不存在');
    }

    if($comment['status'] != 0) {
        show_system_message('评论已审核过，不能重复操作');
    }

    if($db->upgrade('content_comment', ['status' => $status], ['id' => $id])) {
        if($status == 1) {
            //审核通过
            $db->upgrade('content', ['comment_count' => ['exp', '`comment_count`+1']], ['id' => $comment['content_id']]);
        }

        show_system_message('审核成功');
    } else {
        show_system_message('系统繁忙，请稍后再试');
    }
}

//评论删除
if('comment_delete' == $act) {
    if( !check_purview('pur_content_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $comment = $db->find('content_comment', ['id', 'content_id', 'status'], ['id' => $id]);

    if(empty($comment)) {
        show_system_message('评论不存在');
    }

    if($db->destroy('content_comment', ['id' => $id]) !== false) {
        $db->destroy('content_comment', ['parent_id' => $id]);
        $db->destroy('content_comment_up', ['content_comment_id' => $id]);

        if($comment['status'] == 1) {
            $db->upgrade('content', ['comment_count' => ['exp', '`comment_count`-1']], ['id' => $comment['content_id']]);
        }

        show_system_message('删除评论成功');
    } else {
        show_system_message('删除评论失败');
    }
}

$template .= $act.'.phtml';
$smarty->display($template);


