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

$template = 'ad/';
assign('subTitle', '广告管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

$get_adpos_list = 'select `id`,`pos_name` as `name`,`width`,`height` from '.$db->table('ad_position');
$adpos_json = array();
$adpos_list = $db->fetchAll($get_adpos_list);
if( $adpos_list ) {
    foreach ($adpos_list as $adpos) {
        $adpos_json[$adpos['id']] = $adpos;
    }
}
assign('adpos_json', json_encode($adpos_json));
assign('adpos_list', $adpos_list);

if('edit' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_ad_edit', $_SESSION['purview']))
    {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $url = getPOST('url');
    $img = getPOST('img');
    $alt = getPOST('alt');
    $forever = getPOST('forever');
    $ad_pos_id = intval(getPOST('ad_pos_id'));
    $order_view = intval(getPOST('order_view'));
    $begin_time = getPOST('begin_time');
    $end_time = getPOST('end_time');
    $id = intval(getPOST('eid'));

    if($id <= 0)
    {
        $response['msg'] = '参数错误';
    }

    if($alt == '')
    {
        $response['errmsg']['alt'] = '-请填写替换文字';
    } else {
        $alt = $db->escape($alt);
    }

    if($ad_pos_id <= 0)
    {
        $response['errmsg']['ad_pos_id'] = '-请选择广告位置';
    }

    if($order_view < 0)
    {
        $response['errmsg']['order_view'] = '-请输入广告排序';
    }

    if($forever == 0)
    {
        if($begin_time == '' || $end_time == '')
        {
            $response['errmsg']['time'] = '-请选择有效时间';
        } else {
            $begin_time = strtotime($begin_time);
            $end_time = strtotime($end_time);

            if($begin_time == -1 || !$begin_time || $end_time == -1 || !$end_time || $begin_time > $end_time)
            {
                $response['errmsg']['time'] = '-请选择有效时间';
            }
        }
    } else {
        $forever = 1;
        $begin_time = time();
        $end_time = -1;
    }

    if($url == '')
    {
        $response['errmsg']['url'] = '-请输入广告链接';
    } else {
        $url = $db->escape($url);
    }

    if($img == '')
    {
        $response['errmsg']['img'] = '-请上传广告图片';
    } else {
        $img = $db->escape($img);
    }

    if(count($response['errmsg']) == 0)
    {
        $ad_data = array(
            'url' => $url,
            'img' => $img,
            'begin_time' => $begin_time,
            'end_time' => $end_time,
            'alt' => $alt,
            'order_view' => $order_view,
            'ad_pos_id' => $ad_pos_id,
            'forever' => $forever
        );

        if($db->autoUpdate('ad', $ad_data, '`id`='.$id))
        {
            $response['msg'] = '修改广告成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试'.$db->errmsg();
        }
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_ad_add', $_SESSION['purview']))
    {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $url = getPOST('url');
    $img = getPOST('img');
    $alt = getPOST('alt');
    $forever = getPOST('forever');
    $ad_pos_id = intval(getPOST('ad_pos_id'));
    $order_view = intval(getPOST('order_view'));
    $begin_time = getPOST('begin_time');
    $end_time = getPOST('end_time');

    if($alt == '')
    {
        $response['errmsg']['alt'] = '-请填写替换文字';
    } else {
        $alt = $db->escape($alt);
    }

    if($ad_pos_id <= 0)
    {
        $response['errmsg']['ad_pos_id'] = '-请选择广告位置';
    }

    if($order_view < 0)
    {
        $response['errmsg']['order_view'] = '-请输入广告排序';
    }

    if($forever == 0)
    {
        if($begin_time == '' || $end_time == '')
        {
            $response['errmsg']['time'] = '-请选择有效时间';
        } else {
            $begin_time = strtotime($begin_time);
            $end_time = strtotime($end_time);

            if($begin_time == -1 || !$begin_time || $end_time == -1 || !$end_time || $begin_time > $end_time)
            {
                $response['errmsg']['time'] = '-请选择有效时间';
            }
        }
    } else {
        $forever = 1;
        $begin_time = time();
        $end_time = -1;
    }

    if($url == '')
    {
        $response['errmsg']['url'] = '-请输入广告链接';
    } else {
        $url = $db->escape($url);
    }

    if($img == '')
    {
        $response['errmsg']['img'] = '-请上传广告图片';
    } else {
        $img = $db->escape($img);
    }

    if(count($response['errmsg']) == 0)
    {
        $ad_data = array(
            'url' => $url,
            'img' => $img,
            'add_time' => time(),
            'begin_time' => $begin_time,
            'end_time' => $end_time,
            'alt' => $alt,
            'order_view' => $order_view,
            'ad_pos_id' => $ad_pos_id,
            'forever' => $forever,
            'business_account' => ''
        );

        if($db->autoInsert('ad', array($ad_data)))
        {
            $response['msg'] = '新增广告成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试'.$db->errmsg();
        }
    }

    echo json_encode($response);
    exit;
}


if('view' == $act) {
    if (!check_purview('pur_ad_view', $_SESSION['purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $pos = intval(getGET('pos'));
    $and_where = '';
    if( $pos > 0 ) {
        $and_where = ' and ad_pos_id = '.$pos;
    }
    assign('pos', $pos);

    $page = intval(getGET('page'));
    $page_count = 10;
    assign('count', $page_count);

    $get_total = 'select count(*) from '.$db->table('ad').' where business_account = \'\''. $and_where;
    $total = $db->fetchOne($get_total);

    $total_page = ceil($total / $page_count);
    $page = $page >= $total_page ? $total_page : $page;
    $page = $page <= 0 ? 1 : $page;
    $offset = ($page - 1) * $page_count;
    create_pager($page, $total_page, $total);

    $get_ad_list = 'select * from ' . $db->table('ad') . ' where business_account = \'\''.$and_where.' limit '.$offset.','.$page_count;
    $ad_list = $db->fetchAll($get_ad_list);
    if ($ad_list) {
        foreach ($ad_list as $key => $ad) {
            $ad_list[$key]['pos_name'] = $adpos_json[$ad['ad_pos_id']]['name'];
        }
    }

    assign('ad_list', $ad_list);
}

if('edit' == $act)
{
    if( !check_purview('pur_ad_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    $get_ad = 'select * from '.$db->table('ad').' where `id`='.$id.' and business_account = \'\'';

    assign('ad', $db->fetchRow($get_ad));
}

if('delete' == $act)
{
    if( !check_purview('pur_ad_del', $_SESSION['purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('请求失败');
        exit;
    }

    $get_img = 'select `img` from '.$db->table('ad').' where `id`='.$id.' and business_account = \'\'';

    $img = $db->fetchOne($get_img);

    if($db->autoDelete('ad', '`id`='.$id)) {
        show_system_message('删除广告成功');
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试');
        exit;
    }
}

$template .= $act.'.phtml';
$smarty->display($template);
