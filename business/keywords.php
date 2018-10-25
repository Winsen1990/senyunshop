<?php
/**
 * 友情链接管理
 * @author Winsen
 * @email airplace1@gmail.com
 * @date 2015-8-6
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $db, $log, $smarty, $config;
business_base_init();

$template = 'keywords/';
assign('subTitle', '关键词管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

if('edit' == $opera)
{
    $response = array('error'=>1, 'message'=>'', 'errors'=>array());

    if(!check_purview('pur_keyword_edit', $_SESSION['business_purview']))
    {
        throw new RestFulException('没有操作权限', 550);
    }

    $keyword = trim(getPOST('keyword'));
    $sort = intval(getPOST('sort'));
    $sort = max(1, $sort);
    $id = intval(getPOST('id'));

    if($id <= 0)
    {
        $response['message'] = '参数错误';
    } else {
        $keyword_record = $db->find('keywords', ['id'], ['id' => $id]);
        
        if(empty($keyword_record)) {
            $response['message'] = '关键词不存在';
        }
    }

    if(empty($keyword)) {
        $response['errors']['keyword'] = '请填写关键词';
    } else {
        if($db->getColumn('keywords', 'id', ['keyword' => $keyword, 'id' => ['neq', $id]])) {
            $response['errors']['keyword'] = '关键词已存在';
        }
    }

    if(count($response['errors']) == 0 && $response['message'] == '')
    {
        $keyword_data = [
            'keyword' => $keyword,
            'sort' => $sort
        ];

        if($db->upgrade('keywords', $keyword_data, ['id' => $id]) !== false)
        {
            $response['message'] = '修改关键词成功';
            $response['error'] = 0;
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = array('error'=>1, 'message'=>'', 'errors'=>array());

    if(!check_purview('pur_keyword_add', $_SESSION['business_purview']))
    {
        throw new RestFulException('没有操作权限', 550);
    }

    $keyword = trim(getPOST('keyword'));
    $sort = intval(getPOST('sort'));
    $sort = max(1, $sort);

    if(empty($keyword)) {
        $response['errors']['keyword'] = '请填写关键词';
    } else {
        if($db->getColumn('keywords', 'id', ['keyword' => $keyword])) {
            $response['errors']['keyword'] = '关键词已存在';
        }
    }

    if(count($response['errors']) == 0 && $response['message'] == '')
    {
        $keyword_data = [
            'keyword' => $keyword,
            'sort' => $sort
        ];

        if($db->create('keywords', $keyword_data))
        {
            $response['message'] = '新增关键词成功';
            $response['error'] = 0;
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}


if('view' == $act) {
    if (!check_purview('pur_keyword_view', $_SESSION['business_purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $keywords = $db->all('keywords');

    assign('keywords', $keywords);
}

if('edit' == $act)
{
    if( !check_purview('pur_keyword_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    $keyword = $db->find('keywords', ['id', 'keyword', 'sort'], ['id' => $id]);

    assign('keyword', $keyword);
}

if('delete' == $act)
{
    if( !check_purview('pur_keyword_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('请求失败');
        exit;
    }

    if($db->destroy('keywords', ['id' => $id])) {
        show_system_message('删除关键词成功');
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试');
        exit;
    }
}

$template .= $act.'.phtml';
$smarty->display($template);
