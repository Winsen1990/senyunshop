<?php
include 'library/init.inc.php';

$operation = 'shake';
$opera = check_action($operation, getPOST('opera'));

if('shake' == $opera)
{
    $response = array('error' => 1, 'msg' => '');

    $progress = intval(getPOST('progress'));
    $cycle = intval(getPOST('cycle'));

    if($progress <= 0)
    {
        $progress = 1;
    }

    if($cycle <= 0)
    {
        $response['msg'] = '参数错误';
    }

    if($response['msg'] == '')
    {
        $get_cycle_status = 'select `status` from '.$db->table('cycle').' where `id`='.$cycle;
        $status = $db->fetchOne($get_cycle_status);

        $get_shake = 'select `id`,`total`,`progress`,`goal` from '.$db->table('shake').' where `account`=\''.$_SESSION['account'].'\'';
        $shake = $db->fetchRow($get_shake);

        if($shake && $status == 1)
        {
            $goal = false;

            if($shake['total'] < 100) {
                if ($shake['total'] + $progress >= 100) {
                    $progress = 100 - $shake['total'];
                    $goal = true;
                }

                $shake_data = array(
                    'total' => $shake['total'] + $progress,
                    'progress' => $shake['progress'] + $progress,
                    'cycle' => $cycle
                );

                if ($goal) {
                    $shake_data['end_time'] = microtime();
                }

                if ($db->autoUpdate('shake', $shake_data, '`account`=\'' . $_SESSION['account'] . '\'')) {
                    $response['error'] = 0;
                } else {
                    $response['msg'] = '系统繁忙,请稍后再试';
                }
            } else {
                $response['msg'] = '到终点了';
            }
        } else {
            if($status == 2)
            {
                $response['msg'] = '活动已经结束,请看大屏幕排名';
            } else {
                //检查如果没有获奖或系统允许重复参与
                $get_scene_id = 'select `scene_id` from ' . $db->table('cycle') . ' where `id`=' . $cycle_id;
                $scene_id = $db->fetchOne($get_scene_id);

                $get_allow_repeat = 'select `allow_repeat` from ' . $db->table('scene') . ' where `id`=' . $scene_id;
                $allow_repeat = $db->fetchOne($get_allow_repeat);

                if (!$allow_repeat && $shake['goal']) {
                    $response['msg'] = '您已获奖,不能再次参与本活动';
                } else {
                    $response['msg'] = '游戏尚未开始';
                }
            }
        }
    }

    echo json_encode($response);
    exit;
}

//检查是否有报名中的活动
$get_cycle = 'select `id`,`status` from '.$db->table('cycle').' where `actived`=1';
$cycle = $db->fetchRow($get_cycle);

if($cycle)
{
    $cycle_id = $cycle['id'];
    $get_shake = 'select * from '.$db->table('shake').' where `account`=\''.$_SESSION['account'].'\'';
    $shake = $db->fetchRow($get_shake);

    if(!$shake)
    {
        $cycle_data = array(
            'account' => $_SESSION['account'],
            'cycle' =>$cycle_id,
            'add_time' => time(),
            'end_time' => '999999'
        );

        $db->autoInsert('shake', array($cycle_data));
    } else {
        if($cycle['status'] == 2)
        {
            assign('notice', '活动已结束,请看大屏幕排名');
        } else {
            //检查如果没有获奖或系统允许重复参与
            $get_scene_id = 'select `scene_id` from ' . $db->table('cycle') . ' where `id`=' . $cycle_id;
            $scene_id = $db->fetchOne($get_scene_id);

            $get_allow_repeat = 'select `allow_repeat` from ' . $db->table('scene') . ' where `id`=' . $scene_id;
            $allow_repeat = $db->fetchOne($get_allow_repeat);

            if ($allow_repeat || $shake['goal'] == 0) {
                $cycle_data = array(
                    'add_time' => time(),
                    'cycle' => $cycle_id,
                    'end_time' => '999999999'
                );

                $db->autoUpdate('shake', $cycle_data, '`id`=' . $shake['id']);
            } else {
                assign('notice', '您已获奖,不能再次参与本次活动');
            }
        }
    }
} else {
    assign('notice', '不在活动时间');
}

assign('cycle', intval($cycle_id));
$smarty->display('shake.phtml');