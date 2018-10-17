<?php
/**
 *
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 15-12-2
 * @version 
 */

include 'library/init.inc.php';

$action = 'list';
$operation = 'sort';

$act = check_action($action, getGET('act'));

$act = ($act == '') ? 'list' : $act;
$opera = check_action($operation, getPOST('opera'));
$id = 24;

if( 'sort' == $opera ) {
    $response = array('error'=>1, 'msg'=>'');

    $filter = getPOST('filter');
    $mode = getPOST('mode');

    $get_section = 'select `section_name`,`path` from '.$db->table('section').' where id = '.$id.' limit 1';
    $section = $db->fetchRow($get_section);
    assign('section', $section);
    $get_section_list = 'select `id` from '.$db->table('section').' where `path` like \''.$section['path'].'%\'';
    $section_list = $db->fetchAll($get_section_list);
    $section_id_array = array();
    if( $section_list ) {
        foreach( $section_list as $key => $section ) {
            array_push($section_id_array, $section['id']);
        }
    }
    $section_id_str = '('.implode(',', $section_id_array).')';
    $get_article_list = 'select * from '.$db->table('content').' where `section_id` in '.$section_id_str.' and status <> 0';

    $response['filter'] = $filter;

    //分组使用筛选条件

    switch($mode)
    {
        case 'default':
            $get_article_list .= ' order by `last_modify` DESC';
            break;
        case 'top':
            $get_article_list .= ' and order_view = 0 order by `last_modify` DESC';
            break;
        default:
            $get_article_list .= ' order by `last_modify` DESC';
            break;
    }

//    $response['sql'] = $get_article_list;
    $article_list = $db->fetchAll($get_article_list);

    assign('article_list', $article_list);
    $response['content'] = $smarty->fetch('recommend-item.phtml');
    $response['error'] = 0;

    echo json_encode($response);
    exit;
}

if( 'list' == $act ) {
    $filter = array();
    assign('filter', json_encode($filter));

    $get_section = 'select `section_name`,`path` from '.$db->table('section').' where id = '.$id.' limit 1';
    $section = $db->fetchRow($get_section);
    assign('section', $section);
    $get_section_list = 'select `id` from '.$db->table('section').' where `path` like \''.$section['path'].'%\'';
    $section_list = $db->fetchAll($get_section_list);
    $section_id_array = array();
    if( $section_list ) {
        foreach( $section_list as $key => $section ) {
            array_push($section_id_array, $section['id']);
        }
    }
    $section_id_str = '('.implode(',', $section_id_array).')';
    $get_article_list = 'select * from '.$db->table('content').' where `section_id` in '.$section_id_str;
    $get_article_list .= ' and `status` <> 0 order by `last_modify` desc';
    $article_list = $db->fetchAll($get_article_list);
    assign('article_list', $article_list);

    $get_user_info = 'select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
    $user_info = $db->fetchRow($get_user_info);

    assign('user_info', $user_info);
}
$smarty->display('recommend.phtml');