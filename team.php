<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午4:48
 */
include 'library/init.inc.php';

$get_user_info = 'select `path`,`id`,`account`,`level_id`,`nickname`,`headimg` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';

$user_info = $db->fetchRow($get_user_info);
assign('user_info', $user_info);

$get_member_list = 'select `nickname`,`account`,`add_time`,`mobile`,`headimg` from '.$db->table('member').' where `parent_id`='.$user_info['id'].' order by (`reward`+`reward_await`) DESC';

$member_list_tmp = $db->fetchAll($get_member_list);

$member_list = array();
if($member_list_tmp)
{
    foreach ($member_list_tmp as $member)
    {
        $mobile = $member['mobile'];
        $mobile = substr($mobile, 0, 3) . '****' . substr($mobile, -4);

        $member['mobile'] = $mobile;
        $member_list[] = $member;
    }
}

$get_member_list2 = 'select `nickname`,`account`,`add_time`,`mobile`,`headimg` from '.$db->table('member').' where `path` regexp \'^'.$user_info['path'].'[0-9]+,[0-9]+,$\' order by (`reward`+`reward_await`) DESC';

$member_list2_tmp = $db->fetchAll($get_member_list2);

$member_list2 = array();
if($member_list2_tmp)
{
    foreach ($member_list2_tmp as $member)
    {
        $mobile = $member['mobile'];
        $mobile = substr($mobile, 0, 3) . '****' . substr($mobile, -4);

        $member['mobile'] = $mobile;
        $member_list2[] = $member;
    }
}

assign('member_list', $member_list);
assign('member_list2', $member_list2);
assign('title', '我的团队');

$smarty->display('team.phtml');