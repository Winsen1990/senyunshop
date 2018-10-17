<?php
/**
 * 栏目管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
business_base_init();

$template = 'section/';
assign('subTitle', '栏目管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

//添加栏目
if( 'add' == $opera ) {
    if( !check_purview('pur_section_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $name = trim(getPOST('articleCatName'));
    $parent_id = getPOST('parentId');
    $keywords = trim(getPOST('keywords'));
    $description = trim(getPOST('description'));
    $order_view = intval(getPOST('order_view'));
    $path = '';
    $original = trim(getPOST('img'));
    $thumb = '';


    if('' == $name) {
        show_system_message('栏目名称不能为空', array());
        exit;
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

//    if('' == $keywords) {
//        show_system_message('出于SEO考虑，请务必填写关键词', array());
//        exit;
//    } else {
//        $keywords = $db->escape(htmlspecialchars($keywords));
//    }
//
//    if('' == $description) {
//        show_system_message('出于SEO考虑，请务必填写简要介绍', array());
//        exit;
//    } else {
//        $description = $db->escape(htmlspecialchars($description));
//    }

    $keywords = '';
    $description = '';

    if( '' == $original ) {

    } else {
        $original = $db->escape(htmlspecialchars($original));
        $thumb = $original;
    }

    $parent_id = intval($parent_id);
    if( 0 > $parent_id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $data = array(
        'section_name' => $name,
        'parent_id' => $parent_id,
        'path' => '',
        'keywords' => $keywords,
        'description' => $description,
        'order_view' => $order_view,
        'thumb' => $thumb,
        'original' => $original,
    );

    if( $db->autoInsert('section', array($data)) ) {
        $id = $db->get_last_id();
        $path = $id.',';
        if( 0 < $parent_id ) {
            $get_parent_path = 'select `path` from `'.DB_PREFIX.'section` where `id`='.$parent_id;
            if( $parent_path = $db->fetchRow($get_parent_path) ) {
                $path = $parent_path['path'].','.$path;
            }
        }

        $update_section = 'update `'.DB_PREFIX.'section` set `path`=\''.$path.'\' where `id`='.$id.' limit 1';
        if( $db->update($update_section) ) {
            $links = array(
                array('alt'=>'查看栏目', 'link'=>'section.php?act=list'),
                array('alt'=>'继续添加栏目', 'link'=>'section.php?act=add')
            );
            show_system_message('添加栏目成功', $links);
            exit;
        } else {
            show_system_message('更新栏目失败', array());
            exit;
        }
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }

}

//编辑栏目
if( 'edit' == $opera ) {

    if( !check_purview('pur_section_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = getPOST('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_section = 'select * from `'.DB_PREFIX.'section` where `id`='.$id.' limit 1';
    $section = $db->fetchRow($get_section);

    if( empty($section) ) {
        show_system_message('栏目不存在', array());
        exit;
    }

    $name = trim(getPOST('articleCatName'));
    $parent_id = getPOST('parentId');
    $keywords = trim(getPOST('keywords'));
    $description = trim(getPOST('description'));
    $order_view = intval(getPOST('order_view'));
    $path = '';
    $original = trim(getPOST('img'));
    $thumb = '';


    if('' == $name) {
        show_system_message('栏目名称不能为空', array());
        exit;
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

//    if('' == $keywords) {
//        show_system_message('出于SEO考虑，请务必填写关键词', array());
//        exit;
//    } else {
//        $keywords = $db->escape(htmlspecialchars($keywords));
//    }
//
//    if('' == $description) {
//        show_system_message('出于SEO考虑，请务必填写简要介绍', array());
//        exit;
//    } else {
//        $description = $db->escape(htmlspecialchars($description));
//    }

    if($keywords != '') {
        $keywords = $db->escape(trim($keywords));
    } else {
        $keywords = '';
    }

    if($description != '') {
        $description = $db->escape(trim($description));
    } else {
        $description = '';
    }

    if( '' == $original ) {

    } else {
        $original = $db->escape(htmlspecialchars($original));
        $thumb = $original;
    }

    $parent_id = intval($parent_id);
    if( 0 > $parent_id ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( 0 < $parent_id ) {
        $get_parent_path = 'select `path` from `'.DB_PREFIX.'section` where `id`='.$parent_id;
        if( $parent_path = $db->fetchRow($get_parent_path) ) {
            $path = $parent_path['path'].$id.',';
        }
    } else {
        $path = $id.',';
    }

    $data = array(
        'section_name' => $name,
        'parent_id' => $parent_id,
        'path' => $path,
        'keywords' => $keywords,
        'description' => $description,
        'order_view' => $order_view,
        'thumb' => $thumb,
        'original' => $original,
    );

    /*
    foreach( $data as $key => $value ) {
        if( $value == '' ) {
            unset($data[$key]);
        }
    }
    */

    $where = 'id = '.$id;
    $order = '';
    $limit = '1';

    $db->begin();
    $transaction = true;

    if( !$db->autoUpdate('section', $data, $where, $order, $limit) ) {
        $transaction = false;
    }

    //更新所有后代
    $get_section_list = 'select `id`, `path` from '.$db->table('section').' where id <> '.$id;
    $section_list = $db->fetchAll($get_section_list);
    if( $section_list ) {
        foreach( $section_list as $key => $value ) {
            $temp = str_replace($section['path'], $path, $section_list[$key]['path']);
            if( $temp != $value['path'] ) {
                $update_children_section = 'update '.$db->table('section').' set path = \''.$temp.'\'';
                $update_children_section .= ' where id = \''.$value['id'].'\' limit 1';
                if( !$db->update($update_children_section) ) {
                    $transaction = false;
                }
            }

        }
    }

    if( $transaction ) {
        $db->commit();
        show_system_message('栏目更新成功', array());
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }


}

//===========================================================================

//栏目列表
if( 'view' == $act ) {

    if( !check_purview('pur_section_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_section_list = 'select * from '.$db->table('section').' where 1  order by `order_view` ASC,`path` ASC';
    $section_list = $db->fetchAll($get_section_list);

    if( $section_list ) {
        foreach ($section_list as $key => $section) {
            $count = count(explode(',', $section['path']));
            if ($count > 1) {
                $temp = '|--' . $section['section_name'];
                while ($count--) {
                    $temp = '&nbsp;&nbsp;' . $temp;
                }

                $section['section_name'] = $temp;
                $section_list[$key] = $section;
            }
        }
    }

    assign('sectionList', $section_list);
}

//添加栏目
if( 'add' == $act ) {

    if( !check_purview('pur_section_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_section_list = 'select * from '.$db->table('section').' where 1 order by `path` ASC';
    $section_list = $db->fetchAll($get_section_list);

    if( $section_list ) {
        foreach ($section_list as $key => $section) {
            $count = count(explode(',', $section['path']));
            if ($count > 1) {
                $temp = '|--' . $section['section_name'];
                while ($count--) {
                    $temp = '&nbsp;&nbsp;' . $temp;
                }

                $section['section_name'] = $temp;
                $section_list[$key] = $section;
            }
        }
    }

    assign('sectionList', $section_list);

    $get_configs = 'select * from '.$db->table('sysconf').' where 1';
    $configs = $db->fetchAll($get_configs);

    foreach( $configs as $config ) {
        $target[$config['key']] = $config['value'];
    }

    assign('configs', $target);
}

//编辑栏目
if( 'edit' == $act ) {
    if( !check_purview('pur_section_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = getGET('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_section = 'select * from `'.DB_PREFIX.'section` where `id`='.$id.' limit 1';
    $section = $db->fetchRow($get_section);

    if( empty($section) ) {
        show_system_message('栏目不存在', array());
        exit;
    }

    assign('section', $section);

    $get_section_list = 'select * from '.$db->table('section').' where `id` <> '.$id.'  order by `path` ASC';
    $section_list = $db->fetchAll($get_section_list);
    $target = array();
    if( $section_list ) {
        foreach ($section_list as $key => $value) {
            if( false === strpos($value['path'], $section['path']) ) {

                $count = count(explode(',', $value['path']));
                if ($count > 1) {
                    $temp = '|--' . $value['section_name'];
                    while ($count--) {
                        $temp = '&nbsp;&nbsp;' . $temp;
                    }

                    $value['section_name'] = $temp;
                    $target[] = $value;
                }
            }
        }
    }
    assign('sectionList', $target);

    $get_configs = 'select * from '.$db->table('sysconf').' where 1';
    $configs = $db->fetchAll($get_configs);

    foreach( $configs as $config ) {
        $target[$config['key']] = $config['value'];
    }

    assign('configs', $target);

}


//删除栏目
if( 'delete' == $act ) {
    if( !check_purview('pur_section_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getGET('id');
    $id = intval($id);

    if(0 >= $id) {
        show_system_message('参数错误', array());
        exit;
    }

    $check_section = 'select `id` from '.$db->table('section').' where `parent_id`='.$id;
    $section = $db->fetchAll($check_section);
    if( $section ) {
        show_system_message('当前栏目下有子栏目，请先删除或移走子栏目', array());
        exit;
    }

    $get_content = 'select `id` from '.$db->table('content').' where section_id = '.$id.' and status = 1';
    $content = $db->fetchRow($get_content);
    if( $content ) {
        show_system_message('当前栏目下有资讯，请先删除或移走资讯', array());
        exit;
    }

//    if( $id == 1 ) {
//        show_system_message('系统保留分类，不允许删除', array());
//        exit;
//    }

    $delete_section = 'delete from `'.DB_PREFIX.'section` where `id`='.$id.' limit 1';
    if($db->delete($delete_section)) {
        $delete_content = 'delete from '.$db->table('content').' where status = 0 and section_id = '.$id;
        $db->delete($delete_content);
        show_system_message('删除栏目成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}


$template .= $act.'.phtml';
$smarty->display($template);
