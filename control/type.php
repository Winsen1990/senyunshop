<?php
/**
 * 产品类型管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'type/';
assign('subTitle', '产品类型');

$action = 'edit|add|view|delete|attr|add-attr|edit-attr|delete-attr';
$operation = 'edit|add|add-attr|edit-attr';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

if( 'add' == $opera ) {
    if( !check_purview('pur_type_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $type_name = trim(getPOST('type_name'));
    if( '' == $type_name ) {
        show_system_message('产品类型名称不能为空', array());
        exit;
    }
    $type_name = $db->escape($type_name);

    $add_type = 'insert into '.$db->table('product_type').' (`name`) values (\''.$type_name.'\')';
    if( $db->insert($add_type) ) {
        $links = array(
            array('link' => 'type.php', 'alt' => '产品类型列表'),
            array('link' => 'type.php?act=add', 'alt' => '继续添加'),
        );
        show_system_message('添加成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'edit' == $opera ) {
    if( !check_purview('pur_type_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_type = 'select * from '.$db->table('product_type').' where id = \''.$id.'\' limit 1';
    $type = $db->fetchRow($get_type);

    if( empty($type) ) {
        show_system_message('产品类型不存在', array());
        exit;
    }
    $type_name = trim(getPOST('type_name'));
    if( '' == $type_name ) {
        show_system_message('产品类型名称不能为空', array());
        exit;
    }
    $type_name = $db->escape($type_name);

    $update_type = 'update '.$db->table('product_type').' set name = \''.$type_name.'\'';
    $update_type .= ' where id = '.$id.' limit 1';
    if( $db->update($update_type) ) {
        show_system_message('更新成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}


if( 'add-attr' == $opera ) {
    if( !check_purview('pur_type_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $product_type_id = intval(getPOST('id'));
    if( 0 >= $product_type_id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_type = 'select * from '.$db->table('product_type').' where id = \''.$product_type_id.'\' limit 1';
    $type = $db->fetchRow($get_type);

    if( empty($type) ) {
        show_system_message('产品类型不存在', array());
        exit;
    }

    $attr_name = trim(getPOST('attr_name'));
    $attr_type = trim(getPOST('type'));
    $options = trim(getPOST('options'));
    $needle = intval(getPOST('needle'));
    $filter = intval(getPOST('filter'));

    if( '' == $attr_name ) {
        show_system_message('属性名称不能为空', array());
        exit;
    }
    $attr_name = $db->escape($attr_name);

    $type_array = array('text', 'select');
    if( !in_array($attr_type, $type_array) ) {
        $attr_type == 'text';
    }

    $options = $db->escape($options);

    $needle = ( $needle == 1 ) ? 1 : 0;
    $filter = ( $filter == 1 ) ? 1 : 0;

    $data = array(
        'product_type_id' => $product_type_id,
        'name' => $attr_name,
        'type' => $attr_type,
        'options' => $options,
        'needle' => $needle,
        'filter' => $filter,
    );

    if( $db->autoInsert('product_attributes', array($data)) ) {
        $links = array(
            array('link' => 'type.php', 'alt' => '产品类型列表'),
            array('link' => 'type.php?act=attr&id='.$type['id'], 'alt' => '产品属性列表'),
            array('link' => 'type.php?act=add-attr&id='.$type['id'], 'alt' => '继续添加属性'),
        );

        show_system_message($type['name'].'添加属性成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'edit-attr' == $opera ) {
    if( !check_purview('pur_type_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }
    $get_attr = 'select * from '.$db->table('product_attributes').' where id = \''.$id.'\' limit 1';
    $attr = $db->fetchRow($get_attr);
    if( empty($attr) ) {
        show_system_message('产品属性不存在', array());
        exit;
    }

    $attr_name = trim(getPOST('attr_name'));
    $attr_type = trim(getPOST('type'));
    $options = trim(getPOST('options'));
    $needle = intval(getPOST('needle'));
    $filter = intval(getPOST('filter'));

    if( '' == $attr_name ) {
        show_system_message('属性名称不能为空', array());
        exit;
    }
    $attr_name = $db->escape($attr_name);

    $type_array = array('text', 'select');
    if( !in_array($attr_type, $type_array) ) {
        $attr_type == 'text';
    }

    $options = $db->escape($options);

    $needle = ( $needle == 1 ) ? 1 : 0;
    $filter = ( $filter == 1 ) ? 1 : 0;

    $data = array(
        'name' => $attr_name,
        'type' => $attr_type,
        'options' => $options,
        'needle' => $needle,
        'filter' => $filter,
    );
    $where = 'id = '.$id;
    if( $db->autoUpdate('product_attributes', $data, $where) ) {
        $links = array(
            array('link' => 'type.php', 'alt' => '产品类型列表'),
            array('link' => 'type.php?act=attr&id='.$attr['product_type_id'], 'alt' => '产品属性列表'),
        );
        show_system_message('更新属性成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

//===========================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_type_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $get_type_list = 'select * from '.$db->table('product_type').' where 1';
    $type_list = $db->fetchAll($get_type_list);

    assign('type_list', $type_list);
}

if( 'add' == $act ) {
    if( !check_purview('pur_type_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
}

if( 'edit' == $act ) {
    if( !check_purview('pur_type_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_type = 'select * from '.$db->table('product_type').' where id = \''.$id.'\' limit 1';
    $type = $db->fetchRow($get_type);

    if( empty($type) ) {
        show_system_message('产品类型不存在', array());
        exit;
    }

    assign('type', $type);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_type_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_type = 'select * from '.$db->table('product_type').' where id = \''.$id.'\' limit 1';
    $type = $db->fetchRow($get_type);
    if( empty($type) ) {
        show_system_message('产品类型不存在', array());
        exit;
    }

    $get_product = 'select * from '.$db->table('product').' where type_id = '.$id;
    $product = $db->fetchAll($get_product);
    if( $product ) {
        show_system_message('当前产品类型下有产品存在，无法删除', array());
        exit;
    }
    $db->begin();
    $transaction = true;

    $delete_type = 'delete from '.$db->table('product_type').' where id = '.$id.' limit 1';
    if( !$db->delete($delete_type) ) {
        $transaction = false;
    }
    $delete_attr = 'delete from '.$db->table('product_attributes').' where product_type_id = '.$id;
    if( !$db->delete($delete_attr) ) {
        $transaction = false;
    }
    if( $transaction ) {
        $db->commit();
        show_system_message('产品类型删除成功', array());
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'attr' == $act ) {
    if( !check_purview('pur_type_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }
    $get_type = 'select * from '.$db->table('product_type').' where id = \''.$id.'\' limit 1';
    $type = $db->fetchRow($get_type);

    if( empty($type) ) {
        show_system_message('产品类型不存在', array());
        exit;
    }
    assign('type', $type);

    $get_attr_list = 'select * from '.$db->table('product_attributes').' where product_type_id = '.$id;
    $attr_list = $db->fetchAll($get_attr_list);

    if( $attr_list ) {
        foreach( $attr_list as $key => $value ) {
            $attr_list[$key]['needle_str'] = ($value['needle'] == 1 ) ? '是' : '否';
            if( $value['type'] != 'select' && $value['type'] != 'radio' ) {
                $attr_list[$key]['options'] = '无';
            }
        }
    }

    assign('attr_list', $attr_list);
}

if( 'add-attr' == $act ) {
    if( !check_purview('pur_type_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }
    $get_type = 'select * from '.$db->table('product_type').' where id = \''.$id.'\' limit 1';
    $type = $db->fetchRow($get_type);

    if( empty($type) ) {
        show_system_message('产品类型不存在', array());
        exit;
    }
    assign('type', $type);
}

if( 'edit-attr' == $act ) {
    if( !check_purview('pur_type_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }
    $get_attr = 'select * from '.$db->table('product_attributes').' where id = \''.$id.'\' limit 1';
    $attr = $db->fetchRow($get_attr);
    if( empty($attr) ) {
        show_system_message('产品属性不存在', array());
        exit;
    }
    assign('attr', $attr);

}

if( 'delete-attr' == $act ) {
    if( !check_purview('pur_type_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }
    $get_attr = 'select * from '.$db->table('product_attributes').' where id = \''.$id.'\' limit 1';
    $attr = $db->fetchRow($get_attr);
    if( empty($attr) ) {
        show_system_message('产品属性不存在', array());
        exit;
    }
    $delete_attr = 'delete from '.$db->table('product_attributes').' where id = \''.$id.'\' limit 1';
    if( $db->delete($delete_attr) ) {
        show_system_message('删除产品属性成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

$template .= $act.'.phtml';
$smarty->display($template);