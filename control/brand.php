<?php
/**
 * 产品品牌管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'brand/';
assign('subTitle', '产品品牌');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

if( 'add' == $opera ) {
    if( !check_purview('pur_brand_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $name = trim(getPOST('name'));
    $img = trim(getPOST('img'));
    $desc = trim(getPOST('desc'));

    if( '' == $name ) {
        show_system_message('品牌名称不能未空', array());
        exit;
    }
    $name = $db->escape($name);
    $img = $db->escape($img);
    $desc = $db->escape($desc);

    $data = array(
        'name' => $name,
        'img' => $img,
        'desc' => $desc,
    );

    if( $db->autoInsert('brand', array($data)) ) {
        show_system_message('添加品牌成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'edit' == $opera ) {
    if( !check_purview('pur_brand_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_brand = 'select * from '.$db->table('brand').' where id = \''.$id.'\' limit 1';
    $brand = $db->fetchRow($get_brand);
    if( empty($brand) ) {
        show_system_message('品牌不存在', array());
        exit;
    }

    $name = trim(getPOST('name'));
    $img = trim(getPOST('img'));
    $desc = trim(getPOST('desc'));

    if( '' == $name ) {
        show_system_message('品牌名称不能未空', array());
        exit;
    }
    $name = $db->escape($name);
    $img = $db->escape($img);
    $desc = $db->escape($desc);

    $data = array(
        'name' => $name,
        'img' => $img,
        'desc' => $desc,
    );
    $where = 'id = '.$id;

    if( $db->autoUpdate('brand', $data, $where) ) {
        show_system_message('更新品牌成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}


//===========================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_brand_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $and_where = '';
    $keyword = trim(getGET('keyword'));
    if( '' != $keyword ) {
        $keyword = $db->escape($keyword);
        $and_where .= ' and name like \'%'.$keyword.'%\' or `desc` like \'%'.$keyword.'%\'';
    }

    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('brand');
    $get_total .= ' where 1';
    $get_total .= $and_where;
    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);
    assign('keyword', $keyword);

    $offset = ($page - 1) * $count;

    $get_brand_list = 'select * from '.$db->table('brand').' where 1 ';
    $get_brand_list .= $and_where;
    $get_brand_list .= ' order by id desc';
    $get_brand_list .= ' limit '.$offset.','.$count;

    $brand_list = $db->fetchAll($get_brand_list);
    assign('brand_list', $brand_list);
}

if( 'add' == $act ) {
    if( !check_purview('pur_brand_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
}

if( 'edit' == $act ) {
    if( !check_purview('pur_brand_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_brand = 'select * from '.$db->table('brand').' where id = \''.$id.'\' limit 1';
    $brand = $db->fetchRow($get_brand);
    if( empty($brand) ) {
        show_system_message('品牌不存在', array());
        exit;
    }
    if( $brand['img'] == '' ) {
        $brand['img'] = '/upload/image/no-image.png';
    }
    assign('brand', $brand);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_brand_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_brand = 'select * from '.$db->table('brand').' where id = \''.$id.'\' limit 1';
    $brand = $db->fetchRow($get_brand);
    if( empty($brand) ) {
        show_system_message('品牌不存在', array());
        exit;
    }

    $get_product = 'select * from '.$db->table('product').' where brand_id = '.$id;
    $product = $db->fetchAll($get_product);
    if( $product ) {
        show_system_message('当前品牌下有产品存在，无法删除', array());
        exit;
    }

    $delete_brand = 'delete from '.$db->table('brand').' where id = '.$id.' limit 1';
    if( $db->delete($delete_brand) ) {
        show_system_message('品牌删除成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}


$template .= $act.'.phtml';
$smarty->display($template);