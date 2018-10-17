<?php
/**
 * 会员管理
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:04
 */
include 'library/init.inc.php';

global $db, $log, $config;

business_base_init();

$template = 'member/';
assign('subTitle', '会员管理');

$action = 'edit|view|delete|revoke|network|upgrade|downgrade';
$operation = 'edit|refresh|get_forever_qr_code';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));

$level_str = array(
    0 => '普通会员',
    1 => '商业会员',
    2 => '招商会员',
);

//===========================================================================
//刷新微信信息
if('refresh' == $opera) {
    $response = ['error' => -1, 'message' => ''];

    if(!check_purview('pur_member_edit', $_SESSION['business_purview'])) {
        $response['message'] = '权限不足';
        echo json_encode($response);
        exit;
    }

    $account = trim(getPOST('account'));

    if(empty($account)) {
        $response['message'] = '参数错误';
    } else {
        $check_member = 'select `account`,`expired`,`openid` from '.$db->table('member').' where `account`=\''.$account.'\'';
        $member = $db->fetchRow($check_member);
        if(empty($member)) {
            $response['message'] = '用户不存在';
        } else {
            $access_token = get_access_token($config['appid'], $config['appsecret']);
            $user_info = get_user_wechat_info($access_token, $member['openid']);

            if($user_info) {
                if(isset($user_info->nickname)) {
                    $response['error'] = 0;
                    $response['message'] = '同步微信资料成功';
                } else {
                    if(isset($user_info->subscribe) && $user_info->subscribe == 0) {
                        $response['message'] = '该用户已取消关注';
                    } else {
                        $response['message'] = $user_info->errmsg;
                        $response['error'] = $user_info->errcode;
                    }
                }
            } else {
                $response['message'] = '获取信息失败，请稍后再试';
            }
        }
    }

    echo json_encode($response);
    exit;
}

//生成永久二维码
if('get_forever_qr_code' == $opera) {
    $response = ['error' => -1, 'message' => ''];

    if(!check_purview('pur_member_edit', $_SESSION['business_purview'])) {
        $response['message'] = '权限不足';
        echo json_encode($response);
        exit;
    }

    $account = trim(getPOST('account'));

    if(empty($account)) {
        $response['message'] = '参数错误';
    } else {
        $check_member = 'select `account`,`expired`,`openid` from '.$db->table('member').' where `account`=\''.$account.'\'';
        $member = $db->fetchRow($check_member);
        if(empty($member)) {
            $response['message'] = '用户不存在';
        } else {
            if($member['expired'] < 0) {
                $response['message'] = '该用户已申请有永久二维码';
            } else {
                $access_token = get_access_token($config['appid'], $config['appsecret']);
                $qr_code = get_forever_qrcode($member['openid'], $access_token);

                if($qr_code) {
                    $response['error'] = 0;
                    $response['message'] = '永久二维码申请成功';
                } else {
                    $response['message'] = '永久二维码申请失败，请稍后再试';
                }
            }
        }
    }

    echo json_encode($response);
    exit;
}

//修改会员信息
if('edit' == $opera) {
    if( !check_purview('pur_member_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getPOST('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $account = $db->escape($account);
    $get_member = 'select * from '.$db->table('member').' where account = \''.$account.'\' limit 1';
    $member = $db->fetchRow($get_member);
    if( empty($member) ) {
        show_system_message('会员不存在', array());
        exit;
    }

    $mobile = trim(getPOST('mobile'));
    $reward = floatval(getPOST('reward'));
    $integral = floatval(getPOST('integral'));
    $level_id = intval(getPOST('level_id'));

    $real_name = trim(getPOST('real_name'));

    if( '' == $mobile ) {
        show_system_message('手机不能为空', array());
        exit;
    }
    if( strlen($mobile) != 11 ) {
        show_system_message('手机格式不正确', array());
        exit;
    }
    $mobile = $db->escape($mobile);

    $reward = ( $reward < 0 ) ? 0 : $reward;
    $integral = ($integral < 0 ) ? 0 : $integral;

    if( !in_array($level_id, array(0, 1, 2)) ) {
        $level_id = 0;
    }

    $data = array(
        'mobile' => $mobile,
        'reward' => $reward,
        'integral' => $integral,
        'level_id' => $level_id,
    );
    $where = 'account = \''.$account.'\'';
    if( $db->autoUpdate('member', $data, $where) ) {
        if($level_id >= 1) {
            if($member['openid'] != '') {
                notify_member($member['openid'], '您已成为'.$level_str[$level_id]);
            }
        }

        if( $reward != $member['reward'] || $integral != $member['integral'] ) {
            $data = array(
                'account' => $account,
                'add_time' => time(),
                'balance' => $member['balance'],
                'reward' => $reward - $member['reward'],
                'integral' => $integral - $member['integral'],
                'remark' => '修改会员资料',
                'reward_await' => $member['reward_await'],
                'integral_await' => $member['integral_await'],
                'operator' => $_SESSION['account'],
            );
            $db->autoInsert('member_exchange_log', array($data));
        }

        $real_name = empty($real_name) ? '' : $real_name;
        $has_member_info = $db->fetchOne('select `account` from '.$db->table('member_info').' where `account`=\''.$account.'\'');

        if($has_member_info) {
            //更新用户信息
            $db->autoUpdate('member_info', [
                'real_name' => $real_name
            ], '`account`=\''.$account.'\'');
        } else {
            //新建用户信息
            $db->autoInsert('member_info', [
                [
                    'account' => $account,
                    'real_name' => $real_name
                ]
            ]);
        }

        show_system_message('更新成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//===========================================================================

//
if( 'view' == $act ) {
    if( !check_purview('pur_member_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $where = '';
    $account = trim(getGET('account'));
    if($account != '')
    {
        $account = $db->escape($account);
        $where .= ' and `account`=\''.$account.'\'';
    }
    $page = intval(getGET('page'));
    $count = intval(getGET('count'));
    $count_expected = array(10, 25, 50, 100);
    if( !in_array($count, $count_expected) ) {
        $count = 10;
    }

    $get_total = 'select count(*) from '.$db->table('member').' where 1'.$where;
    $total = $db->fetchOne($get_total);
    $total_page = ceil($total / $count);

    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( $page <= 0 ) ? 1 : $page;

    $offset = ($page - 1) * $count;

    create_pager($page, $total_page, $total);
    assign('count', $count);

    $get_member_list = 'select `expired`,`openid`,`id`, `sex`, `account`, `nickname`, `mobile`, `email`, `add_time`, `leave_time`, `status`, `level_id`,`real_name`';
    $get_member_list .= ' from '.$db->table('member');
    $get_member_list .= ' left join '.$db->table('member_info').' using (`account`)';
    $get_member_list .= ' where 1 '.$where.' order by `add_time` desc';
    $get_member_list .= ' limit '.$offset.','.$count;

    $member_list = $db->fetchAll($get_member_list);
    if( $member_list ) {
        foreach ($member_list as $key => $value) {
            if (empty($value['leave_time']) && !empty($value['openid'])) {
                $member_list[$key]['subscribed'] = '已关注';
            } else {
                $member_list[$key]['subscribed'] = '未关注';
            }

            if($value['sex'] == '' || $value['sex'] == 'N')
            {
                $member_list[$key]['sex_str'] = '保密';
            } elseif( $value['sex'] == 'M' ) {
                $member_list[$key]['sex_str'] =  '男';
            } elseif( $value['sex'] == 'F' ) {
                $member_list[$key]['sex_str'] =  '女';
            } elseif( $value['sex'] == '0' ) {
                $member_list[$key]['sex_str'] = '保密';
            } elseif( $value['sex'] == '1' ) {
                $member_list[$key]['sex_str'] =  '男';
            } elseif( $value['sex'] == '2' ) {
                $member_list[$key]['sex_str'] =  '女';
            }
            $member_list[$key]['level_str'] = $level_str[$value['level_id']];
            $member_list[$key]['sex'] = $member_list[$key]['sex_str'];
        }
    }
    assign('member_list', $member_list);
}

if( 'edit' == $act ) {
    if( !check_purview('pur_member_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getGET('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $account = $db->escape($account);
    $get_member = 'select a.*,if(b.real_name is null, \'\', b.real_name) as real_name from '.$db->table('member').' as a left join '.$db->table('member_info').' as b using(`account`) where account = \''.$account.'\' limit 1';
    $member = $db->fetchRow($get_member);
    if( empty($member) ) {
        show_system_message('会员不存在', array());
        exit;
    }

    assign('member', $member);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_member_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getGET('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $account = $db->escape($account);
    $get_member = 'select * from '.$db->table('member').' where account = \''.$account.'\' limit 1';
    $member = $db->fetchRow($get_member);
    if( empty($member) ) {
        show_system_message('会员不存在', array());
        exit;
    }

    $delete_member = 'update '.$db->table('member').' set status = 0  where account = \''.$account.'\' limit 1';
    if( $db->update($delete_member) ) {
        show_system_message('会员'.$account.'已被拉黑', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}


if( 'revoke' == $act ) {
    if( !check_purview('pur_member_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getGET('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $account = $db->escape($account);
    $get_member = 'select * from '.$db->table('member').' where account = \''.$account.'\' limit 1';
    $member = $db->fetchRow($get_member);
    if( empty($member) ) {
        show_system_message('会员不存在', array());
        exit;
    }

    $delete_member = 'update '.$db->table('member').' set status = 1  where account = \''.$account.'\' limit 1';
    if( $db->update($delete_member) ) {
        show_system_message('会员'.$account.'已撤销拉黑', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'network' == $act ) {
    if( !check_purview('pur_member_network', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getGET('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $account = $db->escape($account);
    $get_member = 'select * from '.$db->table('member').' where account = \''.$account.'\' limit 1';
    $member = $db->fetchRow($get_member);
    if( empty($member) ) {
        show_system_message('会员不存在', array());
        exit;
    }
    if( $member['parent_id'] != 0 ) {
        $get_parent = 'select * from '.$db->table('member').' where id = \''.$member['parent_id'].'\' limit 1';
        $parent = $db->fetchRow($get_parent);

        if( $parent['parent_id'] != 0 ) {
            $get_grand = 'select * from '.$db->table('member').' where id = \''.$parent['parent_id'].'\' limit 1';
            $grand = $db->fetchRow($get_grand);
        }
    }


    $data = array();
    if( !empty($grand) ) {
        $data = array(
            'name' => '二级推荐人-'.$grand['account'].'-'.$grand['nickname'],
            'account' => $grand['account'],
            'parentId' => $grand['parent_id'],
            'isParent' => true,
            'open' => true,
        );
        $data['children'] = array(array(
            'name' => '一级推荐人-'.$parent['account'].'-'.$parent['nickname'],
            'account' => $parent['account'],
            'parentId' => $parent['parent_id'],
            'isParent' => true,
            'open' => true,
            'children' => array(array(
                'name' => $member['account'].'-'.$member['nickname'],
                'account' => $member['account'],
                'parentId' => $member['parent_id'],
                'isParent' => true,
            )),
        ));
    } else if( !empty($parent) ) {
        $data = array(
            'name' => '一级推荐人-'.$parent['account'].'-'.$parent['nickname'],
            'account' => $parent['account'],
            'parentId' => $parent['parent_id'],
            'isParent' => true,
            'open' => true,
            'children' => array(array(
                'name' => $member['account'].'-'.$member['nickname'],
                'account' => $member['account'],
                'parentId' => $member['parent_id'],
                'isParent' => true,
            )),
        );
    } else {
        $data = array(
            'name' => $member['account'].'-'.$member['nickname'],
            'account' => $member['account'],
            'parentId' => $member['parent_id'],
            'isParent' => true,
        );
    }
    assign('account', $member['account']);
    assign('data', json_encode(array($data)));

}

assign('act', $act);
$template .= $act.'.phtml';
$smarty->display($template);