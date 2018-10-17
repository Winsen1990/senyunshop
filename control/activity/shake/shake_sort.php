<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/12/10
 * Time: 下午7:01
 */
include '../../../library/init.inc.php';
$response = array(
    'ret' => 0,
    'data' => array(
        'status' => 1,//1--游戏中, 2--游戏结束
        'players' => array()
    )
);

$scene_id = intval(getGET('scene_id'));

$get_cycle = 'select `id` from '.$db->table('cycle').' where `scene_id`='.$scene_id.' and `status`=1';

$cycle_id = $db->fetchOne($get_cycle);

$get_member_list = 'select s.`total`,s.`id` as `shake_id`,if(m.`headimg`<>\'\',m.`headimg`,\'../themes/sbx/images/wang.jpg\') as `avatar`,m.`sex` as `sex`,m.`id` as `id`,s.`progress`,m.`nickname` as `nick_name`'.
              ' from '.$db->table('shake').' as s left join '.$db->table('member').' as m using(`account`) where `cycle`='.$cycle_id.' order by s.`total` DESC';
$member_list = $db->fetchAll($get_member_list);
if($member_list) {
    foreach ($member_list as $index => $m) {
        $m['progress'] = intval($m['progress']);
        $m['shake_id'] = intval($m['shake_id']);
        $m['id'] = intval($m['id']);
        $m['rotate_id'] = $m['id'];
        $m['mid'] = $m['id'];
        $m['sex'] = $m['sex'] == 'F' ? 2 : 1;

        $member_list[$index] = $m;

        if ($m['total'] == 100) {
            $response['data']['status'] = -1;
            //关闭活动
            $cycle_data = array(
                'end_time' => time(),
                'status' => 2
            );

            if($db->autoUpdate('cycle', $cycle_data, '`id`='.$cycle_id))
            {
                $get_allow_repeat = 'select `allow_repeat` from '.$db->table('scene').' where `id`='.$scene_id;
                $allow_repeat = $db->fetchOne($get_allow_repeat);

                //排名并清理排名
                $get_account_list = 'select `account` from ' . $db->table('shake') . ' where `cycle`=' . $cycle_id . ' order by `end_time` ASC, `total` DESC limit 3';
                $account_list = $db->fetchAll($get_account_list);
                if($account_list)
                {
                    $position = 1;
                    while($reward_account = array_shift($account_list))
                    {
                        $reward_account = $reward_account['account'];
                        $shake_data = array(
                            'goal' => $position
                        );

                        if($db->autoUpdate('shake', $shake_data, '`account`=\''.$reward_account.'\''))
                        {
                            $cycle_reward = array(
                                'account' => $reward_account,
                                'position' => $position++,
                                'add_time' => time(),
                                'cycle_id' => $cycle_id
                            );

                            $db->autoInsert('cycle_reward', array($cycle_reward));
                        }
                    }
                }

                $db->autoUpdate('shake', array('progress'=>0, 'total'=>0));
            }
        }
    }
} else {
    $member_list = array();
}
$response['sql'] = $get_member_list;
$response['sql1'] = $get_cycle;

$response['data']['players'] = $member_list;

$db->autoUpdate('shake', array('progress'=>0));

echo json_encode($response);