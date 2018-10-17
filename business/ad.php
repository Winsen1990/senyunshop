<?php
/**
 * 商家店伦播
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-10-16
 * @version 1.0.0
 */

include 'library/init.inc.php';
business_base_init();

$template = 'ad/';
assign('subTitle', '广告管理');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

//===========================================================================

if( 'add' == $opera ) {
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_ad_add', $_SESSION['business_purview'])) {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $url = getPOST('url');
    $img = getPOST('img');
    $alt = getPOST('alt');
    $forever = getPOST('forever');
    $order_view = intval(getPOST('order_view'));
    $begin_time = getPOST('begin_time');
    $end_time = getPOST('end_time');

    if($alt == '') {
        $response['errmsg']['alt'] = '-请填写替换文字';
    } else {
        $alt = $db->escape($alt);
    }

    if($order_view < 0) {
        $response['errmsg']['order_view'] = '-请输入广告排序';
    }

    if($forever == 0) {
        if($begin_time == '' || $end_time == '') {
            $response['errmsg']['time'] = '-请选择有效时间';
        } else {
            $begin_time = strtotime($begin_time);
            $end_time = strtotime($end_time);

            if($begin_time == -1 || !$begin_time || $end_time == -1 || !$end_time || $begin_time > $end_time) {
                $response['errmsg']['time'] = '-请选择有效时间';
            }
        }
    } else {
        $forever = 1;
        $begin_time = time();
        $end_time = -1;
    }

    if($url == '') {
        $response['errmsg']['url'] = '-请输入广告链接';
    } else {
        $url = $db->escape($url);
    }

    if($img == '') {
        $response['errmsg']['img'] = '-请上传广告图片';
    } else {
        $img = $db->escape($img);
    }

    if(count($response['errmsg']) == 0) {
        $ad_data = array(
            'url' => $url,
            'img' => $img,
            'add_time' => time(),
            'begin_time' => $begin_time,
            'end_time' => $end_time,
            'alt' => $alt,
            'order_view' => $order_view,
            'ad_pos_id' => 3,
            'forever' => $forever,
            'business_account' => $_SESSION['business_account'],
        );

        if($db->autoInsert('ad', array($ad_data))) {
            $response['msg'] = '新增广告成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试'.$db->errmsg();
        }
    }

    echo json_encode($response);
    exit;
}

if( 'edit' == $opera ) {
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_ad_edit', $_SESSION['business_purview'])) {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $url = getPOST('url');
    $img = getPOST('img');
    $alt = getPOST('alt');
    $forever = getPOST('forever');
    $order_view = intval(getPOST('order_view'));
    $begin_time = getPOST('begin_time');
    $end_time = getPOST('end_time');
    $id = intval(getPOST('eid'));

    if($id <= 0) {
        $response['msg'] = '参数错误';
    }

    $get_ad = 'select * from '.$db->table('ad').' where `id`='.$id.' and business_account = \''.$_SESSION['business_account'].'\' limit 1';
    $ad = $db->fetchRow($get_ad);
    if( empty($ad) ) {
        show_system_message('广告不存在');
        exit;
    }


    if($alt == '') {
        $response['errmsg']['alt'] = '-请填写替换文字';
    } else {
        $alt = $db->escape($alt);
    }


    if($order_view < 0) {
        $response['errmsg']['order_view'] = '-请输入广告排序';
    }

    if($forever == 0) {
        if($begin_time == '' || $end_time == '') {
            $response['errmsg']['time'] = '-请选择有效时间';
        } else {
            $begin_time = strtotime($begin_time);
            $end_time = strtotime($end_time);

            if($begin_time == -1 || !$begin_time || $end_time == -1 || !$end_time || $begin_time > $end_time) {
                $response['errmsg']['time'] = '-请选择有效时间';
            }
        }
    } else {
        $forever = 1;
        $begin_time = time();
        $end_time = -1;
    }

    if($url == '') {
        $response['errmsg']['url'] = '-请输入广告链接';
    } else {
        $url = $db->escape($url);
    }

    if($img == '') {
        $response['errmsg']['img'] = '-请上传广告图片';
    } else {
        $img = $db->escape($img);
    }

    if(count($response['errmsg']) == 0) {
        $ad_data = array(
            'url' => $url,
            'img' => $img,
            'begin_time' => $begin_time,
            'end_time' => $end_time,
            'alt' => $alt,
            'order_view' => $order_view,
            'forever' => $forever
        );

        if($db->autoUpdate('ad', $ad_data, '`id`='.$id)) {
            $response['msg'] = '修改广告成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试'.$db->errmsg();
        }
    }

    echo json_encode($response);
    exit;
}


//===========================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_ad_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_ad_list = 'select * from '.$db->table('ad');
    $get_ad_list .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_ad_list .= ' order by order_view asc, add_time desc';

    $ad_list = $db->fetchAll($get_ad_list);
    assign('ad_list', $ad_list);
}

if( 'add' == $act ) {
    if( !check_purview('pur_ad_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_number = 'select `number` from '.$db->table('ad_position').' where id = 3 limit 1';
    $number = $db->fetchOne($get_number);

    $get_total = 'select count(*) from '.$db->table('ad').' where business_account = \''.$_SESSION['business_account'].'\'';
    $total = $db->fetchOne($get_total);

    if( $total >= $number ) {
        show_system_message('广告数量到达上限', array());
        exit;
    }
}

if('edit' == $act) {
    if( !check_purview('pur_ad_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    $get_ad = 'select * from '.$db->table('ad').' where `id`='.$id.' and business_account = \''.$_SESSION['business_account'].'\' limit 1';

    $ad = $db->fetchRow($get_ad);
    if( empty($ad) ) {
        show_system_message('广告不存在');
        exit;
    }

    assign('ad', $ad);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_ad_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('请求失败');
        exit;
    }

    $get_ad = 'select * from '.$db->table('ad').' where `id`='.$id.' and business_account = \''.$_SESSION['business_account'].'\' limit 1';
    $ad = $db->fetchRow($get_ad);
    if( empty($ad) ) {
        show_system_message('广告不存在');
        exit;
    }

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