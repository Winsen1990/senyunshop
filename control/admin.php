<?php
/**
 * 管理员
 * @author 王仁欢
 * @date 2015-08-07
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'admin/';
assign('subTitle', '管理员');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

//编辑管理员
if( 'edit' == $opera ) {
    if( !check_purview('pur_admin_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getPOST('current-account'));

    $password = trim(getPOST('passwordNoRequired'));
    $role_id = getPOST('roleId');
    $role_id = intval($role_id);
    $name = trim(getPOST('screenName'));
    $sex = trim(getPOST('sex'));
    $email = trim(getPOST('email'));
    $mobile = trim(getPOST('phone'));
    $photo = '';


    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    } else {
        $account = $db->escape(htmlspecialchars($account));
    }

    $get_admin = 'select * from `'.DB_PREFIX.'platform_admin` where account = \''.$account.'\' limit 1';
    $admin = $db->fetchRow($get_admin);
    if( empty($admin) ) {
        show_system_message('管理员不存在', array());
        exit;
    }

    if( $admin['role_id'] == 1 ) {
        show_system_message('不能对超级管理员进行修改', array());
        exit;
    }


    if( '' != $password ) {
        $password = md5($password.PASSWORD_END);
    }

    if( 0 >= $role_id ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( '' == $name ) {
        $name = $account;
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

    if( '' == $sex ) {
        $sex = 'M';
    } else {
        if( strpos('M|F', $sex) !== false ) {
            $sex = $db->escape($sex);
        } else {
            $sex = 'M';
        }
    }

    if( '' == $email ) {
        show_system_message('电子邮箱不能为空', array());
        exit;
    } else {
        $email = $db->escape(htmlspecialchars($email));
        $check_email = 'select `account` from `'.DB_PREFIX.'platform_admin` where `email`=\''.$email.'\' and `account`<>\''.$account.'\'';
        if( $db->fetchRow($check_email )) {
            show_system_message('电子邮箱已被占用，请使用其他邮箱', array());
            exit;
        }
    }

    $data = array(
        'email' => $email,
        'role_id' => $role_id,
    );
    if( '' != $name ) {
        $data['name'] = $name;
    }
    if( '' != $password ) {
        $data['password'] = $password;
    }

    $where = 'account = \''.$account.'\'';
    $order = '';
    $limit = '1';

    if( $db->autoUpdate('platform_admin', $data, $where, $order, $limit) ) {
        show_system_message('修改管理员成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，稍后重试', array());
        exit;
    }

}


//添加管理员
if( 'add' == $opera ) {
    if( !check_purview('pur_admin_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getPOST('userName'));
    $password = trim(getPOST('password'));
    $role_id = getPOST('roleId');
    $role_id = intval($role_id);
    $name = trim(getPOST('screenName'));
    $sex = trim(getPOST('sex'));
    $email = trim(getPOST('email'));
    $mobile = trim(getPOST('phone'));
    $photo = '';

    if( '' == $account ) {
        show_system_message('请填写账号', array());
        exit;
    } else {
        $account = $db->escape(htmlspecialchars($account));
        $check_account = 'select `account` from `'.DB_PREFIX.'platform_admin` where `account`=\''.$account.'\'';
        if( $db->fetchRow($check_account) ) {
            show_system_message('该账号已被注册，请使用其他账号进行注册', array());
            exit;
        }
    }

    if( '' == $password ) {
        show_system_message('请填写密码', array());
        exit;
    } else {
        $password = md5($password.PASSWORD_END);
    }

    if( 0 >= $role_id ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( '' == $name ) {
        $name = $account;
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

    if( '' == $sex ) {
        $sex = 'M';
    } else {
        if(strpos('M|F', $sex) !== false) {
            $sex = $db->escape($sex);
        } else {
            $sex = 'M';
        }
    }

    if( '' == $email ) {
        show_system_message('电子邮箱不能为空', array());
        exit;
    } else {
        $email = $db->escape(htmlspecialchars($email));
        $checkEmail = 'select `account` from `'.DB_PREFIX.'platform_admin` where `email`=\''.$email.'\'';
        if($db->fetchRow($checkEmail)) {
            show_system_message('电子邮箱已被占用，请使用其他邮箱', array());
            exit;
        }
    }

//    if( '' == $mobile ) {
//        show_system_message('手机号码不能为空', array());
//        exit;
//    } else {
//        $mobile = $db->escape(htmlspecialchars($mobile));
//        $checkMobile = 'select `account` from `'.DB_PREFIX.'platform_admin` where `mobile`=\''.$mobile.'\'';
//        if($db->fetchRow($checkMobile)) {
//            show_system_message('手机号码已被占用，请使用其他号码', array());
//            exit;
//        }
//    }

    if( isset($_FILES['photo']) ) {
        $photo = upload($_FILES['photo']);
        if($photo['error'] == 0) {
            $photo = $photo['msg'];
        } else {
            show_system_message($photo['msg'], array());
            exit;
        }
    }

    $data = array(
        'account' => $account,
    );

    $data = array(
        'account' => $account,
        'password' => $password,
        'email' => $email,
        'name' => $name,
        'sex' => $sex,
        'role_id' => $role_id,
    );

    if($db->autoInsert('platform_admin', array($data))) {
        show_system_message('新增管理员成功', array(array('alt'=>'查看管理员列表', 'link'=>'admin.php')));
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

//===========================================================================


if( 'view' == $act ) {
    if( !check_purview('pur_admin_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_admin_list = 'select a.*, r.name as role_name from `'.DB_PREFIX.'platform_admin` as a';
    $get_admin_list .= ' left join '.DB_PREFIX.'platform_role as r on a.role_id = r.id';

    $admin_list = $db->fetchAll($get_admin_list);
    assign('adminList', $admin_list);

}

if( 'add' == $act ) {
    if( !check_purview('pur_admin_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_role_list = 'select `id`,`name` from `'.DB_PREFIX.'platform_role` where `id`<>1 order by `id` asc';
    $role_list = $db->fetchAll($get_role_list);

    if( empty($role_list) ) {
        show_system_message('系统尚未有管理员角色', array(array('alt' => '添加管理员角色', 'link' => 'role.php?act=add')));
        exit;
    }

    assign('roleList', $role_list);
}

if( 'edit' == $act ) {
    if( !check_purview('pur_admin_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getGET('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }
    $account = $db->escape($account);

    $get_admin = 'select * from `'.DB_PREFIX.'platform_admin` where account = \''.$account.'\' limit 1';
    $admin = $db->fetchRow($get_admin);
    if( empty($admin) ) {
        show_system_message('管理员不存在', array());
        exit;
    }

    if( $admin['role_id'] == 1 ) {
        show_system_message('不能对超级管理员进行修改', array());
        exit;
    }

    assign('admin', $admin);

    $get_role_list = 'select `id`,`name` from `'.DB_PREFIX.'platform_role` where `id`<>1 order by `id` asc';
    $role_list = $db->fetchAll($get_role_list);

    if( empty($role_list) ) {
        show_system_message('系统尚未有管理员角色', array(array('alt' => '添加管理员角色', 'link' => 'role.php?act=add')));
        exit;
    }

    assign('roleList', $role_list);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_admin_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $account = trim(getGET('account'));
    if( '' == $account ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_admin = 'select * from `'.DB_PREFIX.'platform_admin` where `account` = \''.$account.'\' limit 1';
    $admin = $db->fetchRow($get_admin);
    if( empty($admin) ) {
        show_system_message('管理员不存在', array());
        exit;
    }

    if( $admin['role_id'] == 1 ) {
        show_system_message('不能删除超级管理员', array());
        exit;
    }

    $delete_admin = 'delete from `'.DB_PREFIX.'platform_admin` where `account` = \''.$account.'\' limit 1';
    if( $db->delete($delete_admin) ) {
        show_system_message('成功删除管理员', array());
        exit;
    } else {
        show_system_message('系统繁忙，稍后重试', array());
        exit;
    }

}

$template .= $act.'.phtml';
$smarty->display($template);
