<?php
/**
 * 产品分类
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-19
 * @version 1.0.0
 */

include 'library/init.inc.php';
global $db, $log, $config, $smarty;

//商户管理后台初始化
business_base_init();
$template = 'category/';

$action = 'view|add|edit|delete';
$operation = 'add|edit';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

//============================================================

if( 'add' == $opera ) {
    if( !check_purview('pur_category_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
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

    $img = trim(getPOST('img'));
    $img = $db->escape($img);

    $parent_id = intval($parent_id);
    if( 0 > $parent_id ) {
        show_system_message('参数错误', array());
        exit;
    }

    //限制自定义分类层数
    if($parent_id > 0) {
        $get_parent = 'select `parent_id`,`path` from ' . $db->table('category') . ' where `id`=' . $parent_id;
        if ($parent = $db->fetchRow($get_parent)) {
            $count = count(explode(',', $parent['path']));
            if ($count >= (intval($config['category_depth']) + 2)) {
                show_system_message('产品分类不能超过' . $config['category_depth'] . '级', array());
                exit;
            }
        }
    }

    $price_filter = intval($price_filter);
    if( 0 >= $price_filter ) {
        $price_filter = 3;
    }

    $search_brand = intval($search_brand);
    $search_brand = ( $search_brand == 1 ) ? 1 : 0;

    $data = array(
        'name' => $name,
        'business_account' => '',
        'parent_id' => $parent_id,
        'price_filter' => $price_filter,
        'path' => '',
        'icon' => $img,
        'search_brand' => $search_brand,
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
                array('alt'=>'查看产品分类', 'link'=>'category.php'),
                array('alt'=>'继续添加产品分类', 'link'=>'category.php?act=add')
            );
            show_system_message('添加产品分类成功', $links);
            exit;
        } else {
            show_system_message('更新产品分类失败', array());
            exit;
        }
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'edit' == $opera ) {
    if( !check_purview('pur_category_edit', $_SESSION['business_purview']) ) {
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
        show_system_message('产品分类不存在', array());
        exit;
    }

    $name = trim(getPOST('category_name'));
    $parent_id = trim(getPOST('parent_id'));
    $price_filter = trim(getPOST('price_filter'));
    $img = trim(getPOST('img'));
    $img = $db->escape($img);
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

    //限制自定义分类层数
    if($parent_id > 0) {
        $get_parent = 'select `parent_id`,`path` from ' . $db->table('category') . ' where `id`=' . $parent_id;
        if ($parent = $db->fetchRow($get_parent)) {
            $count = count(explode(',', $parent['path']));
            if ($count >= (intval($config['category_depth']) + 2)) {
                show_system_message('产品分类不能超过' . $config['category_depth'] . '级', array());
                exit;
            }
        }
    }

    $price_filter = intval($price_filter);
    if( 0 >= $price_filter ) {
        $price_filter = 3;
    }

    $search_brand = intval($search_brand);
    $search_brand = ( $search_brand == 1 ) ? 1 : 0;
    $path = $id.',';

    if( $parent_id > 0 ) {
        $get_parent_path = 'select `path` from '.$db->table('category').' where id = '.$parent_id.' limit 1';
        $parent_path = $db->fetchOne($get_parent_path);
        $path = $parent_path.$id.',';
    }


    $data = array(
        'name' => $name,
        'business_account' => '',
        'parent_id' => $parent_id,
        'price_filter' => $price_filter,
        'path' => $path,
        'icon' => $img,
        'search_brand' => $search_brand,
    );

    $table = 'category';
    $where = 'id = \''.$id.'\'';
    $order = '';
    $limit = '1';

    if( $db->autoUpdate($table, $data, $where, $order, $limit) ) {
        $get_offspring_list = 'select * from '.$db->table('category').' where `path` like \''.$category['path'].'%\'';
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

//============================================================


if( 'view' == $act ) {
    if( !check_purview('pur_category_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where 1 ';
    $get_category_list .= ' order by `path` ASC';
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
            }
            $category['search_brand_str'] = $category['search_brand'] == 1 ? '是' : '否';
            $category_list[$key] = $category;
        }
    }

    assign('category_list', $category_list);
}

if( 'add' == $act ) {
    if( !check_purview('pur_category_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where 1';
    $get_category_list .= ' order by `path` ASC';
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
    if( !check_purview('pur_category_edit', $_SESSION['business_purview']) ) {
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
        show_system_message('产品分类不存在', array());
        exit;
    }

    assign('category', $category);

    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where `id`<>'.$id.' and `path` not like \''.$category['path'].'%\'';
    $get_category_list .= ' order by `path` ASC';
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
    if( !check_purview('pur_category_del', $_SESSION['business_purview']) ) {
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
        show_system_message('产品分类不存在', array());
        exit;
    }

    $get_children = 'select * from '.$db->table('category').' where parent_id = '.$id;
    $children = $db->fetchAll($get_children);
    if( $children ) {
        show_system_message('当前分类下有子分类，请先删除或移走子分类', array());
        exit;
    }

    $get_products = 'select * from '.$db->table('product').' where category_id = '.$id;
    $products = $db->fetchAll($get_products);
    if( $products ) {
        show_system_message('当前分类下有产品，请先删除或移走产品', array());
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
