<?php
/**
 * 主营分类管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'category/';
assign('subTitle', '主营分类');

$action = 'edit|add|view|delete';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================

if( 'add' == $opera ) {
    if( !check_purview('pur_category_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $name = trim(getPOST('category_name'));
    $parent_id = trim(getPOST('parent_id'));

    if( '' == $name ) {
        show_system_message('分类名不能为空', array());
        exit;
    }
    $name = $db->escape($name);

    $parent_id = intval($parent_id);
    if( 0 > $parent_id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $img = trim(getPOST('img'));
    $img = $db->escape($img);

    $data = array(
        'name' => $name,
        'business_account' => '',
        'parent_id' => $parent_id,
        'price_filter' => 3,
        'path' => '',
        'icon' => $img,
        'search_brand' => 1,
    );

    $table = 'category';

    if( $db->autoInsert($table, array($data)) ) {
        $id = $db->get_last_id();
        $path = $id.',';
        if( 0 < $parent_id ) {
            $get_parent_path = 'select `path` from '.$db->table($table).' where `id`='.$parent_id;
            if( $parent_path = $db->fetchRow($get_parent_path) ) {
                $path = $parent_path['path'].$path;
            }
        }

        $update_category = 'update '.$db->table($table).' set `path`=\''.$path.'\' where `id`='.$id.' limit 1';
        if( $db->update($update_category) ) {
            $links = array(
                array('alt'=>'查看主营分类', 'link'=>'category.php?act=view'),
                array('alt'=>'继续添加主营分类', 'link'=>'category.php?act=add')
            );
            show_system_message('添加主营分类成功', $links);
            exit;
        } else {
            show_system_message('更新主营分类失败', array());
            exit;
        }
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'edit' == $opera ) {
    if( !check_purview('pur_category_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getPOST('id'));

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
    }

    $get_category = 'select * from '.$db->table('category').' where id = \''.$id.'\' limit 1';
    $category = $db->fetchRow($get_category);
    if( empty($category) ) {
        show_system_message('主营分类不存在', array());
        exit;
    }
    if( $category['business_account'] != '' ) {
        show_system_message('主营分类不存在', array());
        exit;
    }

    $name = trim(getPOST('category_name'));
    $parent_id = trim(getPOST('parent_id'));
    $price_filter = trim(getPOST('price_filter'));
    $icon = trim(getPOST('icon'));
    $search_brand = trim(getPOST('search_brand'));

    if( '' == $name ) {
        show_system_message('分类名不能为空', array());
        exit;
    }
    $name = $db->escape($name);

    $parent_id = intval($parent_id);
    if( 0 > $parent_id ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( $parent_id == 0 ) {
        $path = $id.',';
    } else {
        $get_parent_path = 'select `path` from '.$db->table('category').' where id = '.$parent_id.' limit 1';
        $parent_path = $db->fetchOne($get_parent_path);
        $path = $parent_path.$id.',';
    }

    $img = trim(getPOST('img'));
    $img = $db->escape($img);

    $data = array(
        'name' => $name,
        'business_account' => '',
        'parent_id' => $parent_id,
        'price_filter' => 3,
        'path' => $path,
        'icon' => $img,
        'search_brand' => 1,
    );

    $table = 'category';
    $where = 'id = \''.$id.'\'';
    $order = '';
    $limit = '1';

    if( $db->autoUpdate($table, $data, $where, $order, $limit) ) {
        $get_offspring_list = 'select * from '.$db->table('category').' where `business_account` = \'\' `path` like \''.$category['path'].'%\'';
        $offspring_list = $db->fetchAll($get_offspring_list);
        if( $offspring_list ) {
            foreach ($offspring_list as $offspring) {
                $offspring_path = str_replace($category['path'], $path, $offspring['path']);
                $update_offspring = 'update ' . $db->table('category') . ' set `path` = \'' . $offspring_path . '\' where `id` = ' . $offspring['id'] . ' limit 1';
                $db->update($update_offspring);
            }
        }
        show_system_message('更新分类成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}


//===========================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_category_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_category_list = 'select `id` as pid, `name`, `icon`, `parent_id`, `path`, (select count(*) from wi_category where `parent_id` = pid and business_account = \'\') as `count` from '.$db->table('category');
    $get_category_list .= ' where business_account = \'\'';
    $get_category_list .= ' order by `path` ASC';
//    echo $get_category_list;exit;
    $category_list = $db->fetchAll($get_category_list);
    if( $category_list ) {
        foreach ($category_list as $key => $category) {
            $count = count(explode(',', $category['path']));
            if ($count > 1) {
                $temp = '';
                while (--$count) {
                    $temp .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                }
                $category['prefix'] = $temp;
            }
            $category['id'] = $category['pid'];
            if( empty($category['icon']) ) {
                $category['icon'] = 'upload/image/no-image.png';
            }
            $category_list[$key] = $category;
        }
    }
    assign('category_list', $category_list);
}

if( 'add' == $act ) {
    if( !check_purview('pur_category_add', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $get_category_list = 'select `id`, `name`, `path` from '.$db->table('category');
    $get_category_list .= ' where `business_account` = \'\' order by `path` ASC';
    $category_list = $db->fetchAll($get_category_list);
    if( $category_list ) {
        foreach ($category_list as $key => $category) {
            $count = count(explode(',', $category['path']));
            if ($count > 1) {
                $temp = '|--' . $category['name'];
                while ($count--) {
                    $temp = '&nbsp;&nbsp;' . $temp;
                }

                $category['name'] = $temp;
                $category_list[$key] = $category;
            }
        }
    }
    assign('category_list', $category_list);
}

if( 'edit' == $act ) {
    if( !check_purview('pur_category_edit', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
    }

    $get_category = 'select * from '.$db->table('category').' where id = \''.$id.'\' limit 1';
    $category = $db->fetchRow($get_category);
    if( empty($category) ) {
        show_system_message('主营分类不存在', array());
        exit;
    }
    if( $category['business_account'] != '' ) {
        show_system_message('主营分类不存在', array());
        exit;
    }
    $category['icon'] = empty($category['icon']) ? '/upload/image/no-image.png' : $category['icon'];
    assign('category', $category);

    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where business_account = \'\' and id <> \''.$id.'\' and path not like \''.$category['path'].'%\'';
    $get_category_list .= ' order by path asc';

    $category_list = $db->fetchAll($get_category_list);

    if( $category_list ) {
        foreach ($category_list as $key => $category) {
            $count = count(explode(',', $category['path']));
            if ($count > 1) {
                $temp = '|--' . $category['name'];
                while ($count--) {
                    $temp = '&nbsp;&nbsp;' . $temp;
                }

                $category['name'] = $temp;
                $category_list[$key] = $category;
            }
        }
    }
    assign('category_list', $category_list);
}

if( 'delete' == $act ) {
    if( !check_purview('pur_category_del', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));

    if( 0 >= $id ) {
        show_system_message('参数错误', array());
    }

    $get_category = 'select * from '.$db->table('category').' where id = \''.$id.'\' limit 1';
    $category = $db->fetchRow($get_category);
    if( empty($category) ) {
        show_system_message('主营分类不存在', array());
        exit;
    }
    if( $category['business_account'] != '' ) {
        show_system_message('主营分类不存在', array());
        exit;
    }

    $get_children = 'select * from '.$db->table('category').' where parent_id = '.$id;
    $children = $db->fetchAll($get_children);
    if( $children ) {
        show_system_message('当前分类下有子分类，请先删除或移走子分类', array());
        exit;
    }

    $get_business = 'select * from '.$db->table('business').' where category_id = '.$id;
    $business = $db->fetchAll($get_business);
    if( $business ) {
        show_system_message('当前分类下有商户，无法删除', array());
        exit;
    }
    $delete_category = 'delete from '.$db->table('category').' where id = \''.$id.'\' limit 1';
    if( $db->delete($delete_category) ) {
        show_system_message('删除分类成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}


$template .= $act.'.phtml';
$smarty->display($template);