<?php
/**
 * 主营行业管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'industry/';
assign('subTitle', '主营行业');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

if( 'add' == $opera ) {
    if( !check_purview('pur_industry_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $industry_name = trim(getPOST('industry_name'));
    if( '' == $industry_name ) {
        show_system_message('行业名称不能为空', array());
        exit;
    }
    $industry_name = $db->escape($industry_name);

    $add_industry = 'insert into '.$db->table('industry').' (`name`) values (\''.$industry_name.'\')';
    if( $db->insert($add_industry) ) {
        $links = array(
            array('link' => 'industry.php', 'alt' => '行业列表'),
            array('link' => 'industry.php?act=add', 'alt' => '继续添加'),
        );
        show_system_message('添加成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'edit' == $opera ) {
    if( !check_purview('pur_industry_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_industry = 'select * from '.$db->table('industry').' where id = \''.$id.'\' limit 1';
    $industry = $db->fetchRow($get_industry);

    if( empty($industry) ) {
        show_system_message('行业不存在', array());
        exit;
    }

    $industry_name = trim(getPOST('industry_name'));
    if( '' == $industry_name ) {
        show_system_message('行业名称不能为空', array());
        exit;
    }
    $industry_name = $db->escape($industry_name);

    $update_industry = 'update '.$db->table('industry').' set name = \''.$industry_name.'\'';
    $update_industry .= ' where id = \''.$id.'\' limit 1';
    if( $db->update($update_industry) ) {
        $links = array(
            array('link' => 'industry.php', 'alt' => '行业列表'),
            array('link' => 'industry.php?act=edit&id='.$id, 'alt' => '返回上一页'),
        );
        show_system_message('更新成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}


//===========================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_industry_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $get_industry_list = 'select * from '.$db->table('industry').' order by id asc';
    $industry_list = $db->fetchAll($get_industry_list);

    assign('industry_list', $industry_list);

}

if( 'add' == $act ) {
    if( !check_purview('pur_industry_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
}

if( 'edit' == $act ) {
    if( !check_purview('pur_industry_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_industry = 'select * from '.$db->table('industry').' where id = \''.$id.'\' limit 1';
    $industry = $db->fetchRow($get_industry);

    if( empty($industry) ) {
        show_system_message('行业不存在', array());
        exit;
    }

    assign('industry', $industry);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_industry_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_industry = 'select * from '.$db->table('industry').' where id = \''.$id.'\' limit 1';
    $industry = $db->fetchRow($get_industry);

    if( empty($industry) ) {
        show_system_message('行业不存在', array());
        exit;
    }

    $get_business = 'select * from '.$db->table('business').' where industry_id = '.$id;
    $business = $db->fetchAll($get_business);
    if( $business ) {
        show_system_message('当前行业下有商户，无法删除', array());
        exit;
    }
    $delete_industry = 'delete from '.$db->table('industry').' where id = \''.$id.'\' limit 1';
    if( $db->delete($delete_industry) ) {
        show_system_message('删除行业成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}


$template .= $act.'.phtml';
$smarty->display($template);