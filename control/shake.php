<?php
/**
 * 管理后台首页
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-8-5
 * @version 1.0.0
 */

include 'library/init.inc.php';

//管理后台初始化
back_base_init();
$template = 'shake/';

$action = 'detail|view|edit|reward|add|delete|reward_ajax';
$operation = 'add|edit';
$act = check_action($action, getGET('act'));
$opera = check_action($operation, getPOST('opera'));
$act = ( $act == '' ) ? 'view' : $act;

if('edit' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    $id = intval(getPOST('id'));
    $name = trim(getPOST('name'));
    $allow_repeat = trim(getPOST('allow_repeat'));

    if($id <= 0)
    {
        $response['msg'] = '参数错误';
    }

    if($name == '')
    {
        $response['errmsg']['name'] = '-请输入场景名称';
    } else {
        $name = $db->escape($name);
    }

    if($allow_repeat == '')
    {
        $response['errmsg']['allow_repeat'] = '-请选择是否允许重复获奖';
    } else {
        $allow_repeat = intval($allow_repeat);
    }

    if(count($response['errmsg']) == 0 && $response['msg'] == '')
    {
        $scene_data = array(
            'name' => $name,
            'allow_repeat' => $allow_repeat
        );

        if($db->autoUpdate('scene', $scene_data, '`id`='.$id))
        {
            $response['error'] = 0;
            $response['msg'] = '修改场景成功';
        } else {
            $response['msg'] = '系统繁忙,请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    $name = trim(getPOST('name'));
    $allow_repeat = trim(getPOST('allow_repeat'));

    if($name == '')
    {
        $response['errmsg']['name'] = '-请输入场景名称';
    } else {
        $name = $db->escape($name);
    }

    if($allow_repeat == '')
    {
        $response['errmsg']['allow_repeat'] = '-请选择是否允许重复获奖';
    } else {
        $allow_repeat = intval($allow_repeat);
    }

    if(count($response['errmsg']) == 0)
    {
        $scene_data = array(
            'name' => $name,
            'allow_repeat' => $allow_repeat,
            'add_time' => time()
        );

        if($db->autoInsert('scene', array($scene_data)))
        {
            $response['error'] = 0;
            $response['msg'] = '新增场景成功';
        } else {
            $response['msg'] = '系统繁忙,请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}
//====================================
//查看场景
if('view' == $act)
{
    if( !check_purview('pur_scene_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_scene_list = 'select * from '.$db->table('scene');
    $scene_list = $db->fetchAll($get_scene_list);

    assign('scene_list', $scene_list);
}

//删除场景
if('delete' == $act)
{
    if( !check_purview('pur_scene_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));
    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    $check_cycle = 'select `id` from '.$db->table('cycle').' where `scene_id`='.$id.' and `status`<2';

    if($db->fetchOne($check_cycle))
    {
        show_system_message('当前场景有进行中的活动,不能删除');
    }

    if($db->autoDelete('scene', '`id`='.$id))
    {
        $get_cycle_list = 'select `id` from '.$db->table('cycle').' where `scene_id`='.$id;
        $cycle_list = $db->fetchAll($get_cycle_list);

        $cycle_id_str = '';
        foreach($cycle_list as $c)
        {
            $cycle_id_str .= $c['id'].',';
        }

        $db->autoDelete('cycle', '`scene_id`='.$id);
        $db->autoDelete('cycle_reward', '`cycle_id` in ('.$cycle_id_str.'0)');

        show_system_message('删除场景成功');
    } else {
        show_system_message('系统繁忙,请稍后再试');
    }
}
//编辑场景
if('edit' == $act)
{
    if( !check_purview('pur_scene_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    $get_scene = 'select * from '.$db->table('scene').' where `id`='.$id;
    $scene = $db->fetchRow($get_scene);

    assign('scene', $scene);
    assign('subTitle', '编辑场景');
}

//新增场景
if('add' == $act)
{
    if( !check_purview('pur_scene_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    assign('subTitle', '新增场景');
}

//获奖记录
if('reward' == $act || 'reward_ajax' == $act)
{
    if( !check_purview('pur_scene_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    $get_reward_list = 'select * from '.$db->table('cycle').' where `scene_id`='.$id.' order by `serial`';
    $reward_list_temp = $db->fetchAll($get_reward_list);
    $reward_list = array();
    if($reward_list_temp)
    {
        foreach($reward_list_temp as $rlt)
        {
            $get_reward_detail = 'select cr.`position`,cr.`account`,m.`nickname` from '.$db->table('cycle_reward').' as cr '.
                                 ' join '.$db->table('member').' as m using(`account`) where cr.`cycle_id`='.$rlt['id'].' order by `position`';

            $reward_list[$rlt['serial']] = array(
                'detail' => $db->fetchAll($get_reward_detail)
            );
        }
    }

    assign('reward_list', $reward_list);
}

//游戏现场
if('detail' == $act)
{
    if( !check_purview('pur_scene_detail', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $scene_id = intval(getGET('scene_id'));

    if($scene_id <= 0)
    {
        show_system_message('参数错误');
    }

    $get_scene = 'select `id` from '.$db->table('scene').' where `id`='.$scene_id;

    if(!$db->fetchOne($get_scene))
    {
        show_system_message('场景不存在');
    }

    //如果不存在正在报名或者参与中的活动,则创建新的活动
    $get_cycle = 'select `id` from '.$db->table('cycle').' where `scene_id`='.$scene_id.' and `status`<2';

    $cycle_id = $db->fetchOne($get_cycle);

    $db->autoUpdate('cycle', array('actived'=>0));
    if(!$cycle_id)
    {

        $get_cycle_count = 'select count(*) from '.$db->table('cycle').' where `scene_id`='.$scene_id;

        $count = $db->fetchOne($get_cycle_count);

        $count++;

        $cycle_data = array(
            'scene_id' => $scene_id,
            'serial' => $count,
            'status' => 0,
            'add_time' => time(),
            'actived' => 1
        );

        if(!$db->autoInsert('cycle', array($cycle_data)))
        {
            show_system_message('启动场景失败,请稍后再试');
        } else {
            $cycle_id = $db->get_last_id();
        }
    } else {
        $db->autoUpdate('cycle', array('actived'=>1), '`id`='.$cycle_id);
    }

    $_SESSION['cycle_id'] = $cycle_id;
}
$template .= $act.'.phtml';
$smarty->display($template);




