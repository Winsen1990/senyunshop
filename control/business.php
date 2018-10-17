<?php
/**
 * 商户管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-21
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'business/';
assign('subTitle', '商户管理');

$action = 'view|exam|reject|edit|auth|detail|frozen|auth_reject';
$operation = 'auth|exam|reject|auth_reject';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

if( 'auth' == $opera ) {
    if( !check_purview('pur_business_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $business_account = trim(getPOST('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);

    $get_business = 'select a.*, i.name as industry_name, cate.name as cate_name from '.$db->table('business').' as a';
    $get_business .= ' left join '.$db->table('industry').' as i on a.industry_id = i.id';
    $get_business .= ' left join '.$db->table('category').' as cate on a.category_id = cate.id';
    $get_business .= ' where a.business_account = \''.$business_account.'\' limit 1';
    $business = $db->fetchRow($get_business);

    if( empty($business) ) {
        show_system_message('商户不存在', array());
        exit;
    }

    $get_auth = 'select a.*, i.name as industry_name, cate.name as cate_name from '.$db->table('auth').' as a';
    $get_auth .= ' left join '.$db->table('industry').' as i on a.industry_id = i.id';
    $get_auth .= ' left join '.$db->table('category').' as cate on a.category_id = cate.id';
    $get_auth .= ' where a.business_account = \''.$business_account.'\' and a.status = 0';
    $get_auth .= ' order by a.add_time desc limit 1';
    $auth = $db->fetchRow($get_auth);

    if( empty($auth) ) {
        show_system_message('商户认证记录不存在', array());
        exit;
    }

    $db->begin();
    $transaction = true;

    $data = array(
        'company' => $auth['company'],
        'industry_id' => $auth['industry_id'],
        'category_id' => $auth['category_id'],
        'contact' => $auth['contact'],
        'mobile' => $auth['mobile'],
        'email' => $auth['email'],
        'license' => $auth['license'],
        'identity' => $auth['identity'],
    );
    $where = 'business_account = \''.$business_account.'\'';

    if( !$db->autoUpdate('business', $data, $where) ) {
        $transaction = false;
    }

    $update_auth = 'update '.$db->table('auth').' set status = 1';
    $update_auth .= ' where business_account = \''.$business_account.'\' and status = 0 limit 1';

    if( !$db->update($update_auth) ) {
        $transaction = false;
    }

    //已系统消息形式返回驳回理由，返回到
    $data = array(
        'title' => '信息认证',
        'content' => '您的信息认证已通过审核，数据已更新。',
        'account' => $business['account'],
        'business_account' => $business['business_account'],
        'add_time' => time(),
        'status' => 0,  //未读
    );
    if( !$db->autoInsert('message', array($data)) ) {
        $transaction = false;
    }

    if( $transaction ) {
        $db->commit();
        //删除图片
        if( $auth['license'] != $business['license'] && file_exists(realpath('..'.$business['license'])) ) {
            @unlink(realpath('..'.$business['license']));
        }
        if( $auth['identity'] != $business['identity'] && file_exists(realpath('..'.$business['identity'])) ) {
            @unlink(realpath('..'.$business['identity']));
        }
        $links = array(
            array('alt' => '商户列表', 'link' => 'business.php'),
            array('alt' => '信息认证', 'link' => 'business.php?act=auth'),
        );
        show_system_message('信息认证已通过', $links);
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'exam' == $opera ) {
    if( !check_purview('pur_business_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $business_account = trim(getPOST('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);

    $get_business = 'select a.*, i.name as industry_name, cate.name as cate_name from '.$db->table('business').' as a';
    $get_business .= ' left join '.$db->table('industry').' as i on a.industry_id = i.id';
    $get_business .= ' left join '.$db->table('category').' as cate on a.category_id = cate.id';
    $get_business .= ' where a.business_account = \''.$business_account.'\' limit 1';
    $business = $db->fetchRow($get_business);

    if( empty($business) ) {
        show_system_message('商户不存在', array());
        exit;
    }

    $update_business = 'update '.$db->table('business').' set status = 2';
    $update_business .= ' where business_account = \''.$business_account.'\' and status = 1 limit 1';

    if( $db->update($update_business) ) {
        //系统通知
        $data = array(
            'title' => '商户审核',
            'content' => '您已成功入驻分销平台，祝您生意兴隆！',
            'account' => $business['account'],
            'business_account' => $business['business_account'],
            'add_time' => time(),
            'status' => 0,  //未读
        );

        if( !$db->autoInsert('message', array($data)) ) {
            $transaction = false;
        }

        $links = array(
            array('alt' => '商户列表', 'link' => 'business.php'),
        );
        show_system_message('审核通过', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'reject' == $opera ) {
    if( !check_purview('pur_business_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $business_account = trim(getPOST('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);
    $get_business = 'select * from '.$db->table('business');
    $get_business .= ' where business_account = \''.$business_account.'\' and status = 1 limit 1';

    $business = $db->fetchRow($get_business);
    if( empty($business) ) {
        show_system_message('商户不存在', array());
        exit;
    }

    $reason = trim(getPOST('reason'));
    if( '' == $reason ) {
        $reason = '您的开店申请审核不通过，请重新提交';
    } else {
        $reason = $db->escape($reason);
    }

    $db->begin();
    $transaction = true;

    $update_business = 'update '.$db->table('business').' set status = 0';
    $update_business .= ' where business_account = \''.$business_account.'\' limit 1';
    if( !$db->update($update_business) ) {
        $transaction = false;
    }
    //已系统消息形式返回驳回理由，返回到
    $data = array(
        'title' => '商户审核',
        'content' => $reason,
        'account' => $business['account'],
        'business_account' => $business['business_account'],
        'add_time' => time(),
        'status' => 0,  //未读
    );

    if( !$db->autoInsert('message', array($data)) ) {
        $transaction = false;
    }

    if( $transaction ) {
        $db->commit();
        $links = array(
            array('link' => 'business.php', 'alt' => '商户列表'),
        );
        show_system_message('入驻申请已驳回', $links);
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'auth_reject' == $opera ) {
    if( !check_purview('pur_business_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $business_account = trim(getPOST('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);
    $get_business = 'select * from '.$db->table('business');
    $get_business .= ' where business_account = \''.$business_account.'\' limit 1';

    $business = $db->fetchRow($get_business);
    if( empty($business) ) {
        show_system_message('商户不存在', array());
        exit;
    }

    $reason = trim(getPOST('reason'));
    if( '' == $reason ) {
        $reason = '您的认证信息审核不通过，请重新提交';
    } else {
        $reason = $db->escape($reason);
    }

    $db->begin();
    $transaction = true;

    $update_business = 'update '.$db->table('auth').' set status = 2';
    $update_business .= ' where business_account = \''.$business_account.'\' limit 1';
    if( !$db->update($update_business) ) {
        $transaction = false;
    }
    //已系统消息形式返回驳回理由，返回到
    $data = array(
        'title' => '信息认证',
        'content' => $reason,
        'account' => $business['account'],
        'business_account' => $business['business_account'],
        'add_time' => time(),
        'status' => 0,  //未读
    );
    if( !$db->autoInsert('message', array($data)) ) {
        $transaction = false;
    }

    if( $transaction ) {
        $db->commit();
        $links = array(
            array('link' => 'business.php', 'alt' => '商户列表'),
        );
        show_system_message('信息认证已驳回', $links);
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//===========================================================================

//列表
if( 'view' == $act ) {
    if( !check_purview('pur_business_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $where = '';

    $status = intval(getGET('status'));
    if( $status != 0 ) {
        switch($status) {
            case 1:
                $where .= ' where a.status = 1';
                break;
            case 2:
                $where .= ' where a.status = 2';
                break;
            case 3:
                $where .= ' where a.status = 3';
                break;
            case 4:
                $where .= ' where a.status = 4';
                break;
            default:
                $where .= ' where a.status > 0 and a.status <= 4';
                break;
        }
    } else {
        $where .= ' where a.status > 0 and a.status <= 4';
    }
    assign('status', $status);

    $keyword = trim(getGET('keyword'));
    if( '' != $keyword ) {
        $keyword = $db->escape($keyword);
        $where .= ' and a.shop_name like \'%'.$keyword.'%\' or a.company like \'%'.$keyword.'%\'';
    }
    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('business').' as a';
    $get_total .= $where;
    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);
    assign('keyword', $keyword);

    $offset = ($page - 1) * $count;

    $get_business_list = 'select a.*, p.province_name, c.city_name, d.district_name, g.group_name, cate.name as cate_name, i.name as industry_name';
    $get_business_list .= ' from '.$db->table('business').' as a';
    $get_business_list .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_business_list .= ' left join '.$db->table('city').' as c on a.city = c.id';
    $get_business_list .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_business_list .= ' left join '.$db->table('group').' as g on a.group = g.id';
    $get_business_list .= ' left join '.$db->table('category').' as cate on a.category_id = cate.id';
    $get_business_list .= ' left join '.$db->table('industry').' as i on a.industry_id = i.id';
    $get_business_list .= $where;
    $get_business_list .= ' limit '.$offset.','.$count;

    $business_list = $db->fetchAll($get_business_list);

    $status_array = array(
        1 => '待审核',
        2 => '运营中',
        3 => '已冻结',
        4 => '待下单',
    );
    if( $business_list ) {
        foreach( $business_list as $key => $value ) {
            $business_list[$key]['status_str'] = $status_array[$value['status']];
        }
    }
    assign('business_list', $business_list);


    if( $status == 1 ) {
        $exam_count = $total;
    } else {
        $get_exam_count = 'select count(*) from '.$db->table('business').' where status = 1';
        $exam_count = $db->fetchOne($get_exam_count);
    }
    assign('exam_count', $exam_count);

    $get_auth_count = 'select count(*) from '.$db->table('auth').' where status = 0';
    $auth_count = $db->fetchOne($get_auth_count);
    assign('auth_count', $auth_count);
}

//认证信息列表
if( 'auth' == $act ) {
    if( !check_purview('pur_business_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $where = '';

    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('auth').' where `status` = 0';
    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);

    $offset = ($page - 1) * $count;

    $get_auth_list = 'select a.*, i.name as industry_name, c.name as cate_name from '.$db->table('auth').' as a';
    $get_auth_list .= ' left join '.$db->table('industry').' as i on a.industry_id = i .id';
    $get_auth_list .= ' left join '.$db->table('category').' as c on a.category_id = c.id';
    $get_auth_list .= ' where `status` = 0';
    $get_auth_list .= ' order by add_time desc';
    $get_auth_list .= ' limit '.$offset.','.$count;

    $auth_list = $db->fetchAll($get_auth_list);

    if( $auth_list ) {
        foreach( $auth_list as $key => $value ) {
            $auth_list[$key]['add_time_str'] = date('Y-m-d H:i:s', $value['add_time']);
//            $auth_list[$key][''];
        }
    }
    assign('auth_list', $auth_list);

    $get_exam_count = 'select count(*) from '.$db->table('business').' where status = 0';
    $exam_count = $db->fetchOne($get_exam_count);
    assign('exam_count', $exam_count);

    assign('auth_count', $total);

}

//认证信息详情
if( 'detail' == $act ) {
    if( !check_purview('pur_business_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $business_account = trim(getGET('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);

    $get_business = 'select a.*, i.name as industry_name, cate.name as cate_name from '.$db->table('business').' as a';
    $get_business .= ' left join '.$db->table('industry').' as i on a.industry_id = i.id';
    $get_business .= ' left join '.$db->table('category').' as cate on a.category_id = cate.id';
    $get_business .= ' where a.business_account = \''.$business_account.'\' limit 1';
    $business = $db->fetchRow($get_business);

    if( empty($business) ) {
        show_system_message('商户不存在', array());
        exit;
    }

    $get_auth = 'select a.*, i.name as industry_name, cate.name as cate_name from '.$db->table('auth').' as a';
    $get_auth .= ' left join '.$db->table('industry').' as i on a.industry_id = i.id';
    $get_auth .= ' left join '.$db->table('category').' as cate on a.category_id = cate.id';
    $get_auth .= ' where a.business_account = \''.$business_account.'\' and a.status = 0';
    $get_auth .= ' order by a.add_time desc limit 1';
    $auth = $db->fetchRow($get_auth);

    if( empty($auth) ) {
        show_system_message('商户认证记录不存在', array());
        exit;
    }

    assign('business', $business);
    assign('auth', $auth);
}

//入驻审核
if( 'exam' == $act ) {
    if( !check_purview('pur_business_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $business_account = trim(getGET('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);

    $get_business = 'select a.*, p.province_name, city.city_name, d.district_name, g.group_name, cate.name as cate_name, i.name as industry_name';
    $get_business .= ' from '.$db->table('business').' as a';
    $get_business .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_business .= ' left join '.$db->table('city').' as city on a.city = city.id';
    $get_business .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_business .= ' left join '.$db->table('group').' as g on a.group = g.id';
    $get_business .= ' left join '.$db->table('industry').' as i on a.industry_id = i.id';
    $get_business .= ' left join '.$db->table('category').' as cate on a.category_id = cate.id';
    $get_business .= ' where a.business_account = \''.$business_account.'\' limit 1';
    $business = $db->fetchRow($get_business);
    if( empty($business_account) ) {
        show_system_message('商户不存在', array());
        exit;
    }

    $temp = $business['province_name'].' '.$business['city_name'].' '.$business['district_name'];
    if( isset($business['group_name']) ) {
        $temp .= ' '.$business['group_name'];
    }
    $temp .= ' ';
    $business['address'] = $temp . $business['address'];
    assign('business', $business);
}

//冻结、解除冻结
if( 'frozen' == $act ) {
    if( !check_purview('pur_business_frozen', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $business_account = trim(getGET('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);

    $get_business = 'select * from '.$db->table('business');
    $get_business .= ' where business_account = \''.$business_account.'\' and status = 2 or status = 3';
    $get_business .= ' limit 1';

    $business = $db->fetchRow($get_business);

    if( empty($business) ) {
        show_system_message('商户不存在', array());
        exit;
    }
    //冻结
    if( 2 == $business['status'] ) {
        $status = 3;
        $message = '成功冻结用户'.$business_account;
    }
    if( 3 == $business['status'] ) {
        $status = 2;
        $message = '成功为用户'.$business_account.'解冻';
    }
    $update_business = 'update '.$db->table('business').' set status = '.$status;
    $update_business .= ' where business_account = \''.$business_account.'\' limit 1';

    if( $db->update($update_business) ) {
        show_system_message($message, array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

//入驻审核驳回
if( 'reject' == $act ) {
    if( !check_purview('pur_business_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $business_account = trim(getGET('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);

    $get_business = 'select * from '.$db->table('business');
    $get_business .= ' where business_account = \''.$business_account.'\' and status = 1 limit 1';
    $business = $db->fetchRow($get_business);
    if( empty($business) ) {
        show_system_message('商户不存在', array());
        exit;
    }
    assign('business_account', $business_account);
}

//认证信息驳回
if( 'auth_reject' == $act ) {
    if( !check_purview('pur_business_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $business_account = trim(getGET('account'));
    if( '' == $business_account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $business_account = $db->escape($business_account);

    $get_business = 'select * from '.$db->table('business');
    $get_business .= ' where business_account = \''.$business_account.'\' and status = 2 limit 1';
    $business = $db->fetchRow($get_business);
    if( empty($business) ) {
        show_system_message('商户不存在', array());
        exit;
    }
    assign('business_account', $business_account);
}


$template .= $act.'.phtml';
$smarty->display($template);