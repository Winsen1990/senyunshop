<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/12/10
 * Time: 下午6:51
 */
include '../../library/init.inc.php';

$response = array('ret'=>0, 'data'=>array('parters'=>array()),'count'=>0);

$scene_id = intval(getGET('scene_id'));

if($scene_id > 0)
{
    $get_cycle = 'select `id` from '.$db->table('cycle').' where `status`=0 and `scene_id`='.$scene_id;
    $cycle = $db->fetchOne($get_cycle);

    $count = intval(getGET('c'));
    if($count == 0)
    {
        $_SESSION['account_str'] = '';
    }

    $get_parters = 'select if(m.`headimg`<>\'\', m.`headimg`,\''.BASE_DIR.'/themes/sbx/images/wang.jpg\') as `avatar`, m.`id` as `mid`,'.
                   $scene_id.' as `scene_id`, m.`nickname` as `nick_name`,s.`add_time` as `dt_join`,m.`sex` as `sex` from '.$db->table('shake').
                   ' as s join '.$db->table('member').' as m using(`account`) where s.`cycle`='.$cycle.' ';

    if($_SESSION['account_str'] != '')
    {
        $get_parters .= 'and m.`id` not in ('.$_SESSION['account_str'].'0)';
    }

    $get_parters .= ' limit 1';

    $parters = $db->fetchAll($get_parters);
    if(!$parters)
    {
        $parters = array();
    } else {
        $needle = ','.$parters[0]['mid'].',';
        if($_SESSION['account_str'] == '' || strpos($needle, $_SESSION['account_str']) === false)
        {
            $_SESSION['account_str'] .= $parters[0]['mid'] . ',';
        }
    }
    $response['data']['parters'] = $parters;
    $response['sql'] = $get_parters;
    $get_count = 'select count(*) from '.$db->table('shake').' where `cycle`='.$cycle;
    $response['data']['count'] = $db->fetchOne($get_count);
}

echo json_encode($response);