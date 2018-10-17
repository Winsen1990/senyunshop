<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/10
 * Time: 上午10:06
 */
include 'library/init.inc.php';

$operation = 'sort';
$opera = check_action($operation, getPOST('opera'));

if('sort' == $opera)
{
    $response = array('error' => 1, 'msg' => '');

    $mode = getPOST('mode');

    if($mode != 'all')
    {
        $mode = intval($mode);
    }

    $get_article_list = 'select `title`,`id`,`add_time`,`description`,`original` from '.$db->table('content').' where `status`<>0 ';

    switch($mode)
    {
        case 'all':
            $get_article_list .= ' and `section_id` in (1,2,3)';
            break;

        default:
            $get_article_list .= ' and `section_id`='.$mode;
            break;
    }

    $get_article_list .= ' order by `order_view` ASC,`add_time` DESC';
    $article_list = $db->fetchAll($get_article_list);
    assign('article_list', $article_list);
    assign('section_list', null);

    $response['error'] = 0;
    $response['content'] = $smarty->fetch('news-list-item.phtml');

    echo json_encode($response);
    exit;
}

$id = getGET('id');
$id = intval($id);

$get_functions = 'select `name`,`url` from '.$db->table('modules').' where `assoc_type`=\'section_id\' and `assoc`='.$id;
$functions = $db->fetchAll($get_functions);

$get_section_list = 'select `section_name`, `id`,`original` from '.$db->table('section').' where `parent_id` = '.$id.' order by `order_view` asc';
$section_list = $db->fetchAll($get_section_list);

if(empty($section_list) && empty($functions) && $id <= 5) {
    $get_section_list = 'select `section_name`, `id`,`original` from '.$db->table('section').' where `parent_id` = 0 and `id`<=5 order by `order_view` asc';
    $section_list = $db->fetchAll($get_section_list);
}

if( $section_list && empty($functions)) {
    $get_article_list = 'select `title`,`id`,`add_time`,`description`,`original` from ' . $db->table('content') . ' where `section_id`='.$id.' and `status`<>0 order by `order_view` ASC,`add_time` DESC';
    $article_list = $db->fetchAll($get_article_list);

    assign('width', (100 / count($section_list)));
} else {
    $get_article_list = 'select `title`,`id`,`add_time`,`description`,`original` from ' . $db->table('content') . ' where `section_id`='.$id.' and `status`<>0 order by `order_view` ASC,`add_time` DESC';
    $article_list = $db->fetchAll($get_article_list);

    $tag_width = 25;

    if($functions) {
        $tag_width = (100 / count($functions));
    }
    assign('width', $tag_width);
}

assign('functions', $functions);
assign('article_list', $article_list);
assign('all', $id);
assign('section_list', $section_list);

$get_section = 'select `section_name` from '.$db->table('section').' where `id`='.$id;
$section = $db->fetchRow($get_section);
assign('title', $section['section_name']);


$smarty->display('news-list.phtml');