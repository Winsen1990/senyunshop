<?php
/**
 * 管理员
 * @author 王仁欢
 * @date 2015-08-07
 * @version 1.0.0
 */

include 'library/init.inc.php';
global $db, $log, $config, $smarty;
business_base_init();

$template = 'admin/';
assign('subTitle', '管理员');

$action = 'edit|add|view';
$operation = 'edit|add|delete';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================
//删除管理员
if( 'delete' == $opera ) {
    $response = array('error' => -1, 'message' => '');

    if( !check_purview('pur_admin_del', $_SESSION['business_purview']) ) {
        throw new RestFulException('没有操作权限', -1);
    }

    $account = trim(getPOST('account'));
    if( '' == $account ) {
        $response['message'] = '参数错误';
    } else {
        $account = $db->escape($account);
        $get_admin = 'select * from '.$db->table('admin').' where business_account = \''.$_SESSION['business_account'].'\' and `account` = \''.$account.'\' limit 1';
        $admin = $db->fetchRow($get_admin);
        if( empty($admin) ) {
            $response['message'] = '管理员不存在';
        }
    }

    if($response['message'] == '') {
        $delete_admin = 'DELETE FROM ' . $db->table('admin') . ' WHERE business_account = \'' . $_SESSION['business_account'] . '\' AND `account` = \'' . $account . '\' LIMIT 1';
        if ($db->delete($delete_admin)) {
            $response['message'] = '成功删除管理员';
            $response['error'] = 0;
        } else {
            $response['message'] = '系统繁忙，稍后重试';
        }
    }

    echo json_encode($response);
    exit;
}

//编辑管理员
if( 'edit' == $opera ) {
    $response = array('error' => -1, 'message' => '', 'validate_message' => array());

    if( !check_purview('pur_admin_edit', $_SESSION['business_purview']) ) {
        throw new RestFulException('没有操作权限', -1);
    }

    $account = trim(getPOST('account'));
    $password = trim(getPOST('password'));
    $role_id = getPOST('role_id');
    $role_id = intval($role_id);
    $name = trim(getPOST('name'));
    $sex = trim(getPOST('sex'));
    $email = trim(getPOST('email'));
    $mobile = trim(getPOST('mobile'));
    $business_notice_enabled = trim(getPOST('business_notice_enabled')) == 'true' ? 1 : 0;

    if('' == $account) {
        $response['message'] = '参数错误';
    } else {
        $validate_account_conditions = array(
            'business_account' => $_SESSION['business_account'],
            'account' => $account
        );

        $validate_account = $db->find('admin', array('account'), $validate_account_conditions);

        if(empty($validate_account)) {
            $response['message'] = '管理员不存在';
        }
    }

    if( '' != $password ) {
        $password = md5($password.PASSWORD_END);
    }

    if( 0 >= $role_id ) {
        $response['validate_message']['role_id'] = '请选择角色';
    }

    if( '' == $name ) {
        $response['validate_message']['name'] = '请填写管理员名称';
    }

    if( '' == $sex ) {
        $sex = 'M';
    } else {
        if(strpos('M|F', $sex) === false) {
            $sex = 'M';
        }
    }

    if( '' == $email ) {
        $response['validate_message']['email'] = '电子邮箱不能为空';
    } else {
        $checkEmail = 'select `account` from '.$db->table('admin').' where  `email`=\''.$email.'\' and `account`!=\''.$account.'\'';
        if($db->fetchRow($checkEmail)) {
            $response['validate_message']['email'] = '电子邮箱已被使用，请使用其他邮箱';
        }
    }

    if( '' == $mobile ) {
        $response['validate_message']['mobile'] = '请填写手机号码';
    } else if (!is_mobile($mobile)) {
        $response['validate_message']['mobile'] = '手机号码格式错误';
    } else {
        $checkMobile = 'select `account` from '.$db->table('admin').' where `mobile`=\''.$mobile.'\' and `account`!=\''.$account.'\'';
        if($db->fetchRow($checkMobile)) {
            $response['validate_message']['mobile'] = '手机号码已被使用，请使用其他号码';
        }
    }

    if($response['message'] == '' && count($response['validate_message']) == 0) {
        $data = array(
            'email' => $email,
            'role_id' => $role_id,
            'name' => $name,
            'sex' => $sex,
            'mobile' => $mobile,
            'business_notice_enabled' => $business_notice_enabled
        );

        if ('' != $password) {
            $data['password'] = $password;
        }

        $where = '`business_account`=\''.$_SESSION['business_account'].'\' and `account`=\''.$account.'\'';

        if ($db->autoUpdate('admin', $data, $where, '', 1) !== false) {
            $response['message'] = '修改管理员成功';
            $response['error'] = 0;
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}


//添加管理员
if( 'add' == $opera ) {
    $response = array('error' => -1, 'message' => '', 'validate_message' => array());

    if( !check_purview('pur_admin_add', $_SESSION['business_purview']) ) {
        throw new RestFulException('没有操作权限', -1);
    }

    $account = trim(getPOST('account'));
    $password = trim(getPOST('password'));
    $role_id = getPOST('role_id');
    $role_id = intval($role_id);
    $name = trim(getPOST('name'));
    $sex = trim(getPOST('sex'));
    $email = trim(getPOST('email'));
    $mobile = trim(getPOST('mobile'));
    $business_notice_enabled = trim(getPOST('business_notice_enabled')) == 'true' ? 1 : 0;

    if( '' == $account ) {
        $response['validate_message']['account'] = '请填写管理员账号';
    } else if( !preg_match('/[a-zA-Z0-9_]+$/', $account) ) {
        $response['validate_message']['account'] = '帐号格式不正确';
    } else {
        $account .= '@'.$_SESSION['business_account'];
        $check_account = 'select `account` from '.$db->table('admin').' where business_account = \''.$_SESSION['business_account'].'\' and `account`=\''.$account.'\'';
        if( $db->fetchRow($check_account) ) {
            $response['validate_message']['account'] = '该账号已被注册，请使用其他账号进行注册';
        }
    }

    if( '' == $password ) {
        $response['validate_message']['password'] = '请填写密码';
    } else {
        $password = md5($password.PASSWORD_END);
    }

    if( 0 >= $role_id ) {
        $response['validate_message']['role_id'] = '请选择角色';
    }

    if( '' == $name ) {
        $response['validate_message']['name'] = '请填写管理员名称';
    }

    if( '' == $sex ) {
        $sex = 'M';
    } else {
        if(strpos('M|F', $sex) === false) {
            $sex = 'M';
        }
    }

    if( '' == $email ) {
        $response['validate_message']['email'] = '电子邮箱不能为空';
    } else {
        $checkEmail = 'select `account` from '.$db->table('admin').' where  `email`=\''.$email.'\'';
        if($db->fetchRow($checkEmail)) {
            $response['validate_message']['email'] = '电子邮箱已被使用，请使用其他邮箱';
        }
    }

    if( '' == $mobile ) {
        $response['validate_message']['mobile'] = '请填写手机号码';
    } else if (!is_mobile($mobile)) {
        $response['validate_message']['mobile'] = '手机号码格式错误';
    } else {
        $checkMobile = 'select `account` from '.$db->table('admin').' where `mobile`=\''.$mobile.'\'';
        if($db->fetchRow($checkMobile)) {
            $response['validate_message']['mobile'] = '手机号码已被使用，请使用其他号码';
        }
    }

    if($response['message'] == '' && count($response['validate_message']) == 0) {
        $data = array(
            'account' => $account,
            'password' => $password,
            'email' => $email,
            'name' => $name,
            'sex' => $sex,
            'role_id' => $role_id,
            'business_account' => $_SESSION['business_account'],
            'mobile' => $mobile,
            'business_notice_enabled' => $business_notice_enabled
        );

        if ($db->create('admin', $data)) {
            $response['message'] = '新增管理员成功';
            $response['error'] = 0;
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

//===========================================================================


if( 'view' == $act ) {
    if( !check_purview('pur_admin_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_admin_list = 'select a.*, r.name as role_name from '.$db->table('admin').' as a';
    $get_admin_list .= ' left join '.DB_PREFIX.'role as r on a.role_id = r.id';

    $admin_list = $db->fetchAll($get_admin_list);
    assign('adminList', $admin_list);

}

if( 'add' == $act ) {
    if( !check_purview('pur_admin_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_role_list = 'select `id`,`name` from '.$db->table('role').' where business_account = \''.$_SESSION['business_account'].'\' order by `id` asc';
    $role_list = $db->fetchAll($get_role_list);

    if( empty($role_list) ) {
        show_system_message('系统尚未有管理员角色', array(array('alt' => '添加管理员角色', 'link' => 'role.php?act=add')));
        exit;
    }

    assign('roleList', $role_list);
}

if( 'edit' == $act ) {
    if( !check_purview('pur_admin_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getGET('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_admin = 'select * from '.$db->table('admin').' where business_account = \''.$_SESSION['business_account'].'\' and account = \''.$account.'\' limit 1';
    $admin = $db->fetchRow($get_admin);
    if( empty($admin) ) {
        show_system_message('管理员不存在', array());
        exit;
    }

    assign('admin', $admin);

    $get_role_list = 'select `id`,`name` from '.$db->table('role').' where business_account = \''.$_SESSION['business_account'].'\' order by `id` asc';
    $role_list = $db->fetchAll($get_role_list);

    if( empty($role_list) ) {
        show_system_message('系统尚未有管理员角色', array(array('alt' => '添加管理员角色', 'link' => 'role.php?act=add')));
        exit;
    }

    assign('roleList', $role_list);
}

$template .= $act.'.phtml';
$smarty->display($template);