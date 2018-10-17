<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/12/10
 * Time: 下午7:00
 */
include '../../library/init.inc.php';

$response = array('ret'=>1, 'msg'=>'');

$action = 'start|restart';
$act = check_action($action, getGET('action'));

//开始游戏
if('start' == $act)
{
    $scene_id = intval(getGET('scene_id'));

    if($scene_id <= 0)
    {
        $response['msg'] = '场景不存在,游戏启动失败';
    }

    $check_scene = 'select `id` from '.$db->table('scene').' where `id`='.$scene_id;

    if(!$db->fetchOne($check_scene))
    {
        $response['msg'] = '场景不存在,游戏启动失败';
    } else {
        $get_cycle = 'select `id` from '.$db->table('cycle').' where `scene_id`='.$scene_id.' and `status`=0';

        $cycle_id = $db->fetchOne($get_cycle);

        if($db->autoUpdate('cycle', array('status'=>1), '`id`='.$cycle_id))
        {
            $response['ret'] = 0;
            $response['msg'] = '游戏启动成功';
        }
    }
}

//下一轮
if('restart' == $act)
{
    $scene_id = intval(getGET('scene_id'));

    if($scene_id <= 0)
    {
        $response['msg'] = '场景不存在,游戏启动失败';
    }

    $check_scene = 'select `id` from '.$db->table('scene').' where `id`='.$scene_id;

    if(!$db->fetchOne($check_scene))
    {
        $response['msg'] = '场景不存在,游戏启动失败';
    } else {
        //如果不存在正在报名或者参与中的活动,则创建新的活动
        $get_cycle = 'select `id` from '.$db->table('cycle').' where `scene_id`='.$scene_id.' and `status`<2';

        $cycle_id = $db->fetchOne($get_cycle);

        if(!$cycle_id)
        {
            $get_cycle_count = 'select count(*) from '.$db->table('cycle').' where `scene_id`='.$scene_id;

            $count = $db->fetchOne($get_cycle_count);

            $count++;

            $cycle_data = array(
                'scene_id' => $scene_id,
                'serial' => $count,
                'status' => 0,
                'add_time' => time()
            );

            if(!$db->autoInsert('cycle', array($cycle_data)))
            {
                $response['msg'] = '启动场景失败,请稍后再试';
            } else {
                $cycle_id = $db->get_last_id();
                $response['ret'] = 0;
            }
        }

        $_SESSION['cycle_id'] = $cycle_id;
    }
}

echo json_encode($response);