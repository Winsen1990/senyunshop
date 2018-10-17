<?php
/**
 * 友情链接管理
 * @author Winsen
 * @email airplace1@gmail.com
 * @date 2015-8-6
 * @version 1.0.0
 */
include 'library/init.inc.php';
back_base_init();

$template = 'activity/';
assign('subTitle', '活动管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';
$act = check_action($action, getGET('act'));

$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

if('edit' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_activity_edit', $_SESSION['purview']))
    {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $id = intval(getPOST('eid'));
    $name = getPOST('name');

    if($name == '')
    {
        $response['errmsg']['name'] = '-请输入活动名称';
    } else {
        $name = $db->escape($name);
    }

    if(count($response['errmsg']) == 0 && $response['msg'] == '')
    {
        $adpos_data = array(
            'name' => $name
        );

        if($db->autoUpdate('activity', $adpos_data, '`id`='.$id))
        {
            $response['msg'] = '编辑活动成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_activity_add', $_SESSION['purview']))
    {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $name = getPOST('name');

    if($name == '')
    {
        $response['errmsg']['name'] = '-请输入活动名称';
    } else {
        $name = $db->escape($name);
    }

    if(count($response['errmsg']) == 0)
    {
        $adpos_data = array(
            'name' => $name
        );

        if($db->autoInsert('activity', array($adpos_data)))
        {
            $response['msg'] = '新增活动成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}


if('view' == $act)
{
    if(!check_purview('pur_activity_view', $_SESSION['purview']))
    {
        show_system_message('权限不足', array());
        exit;
    }

    $get_adpos_list  = 'select * from '.$db->table('activity');
    $adpos_list = $db->fetchAll($get_adpos_list);

    assign('adpos_list', $adpos_list);
}

if('edit' == $act)
{
    if( !check_purview('pur_adpos_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    $get_adpos = 'select `id`,`name` from '.$db->table('activity').' where `id`='.$id;

    assign('adpos', $db->fetchRow($get_adpos));
}

if('delete' == $act)
{
    if( !check_purview('pur_activity_del', $_SESSION['purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('请求失败');
        exit;
    }

    if($db->autoDelete('activity', '`id`='.$id))
    {
        $db->autoDelete('activity_mapper', '`activity_id`='.$id);
        show_system_message('删除活动成功');
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试');
        exit;
    }
}

$template .= $act.'.phtml';
$smarty->display($template);