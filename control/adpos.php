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

$template = 'adpos/';
assign('subTitle', '广告位置管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';
$act = check_action($action, getGET('act'));

$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

if('edit' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_adpos_edit', $_SESSION['purview']))
    {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $id = intval(getPOST('eid'));
    $pos_name = getPOST('pos_name');
    $height = getPOST('height');
    $width = getPOST('width');
    $code = getPOST('code');
    $number = intval(getPOST('number'));

    if($id <= 0)
    {
        $response['msg'] = '参数错误';
    }

    if($pos_name == '')
    {
        $response['errmsg']['pos_name'] = '-请输入广告位置名称';
    } else {
        $pos_name = $db->escape($pos_name);
    }

    $code = $db->escape($code);

    if($height == '' || $height < 0)
    {
        $response['errmsg']['height'] = '-请输入广告图片高度';
    } else {
        $height = $db->escape($height);
    }

    if($width == '' || $width < 0)
    {
        $response['errmsg']['width'] = '-请输入广告图片宽度';
    } else {
        $width = $db->escape($width);
    }


    if($number < 0)
    {
        $response['errmsg']['number'] = '-请输入广告展示数量';
    }

    if(count($response['errmsg']) == 0 && $response['msg'] == '')
    {
        $adpos_data = array(
            'pos_name' => $pos_name,
            'height' => $height,
            'width' => $width,
            'code' => $code,
            'number' => $number
        );

        if($db->autoUpdate('ad_position', $adpos_data, '`id`='.$id))
        {
            $response['msg'] = '编辑广告位置成功';
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

    if(!check_purview('pur_adpos_add', $_SESSION['purview']))
    {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $pos_name = getPOST('pos_name');
    $height = getPOST('height');
    $width = getPOST('width');
    $code = getPOST('code');
    $number = intval(getPOST('number'));

    if($pos_name == '')
    {
        $response['errmsg']['pos_name'] = '-请输入广告位置名称';
    } else {
        $pos_name = $db->escape($pos_name);
    }

    $code = $db->escape($code);

    if($height == '' || $height < 0)
    {
        $response['errmsg']['height'] = '-请输入广告图片高度';
    } else {
        $height = $db->escape($height);
    }

    if($width == '' || $width < 0)
    {
        $response['errmsg']['width'] = '-请输入广告图片宽度';
    } else {
        $width = $db->escape($width);
    }


    if($number < 0)
    {
        $response['errmsg']['number'] = '-请输入广告展示数量';
    }

    if(count($response['errmsg']) == 0)
    {
        $adpos_data = array(
            'pos_name' => $pos_name,
            'height' => $height,
            'width' => $width,
            'code' => $code,
            'number' => $number
        );

        if($db->autoInsert('ad_position', array($adpos_data)))
        {
            $response['msg'] = '新增广告位置成功';
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
    if(!check_purview('pur_adpos_view', $_SESSION['purview']))
    {
        show_system_message('权限不足', array());
        exit;
    }

    $get_adpos_list  = 'select * from '.$db->table('ad_position');
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

    $get_adpos = 'select `id`,`pos_name`,`width`,`height`,`number`,`code` from '.$db->table('ad_position').' where `id`='.$id;

    assign('adpos', $db->fetchRow($get_adpos));
}

if('delete' == $act)
{
    if( !check_purview('pur_adpos_del', $_SESSION['purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('请求失败');
        exit;
    }

    if( 1 <= $id && $id <= 4 ) {
        show_system_message('请勿删除固定广告位', array(array('alt' => '广告位列表', 'link' => 'adpos.php')));
        exit;
    }

    if($db->autoDelete('ad_position', '`id`='.$id))
    {
        show_system_message('删除广告位置成功');
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试');
        exit;
    }
}

$template .= $act.'.phtml';
$smarty->display($template);

