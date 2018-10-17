<?php
/**
 * 板块管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'topic/';
assign('subTitle', '板块管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

//添加板块
if( 'add' == $opera ) {
    if( !check_purview('pur_topic_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $name = trim(getPOST('articleCatName'));
    $order_view = intval(getPOST('order_view'));
    $original = trim(getPOST('img'));
    $thumb = '';

    if('' == $name) {
        show_system_message('板块名称不能为空', array());
        exit;
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

    if( '' == $original ) {

    } else {
        $original = $db->escape(htmlspecialchars($original));
        if( file_exists('../'.$original) ) {
            $thumb = str_replace('image', 'thumb', $original);
        } else {
            $thumb = '';
        }
    }

    $data = array(
        'name' => $name,
        'order_view' => $order_view,
        'img' => $original,
    );

    if( $db->autoInsert('topic', array($data)) ) {
        $links = array(
            array('alt'=>'查看板块', 'link'=>'topic.php?act=list'),
            array('alt'=>'继续添加板块', 'link'=>'topic.php?act=add')
        );
        show_system_message('添加板块成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }

}

//编辑板块
if( 'edit' == $opera ) {

    if( !check_purview('pur_topic_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = getPOST('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_section = 'select * from `'.DB_PREFIX.'topic` where `id`='.$id.' limit 1';
    $section = $db->fetchRow($get_section);

    if( empty($section) ) {
        show_system_message('板块不存在', array());
        exit;
    }

    $name = trim(getPOST('articleCatName'));
    $order_view = intval(getPOST('order_view'));
    $original = trim(getPOST('img'));
    $thumb = '';


    if('' == $name) {
        show_system_message('板块名称不能为空', array());
        exit;
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

    if( '' == $original ) {

    } else {
        $original = $db->escape(htmlspecialchars($original));
        if( file_exists('../'.$original) ) {
            $thumb = str_replace('image', 'thumb', $original);
        } else {
            $thumb = '';
        }

    }

    $data = array(
        'name' => $name,
        'order_view' => $order_view,
        'img' => $original,
    );

    foreach( $data as $key => $value ) {
        if( $value == '' ) {
            unset($data[$key]);
        }
    }

    $where = 'id = '.$id;
    $order = '';
    $limit = '1';

    $db->begin();
    $transaction = true;

    if( !$db->autoUpdate('topic', $data, $where, $order, $limit) ) {
        $transaction = false;
    }

    if( $transaction ) {
        $db->commit();
        show_system_message('板块更新成功', array());
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }


}

//===========================================================================

//板块列表
if( 'view' == $act ) {

    if( !check_purview('pur_topic_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_section_list = 'select * from '.$db->table('topic').' where 1  order by `order_view` ASC';
    $section_list = $db->fetchAll($get_section_list);
    assign('sectionList', $section_list);
}

//添加板块
if( 'add' == $act ) {

    if( !check_purview('pur_topic_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $get_configs = 'select * from '.$db->table('sysconf').' where 1';
    $configs = $db->fetchAll($get_configs);

    foreach( $configs as $config ) {
        $target[$config['key']] = $config['value'];
    }

    assign('configs', $target);
}

//编辑板块
if( 'edit' == $act ) {
    if( !check_purview('pur_topic_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = getGET('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_section = 'select * from `'.DB_PREFIX.'topic` where `id`='.$id.' limit 1';
    $section = $db->fetchRow($get_section);

    if( empty($section) ) {
        show_system_message('板块不存在', array());
        exit;
    }

    assign('section', $section);

    $get_configs = 'select * from '.$db->table('sysconf').' where 1';
    $configs = $db->fetchAll($get_configs);

    foreach( $configs as $config ) {
        $target[$config['key']] = $config['value'];
    }

    assign('configs', $target);
}


//删除板块
if( 'delete' == $act ) {
    if( !check_purview('pur_topic_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getGET('id');
    $id = intval($id);

    if(0 >= $id) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_content = 'select `id` from '.$db->table('forum').' where topic_id = '.$id;
    $content = $db->fetchRow($get_content);
    if( $content ) {
        show_system_message('当前板块下有帖子，不能删除', array());
        exit;
    }

    $delete_section = 'delete from `'.DB_PREFIX.'topic` where `id`='.$id.' limit 1';
    if($db->delete($delete_section)) {
        show_system_message('删除板块成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}


$template .= $act.'.phtml';
$smarty->display($template);
