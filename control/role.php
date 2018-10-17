<?php
/**
 * 管理员角色
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-07
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'role/';
assign('subTitle', '管理员角色');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

//编辑管理员角色
if( 'edit' == $opera ) {
    if( !check_purview('pur_role_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getPOST('id');
    $id = intval($id);
    $name = trim(getPOST('name'));
    $purviews = getPOST('purviews');

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_role = 'select * from `'.DB_PREFIX.'platform_role` where id = \''.$id.'\' limit 1';
    $role = $db->fetchRow($get_role);
    if( empty($role) ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( '' == $name ) {
        show_system_message('请填写角色名', array());
        exit;
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

    $purview_value = array();

    foreach( $purviews as $key => $sub_purviews ) {
        if( array_key_exists($key, $purview) ) {
            $purview_value[$key] = array();
            foreach( $sub_purviews as $pur ) {
                if( in_array($pur, $purview[$key]) ) {
                    $purview_value[$key][] = $pur;
                }
            }
        }
    }
    $data = array(
        'name' => $name,
        'purview' => json_encode($purview_value),
    );
    $where = 'id = '.$id;
    $order = '';
    $limit = '1';

    if( $db->autoUpdate('platform_role', $data, $where , $order, $limit) ) {
        show_system_message('修改管理员角色成功', array(array('alt'=>'查看管理员角色列表','link'=>'role.php')));
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }

}


//添加管理员角色
if('add' == $opera) {
    if( !check_purview('pur_role_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $name = trim(getPOST('name'));
    $purviews = getPOST('purviews');

    if( '' == $name ) {
        show_system_message('请填写角色名', array());
        exit;
    } else {
        $name = $db->escape(htmlspecialchars($name));
    }

    $purview_value = array();

    foreach( $purviews as $key => $sub_purviews ) {
        if( array_key_exists($key, $purview) ) {
            $purview_value[$key] = array();
            foreach( $sub_purviews as $pur ) {
                if( in_array($pur, $purview[$key]) ) {
                    $purview_value[$key][] = $pur;
                }
            }
        }
    }

    $data = array(
        'name' => $name,
        'purview' => json_encode($purview_value),
    );

    if( $db->autoInsert('platform_role', array($data)) ) {
        show_system_message('新增管理员角色成功', array(array('alt'=>'查看管理员角色列表','link'=>'role.php')));
        exit;
    } else {
        show_system_message('系统繁忙，请稍后再试', array());
        exit;
    }
}

//===========================================================================


if( 'view' == $act ) {
    if( !check_purview('pur_role_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_role_list = 'select `id`, `name` from `'.DB_PREFIX.'platform_role` order by `id` asc';

    $role_list = $db->fetchAll($get_role_list);
    assign('roleList', $role_list);

}

if( 'add' == $act ) {
    if( !check_purview('pur_role_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    global $menus, $purview, $L_purview;
    assign('purviews', $purview);
    assign('purviewValue', $L_purview);
}

if( 'edit' == $act ) {
    if( !check_purview('pur_role_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getGET('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_role = 'select `id`,`name`,`purview` from `'.DB_PREFIX.'platform_role` where `id`='.$id;
    $role = $db->fetchRow($get_role);
    $role['purview'] = json_decode($role['purview']);

    assign('role', $role);

    $purviewC = array();
    $sub_purviewC = array();

    global $purview, $L_purview;

    foreach( $purview as $key => $sub_purviews ) {
        foreach($sub_purviews as $pur) {
            $sub_purviewC[$key][$pur] = false;
        }
        $purviewC[$key] = false;
    }


    foreach( $role['purview'] as $key=>$sub_purviews ) {
        $checked = false;
        foreach( $sub_purviews as $pur ) {
            $sub_purviewC[$key][$pur] = true;
            $checked = true;

        }
        $purviewC[$key] = $checked;
    }

    assign('purviewC', $purviewC);
    assign('sub_purviewC', $sub_purviewC);

    assign('purviews', $purview);
    assign('purviewValue', $L_purview);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_role_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = getGET('id');
    $id = intval($id);

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_role = 'select * from `'.DB_PREFIX.'platform_role` where id = \''.$id.'\' limit 1';
    $role = $db->fetchRow($get_role);
    if( empty($role) ) {
        show_system_message('角色不存在', array());
        exit;
    }

    $checkAdminRole = 'select `account` from `'.DB_PREFIX.'admin` where `role_id`='.$id;
    if( $db->fetchAll($checkAdminRole) ) {
        show_system_message('该角色还有管理员在使用，不能删除', array());
        exit;
    } else {
        $delete_role = 'delete from `'.DB_PREFIX.'platform_role` where `id`='.$id.' limit 1';
        if( $db->delete($delete_role) ) {
            show_system_message('删除管理员角色成功', array(array('alt'=>'查看管理员角色', 'link'=>'role.php')));
            exit;
        } else {
            show_system_message('系统繁忙，请稍后再试', array());
            exit;
        }
    }
}

$template .= $act.'.phtml';
$smarty->display($template);

