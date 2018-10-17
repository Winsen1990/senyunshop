<?php
/**
 *
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 15-10-13
 * @version 
 */

include 'library/init.inc.php';

//商户管理后台初始化
business_base_init();
$template = 'virtual_product/';

$action = 'view|add|edit|delete|cycle|revoke|remove|sale|release|gallery|del-gallery|inventory';
$operation = 'add|edit|gallery|inventory';
$act = check_action($action, getGET('act'));
$opera = check_action($operation, getPOST('opera'));
$act = ( $act == '' ) ? 'view' : $act;
//==========================================================================
if( 'add' == $opera ) {
    if( !check_purview('pur_virtual_product_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $status = intval(getPOST('publish'));
    $name = trim(getPOST('name'));
    $category = intval(getPOST('category'));
    $price = floatval(getPOST('price'));
    $shop_price = floatval(getPOST('shop_price'));
    $lowest_price = floatval(getPOST('lowest_price'));

    $img = trim(getPOST('img'));
    $desc = trim(getPOST('desc'));
    $detail = trim(getPOST('detail'));
    $promote_price = floatval(getPOST('promote_price'));
    $promote_begin = trim(getPOST('promote_begin'));
    $promote_end = trim(getPOST('promote_end'));
    $order_view = intval(getPOST('order_view'));

    $content = getPOST('content');
    $count = getPOST('count');
    $total = getPOST('total');

    $single_inventory = getPOST('single_inventory');
    $single_inventory = intval($single_inventory);

    $integral = intval(getPOST('integral'));

    do {
        $sn = rand(100000, 999999);
        $product_sn = 'EIF'.$sn;
        $sql = 'select * from '.$db->table('product').' where product_sn = \''.$product_sn.'\'';
        $exists = $db->fetchRow($sql);
    } while( $exists );

    $status = ( $status == 1 ) ? 1 : 2;

    if( '' == $name ) {
        show_system_message('产品名称不能为空', array());
        exit;
    }
    $name = $db->escape($name);

    if( 0 >= $category ) {
        show_system_message('产品分类参数错误', array());
        exit;
    }
    if( 0 > $price ) {
        show_system_message('售价不能为负数', array());
        exit;
    }

    if( 0 > $shop_price ) {
        show_system_message('市场价不能为负数', array());
        exit;
    }

    if( 0 > $lowest_price ) {
        show_system_message('最低价不能为负数', array());
        exit;
    }

    if( '' == $img ) {
        show_system_message('请选择一张图片作为封面', array());
        exit;
    }
    $img = $db->escape($img);

    $desc = $desc == '可不填' ? '' : $desc;
    $desc = $db->escape($desc);

    $detail = $db->escape($detail);

    $promote_price = ( $promote_price <= 0 ) ? 0 : $promote_price;

    if( '' != $promote_begin ) {
        if(preg_match('#^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)\ \d{1,2}:\d{1,2}:\d{1,2}$#', $promote_begin)) {
            $dateTime = explode(' ', $promote_begin);
            $date = explode('-', $dateTime[0]);
            $time = explode(':', $dateTime[1]);

            $promote_begin = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        } else {
            show_system_message('时间格式不正确', array());
            exit;
        }
    } else {
        $promote_begin = 0;
    }

    if( '' != $promote_end ) {
        if(preg_match('#^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)\ \d{1,2}:\d{1,2}:\d{1,2}$#', $promote_end)) {
            $dateTime = explode(' ', $promote_end);
            $date = explode('-', $dateTime[0]);
            $time = explode(':', $dateTime[1]);

            $promote_end = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        } else {
            show_system_message('时间格式不正确', array());
            exit;
        }
    } else {
        $promote_end = 0;
    }

    $order_view = ( 0 >= $order_view ) ? 50 : $order_view;

    $check_product_sn = 'select `product_sn` from '.$db->table('product').' where product_sn = \''.$product_sn.'\' limit 1';
    $product = $db->fetchRow($check_product_sn);
    if( $product ) {
        show_system_message('产品编号已被使用', array());
        exit;
    }

    $check_category = 'select * from '.$db->table('category').' where id = '.$category.' and business_account = \''.$_SESSION['business_account'].'\' limit 1';
    $category_exists = $db->fetchRow($check_category);
    if( !$category_exists ) {
        show_system_message('不存在的产品分类', array());
        exit;
    }

    if( 0 >= $integral ) {
        $integral = 0;
    } else {
        $price = 0;
        $shop_price = 0;
        $lowest_price = 0;
        $promote_price = 0;
    }

    $data = array(
        'product_sn' => $product_sn,
        'name' => $name,
        'shop_price' => $shop_price,
        'price' => $price,
        'lowest_price' => $lowest_price,
        'reward' => 0,
        'integral' => $integral,
        'integral_given' => 0,
        'img' => $img,
        'desc' => $desc,
        'detail' => $detail,
        'business_account' => $_SESSION['business_account'],
        'category_id' => $category,
        'product_type_id' => 0,
        'status' => $status,
        'promote_price' => $promote_price,
        'promote_begin' => $promote_begin,
        'promote_end' => $promote_end,
        'add_time' => time(),
        'weight' => 0,
        'brand_id' => 0,
        'order_view' => $order_view,
        'free_delivery' => 0,
        'prev_status' => 0,
        'is_virtual' => 1,
    );
    $table = 'product';
    $db->begin();
    $transaction = true;
    if( !$db->autoInsert($table, array($data)) ) {
        $transaction = false;
    }

    $links = array(
        array('link' => 'virtual_product.php', 'alt' => '虚拟产品列表'),
        array('link' => 'virtual_product.php?act=add', 'alt' => '继续添加'),
    );


    //产品库存
    $inventory = intval($single_inventory);
    if( 0 > $inventory ) {
        $inventory = 0;
    }
    $data = array(
        'product_sn' => $product_sn,
        'attributes' => '',
        'inventory' => $inventory,
        'inventory_logic' => $inventory
    );

    $table = 'inventory';
    if (!$db->autoInsert($table, array($data))) {
        $transaction = false;
    }

    if( $transaction == false ) {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
    $db->commit();
    show_system_message('增加产品成功', $links);
    exit;

}

if( 'edit' == $opera ) {
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_product = 'select * from '.$db->table('product').' where business_account = \''.$_SESSION['business_account'].'\' and id = \''.$id.'\' limit 1';
    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array());
        exit;
    }

    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array());
        exit;
    }

    if( $product['status'] == 2 ) {
        show_system_message('产品审核中，请耐心等待', array());
        exit;
    }

    $name = trim(getPOST('name'));
    $category = intval(getPOST('category'));
    $price = floatval(getPOST('price'));
    $shop_price = floatval(getPOST('shop_price'));
    $lowest_price = floatval(getPOST('lowest_price'));

    $img = trim(getPOST('img'));
    $desc = trim(getPOST('desc'));
    $detail = trim(getPOST('detail'));
    $promote_price = floatval(getPOST('promote_price'));
    $promote_begin = trim(getPOST('promote_begin'));
    $promote_end = trim(getPOST('promote_end'));
    $order_view = intval(getPOST('order_view'));

    $integral = intval(getPOST('integral'));

    $inventory = getPOST('single_inventory');
    $inventory = intval($inventory);
    if( 0 > $inventory ) {
        $inventory = 0;
    }

    if( '' == $name ) {
        show_system_message('产品名称不能为空', array());
        exit;
    }
    $name = $db->escape($name);

    if( 0 >= $category ) {
        show_system_message('产品分类参数错误', array());
        exit;
    }

    if( 0 > $price ) {
        show_system_message('售价不能为负数', array());
        exit;
    }

    if( 0 > $shop_price ) {
        show_system_message('市场价不能为负数', array());
        exit;
    }

    if( 0 > $lowest_price ) {
        show_system_message('最低价不能为负数', array());
        exit;
    }

    if( '' == $img ) {
        show_system_message('请选择一张图片作为封面', array());
        exit;
    }
    $img = $db->escape($img);

    $desc = $db->escape($desc);

    $detail = $db->escape($detail);

    $promote_price = ( $promote_price <= 0 ) ? 0 : $promote_price;

    if( '' != $promote_begin ) {
        if(preg_match('#^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)\ \d{1,2}:\d{1,2}:\d{1,2}$#', $promote_begin)) {
            $dateTime = explode(' ', $promote_begin);
            $date = explode('-', $dateTime[0]);
            $time = explode(':', $dateTime[1]);

            $promote_begin = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        } else {
            show_system_message('时间格式不正确', array());
            exit;
        }
    } else {
        $promote_begin = 0;
    }

    if( '' != $promote_end ) {
        if(preg_match('#^(?:(?!0000)[0-9]{4}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1[0-9]|2[0-8])|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)|(?:[0-9]{2}(?:0[48]|[2468][048]|[13579][26])|(?:0[48]|[2468][048]|[13579][26])00)-02-29)\ \d{1,2}:\d{1,2}:\d{1,2}$#', $promote_end)) {
            $dateTime = explode(' ', $promote_end);
            $date = explode('-', $dateTime[0]);
            $time = explode(':', $dateTime[1]);

            $promote_end = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        } else {
            show_system_message('时间格式不正确', array());
            exit;
        }
    } else {
        $promote_end = 0;
    }

    $order_view = ( 0 >= $order_view ) ? 50 : $order_view;

    if( 0 >= $integral ) {
        $integral = 0;
    } else {
        $price = 0;
        $shop_price = 0;
        $lowest_price = 0;
        $promote_price = 0;
    }

    $data = array(
        'name' => $name,
        'shop_price' => $shop_price,
        'price' => $price,
        'lowest_price' => $lowest_price,
        'img' => $img,
        'desc' => $desc,
        'detail' => $detail,
        'category_id' => $category,
        'promote_price' => $promote_price,
        'promote_begin' => $promote_begin,
        'promote_end' => $promote_end,
        'order_view' => $order_view,
        'status' => ( $product['status'] == 1 ) ? 1 : 2,
        'prev_status' => $product['status'],
        'integral' => $integral,
    );
    $table = 'product';
    $where = 'business_account = \''.$_SESSION['business_account'].'\' and id = \''.$id.'\' and is_virtual = 1';
    $order = '';
    $limit = '1';
    if( $db->autoUpdate($table, $data, $where, $order, $limit) ) {
        //检查库存，如果存在无属性库存则更新库存
        $check_inventory = 'select `id` from '.$db->table('inventory').' where `product_sn`=\''.$product_sn.'\' and `attributes`=\'\'';
        $inventory_id = $db->fetchOne($check_inventory);
        if($inventory_id)
        {
            modify_inventory($product_sn, '', $inventory);
        }

        $links = array(
            array('link' => 'virtual_product.php', 'alt' => '虚拟产品列表'),
            array('link' => 'virtual_product.php?act=add', 'alt' => '添加虚拟产品'),
        );

        show_system_message('修改产品成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'gallery' == $opera ) {
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getPOST('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);
    $get_product = 'select a.* from '.$db->table('product').' as a';
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and a.product_sn = \''.$product_sn.'\' and status <> 5 limit 1';
    $product = $db->fetchRow($get_product);
    if( !$product ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array());
        exit;
    }

    $img_list = getPOST('img');
    $order_view_list = getPOST('order_view');
    $id_list = getPOST('id');

    if( !is_array($img_list) || !is_array($order_view_list) || !is_array($id_list) ) {
        show_system_message('参数错误', array());
        exit;
    }

    $insert_data = array();
    $update_data = array();
    $update_where = array();

    foreach( $order_view_list as $key => $order_view ) {
        $order_view_list[$key] = intval($order_view) <= 0 ? 50 : intval($order_view);
    }

    foreach( $img_list as $key => $img ) {
        $img_list[$key] = $db->escape(trim($img));
    }

    foreach( $id_list as $key => $id ) {
        if( 0 >= intval($id) ) {
            if( '' == $img_list[$key] || !file_exists('..'.$img_list[$key])) {
                continue;
            }
            $insert_data[] = array(
                'original_img' => $img_list[$key],
                'order_view' => $order_view_list[$key],
                'big_img' => $img_list[$key],
                'thumb_img' => str_replace('/image/', '/thumb/', $img_list[$key]),
                'product_sn' => $product_sn,
            );
        } else {
            if( '' == $img_list[$key] || !file_exists('..'.$img_list[$key])) {
                continue;
            }
            $update_data[] = array(
                'original_img' => $img_list[$key],
                'order_view' => $order_view_list[$key],
                'big_img' => $img_list[$key],
                'thumb_img' => str_replace('/image/', '/thumb/', $img_list[$key]),
                'product_sn' => $product_sn,
            );
            $update_where[] = 'id = '.$id.' and product_sn = \''.$product_sn.'\'';
        }
    }

    $db->begin();
    $transaction = true;
    if( $insert_data ) {
        if (!$db->autoInsert('gallery', $insert_data)) {
            $transaction = false;
        }
    }

    foreach( $update_data as $key => $data ) {
        if( !$db->autoUpdate('gallery', $data, $update_where[$key]) ) {
            $transaction = false;
        }
    }

    if( $transaction ) {
        $db->commit();
        show_system_message('修改产品相册成功', array());
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'inventory' == $opera ) {
    $response = array('error' => 1, 'msg' => '', 'errmsg' => array(), 'edit_id' => 0, 'new_inventory' => 0, 'new_inventory_logic' => 0);
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        $response['msg'] = '权限不足';
        echo json_encode($response);
        exit;
    }

    $new_inventory = intval(getPOST('inventory'));
    if( 0 > $new_inventory ) {
        $response['msg'] = '参数错误';
        echo json_encode($response);
        exit;
    }
    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        $response['msg'] = '参数错误';
        echo json_encode($response);
        exit;
    }

    $get_inventory = 'select * from '.$db->table('inventory').' where id = \''.$id.'\' limit 1';
    $inventory = $db->fetchRow($get_inventory);
    if( empty($inventory) ) {
        $response['msg'] = '库存记录不存在';
        echo json_encode($response);
        exit;
    }

    if( $inventory['inventory_await'] > 0 && $inventory['inventory_await'] > $new_inventory ) {
        $response['msg'] = '待发库存不为0时，新库存不能小于待发库存';
        $response['edit_id'] = $inventory['id'];
        echo json_encode($response);
        exit;
    }
    $new_inventory_logic = $new_inventory - $inventory['inventory_await'];

    $update_inventory = 'update '.$db->table('inventory').' set ';
    $update_inventory .= ' `inventory` = '.$new_inventory;
    $update_inventory .= ', `inventory_logic` = '.$new_inventory_logic;
    $update_inventory .= ' where id = \''.$id.'\' limit 1';

    if( $db->update($update_inventory) ) {
        $response['error'] = 0;
        $response['msg'] = '修改库存成功';
        $response['edit_id'] = $id;
        $response['new_inventory'] = $new_inventory;
        $response['new_inventory_logic'] = $new_inventory_logic;
        echo json_encode($response);
        exit;
    } else {
        $response['msg'] = '系统繁忙，请稍后重试';
        echo json_encode($response);
        exit;
    }
}

//===============================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_virtual_product_view', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $and_where = '';

    $status = intval(getGET('status'));
    if( $status != 0 ) {
        switch($status) {
            case 1:
                $and_where .= ' and status = 1';
                break;
            case 2:
                $and_where .= ' and status = 2';
                break;
            case 3:
                $and_where .= ' and status = 3';
                break;
            case 4:
                $and_where .= ' and status = 4';
                break;
            default:
                $and_where .= ' and status = 0';
                break;
        }
    } else {
        $and_where .= ' and status <> 5';
    }
    assign('status', $status);

    $keyword = trim(getGET('keyword'));
    if( '' != $keyword ) {
        $keyword = $db->escape($keyword);
        $and_where .= ' and a.name like \'%'.$keyword.'%\' || product_sn like \'%'.$keyword.'%\'';
    }
    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('product');
    $get_total .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_total .= $and_where;
    $get_total .= ' and is_virtual = 1';    //虚拟产品
    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);
    assign('keyword', $keyword);

    $offset = ($page - 1) * $count;

    $get_product_list = 'select a.*, b.name as category_name from '.$db->table('product').' as a';
    $get_product_list .= ' left join '.$db->table('category').' as b on a.category_id = b.id';
    $get_product_list .= ' where a.business_account = \''.$_SESSION['business_account'].'\'';
    $get_product_list .= $and_where;
    $get_product_list .= ' and is_virtual = 1';    //虚拟产品
    $get_product_list .= ' order by order_view asc, id desc';
    $get_product_list .= ' limit '.$offset.','.$count;
    $product_list = $db->fetchAll($get_product_list);

    $status_array = array(
        1 => '待发布',
        2 => '待审核',
        3 => '已下架',
        4 => '已上架',
    );

    $opera_array = array(
        1 => '发布',
        2 => '',
        3 => '上架',
        4 => '下架',
    );

    if( $product_list ) {
        foreach( $product_list as $key => $product ) {
            if( file_exists(realpath('..'.$product['img']))) {
                $product[$key]['img'] = '..'.$product['img'];
            }
            $product_list[$key]['status_str'] = $status_array[$product['status']];
            $product_list[$key]['sales_str'] = $opera_array[$product['status']];
        }
    }
    assign('product_list', $product_list);

}

if( 'add' == $act ) {
    if( !check_purview('pur_virtual_product_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_category_list .= ' order by `path` ASC';
    $category_list = $db->fetchAll($get_category_list);
    if( $category_list ) {
        foreach ($category_list as $key => $category) {
            $count = count(explode(',', $category['path']));
            if ($count > 2) {
                $temp = '|--' . $category['name'];
                $count = 1;
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

if( 'release' == $act ) {
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where product_sn = \''.$product_sn.'\'';
    $get_product .= ' and business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and is_virtual = 1';
    $get_product .= ' limit 1';
    $product  = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array());
        exit;
    }
    $release_product = 'update '.$db->table('product').' set status = 2, prev_status = '.$product['status'];
    $release_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $release_product .= ' and product_sn = \''.$product_sn.'\' limit 1';

    if( $db->update($release_product) ) {
        show_system_message('发布成功，平台将对产品进行审核', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'sale' == $act ) {
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where product_sn = \''.$product_sn.'\'';
    $get_product .= ' and business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and is_virtual = 1';  //虚拟产品
    $get_product .= ' limit 1';
    $product  = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array());
        exit;
    }
    $product_on_shelf = 'update '.$db->table('product').' set status = 4, prev_status = 3';
    $product_on_shelf .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $product_on_shelf .= ' and product_sn = \''.$product_sn.'\' limit 1';

    $product_off_shelf = 'update '.$db->table('product').' set status = 3, prev_status = 4';
    $product_off_shelf .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $product_off_shelf .= ' and product_sn = \''.$product_sn.'\' limit 1';

    if( $product['status'] == 3 ) {
        if( $db->update($product_on_shelf) ) {
            show_system_message('上架成功', array());
            exit;
        }
    } else if( $product['status'] == 4 ) {
        if( $db->update($product_off_shelf) ) {
            show_system_message('下架成功', array());
            exit;
        }
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'edit' == $act ) {
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select a.* from '.$db->table('product').' as a';
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and is_virtual = 1';  //虚拟产品
    $get_product .= ' and a.product_sn = \''.$product_sn.'\' and status <> 2 limit 1';
    $product = $db->fetchRow($get_product);
    if( !$product ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array());
        exit;
    }
    if( $product['status'] == 2 ) {
        show_system_message('产品审核中，请耐心等待', array());
        exit;
    }

    $product['promote_begin'] = ( $product['promote_begin'] ) ? date('Y-m-d H:i:s', $product['promote_begin']) : '';
    $product['promote_end'] = ( $product['promote_end'] ) ? date('Y-m-d H:i:s', $product['promote_end']) : '';
    $product['img_src'] = (file_exists('..'.$product['img'])) ? '..'.$product['img'] : $product['img'];
    assign('product', $product);


    $get_contents = 'select * from '.$db->table('virtual_content');
    $get_contents .= ' where product_sn = \''.$product_sn.'\' order by id asc';
    $virtual_contents = $db->fetchAll($get_contents);
//    var_dump($product_attributes);exit;
    $inventory = 0;
    $get_attributes = 'select * from '.$db->table('inventory');
    $get_attributes .= ' where product_sn = \''.$product_sn.'\'';
    $product_attributes = $db->fetchAll($get_attributes);
//    var_dump($product_attributes);exit;
    if( $product_attributes ) {
        foreach( $product_attributes as $key => $attributes ) {
            $product_attributes[$key]['attributes'] = json_decode($attributes['attributes']);
        }
    } else {
        $product_attributes = array();
    }

    if(count($product_attributes) == 1)
    {
        $inventory = $product_attributes[0]['inventory'];
    }

    assign('inventory', $inventory);

    if(empty($virtual_contents))
    {
        $virtual_contents = array();
    }
    assign('virtual_contents', json_encode($virtual_contents));

    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_category_list .= ' order by `path` ASC';
    $category_list = $db->fetchAll($get_category_list);
    if( $category_list ) {
        foreach ($category_list as $key => $category) {
            $count = count(explode(',', $category['path']));
            if ($count > 2) {
                $temp = '|--' . $category['name'];
                $count = 1;
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

if( 'gallery' == $act ) {
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select a.* from '.$db->table('product').' as a';
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and is_virtual = 1';  //虚拟产品
    $get_product .= ' and a.product_sn = \''.$product_sn.'\' and status <> 5 limit 1';
    $product = $db->fetchRow($get_product);
    if( !$product ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array());
        exit;
    }

    $get_gallery_list = 'select * from '.$db->table('gallery');
    $get_gallery_list .= ' where product_sn = \''.$product_sn.'\'';
    $get_gallery_list .= ' order by order_view asc, id asc';

    $gallery_list = $db->fetchAll($get_gallery_list);
    $count = count($gallery_list);

    $gallery_count = 5;

    if( $gallery_list ) {
        foreach( $gallery_list as $key => $gallery ) {
            if( file_exists(realpath('..'.$gallery['original_img'])) ) {
                $gallery_list[$key]['original_img_src'] = $gallery['original_img'];
            } else {
                $gallery_list[$key]['original_img_src'] = $gallery['original_img'];
            }

        }
        for( $i = $count; $i < $gallery_count; $i++ ) {
            $gallery_list[$i]['id'] = '';
            $gallery_list[$i]['original_img'] = '';
            $gallery_list[$i]['original_img_src'] = 'upload/image/no-image.png';
            $gallery_list[$i]['order_view'] = '';
        }
    } else {
        for( $i = 0; $i < $gallery_count; $i++ ) {
            $gallery_list[$i]['id'] = '';
            $gallery_list[$i]['original_img'] = '';
            $gallery_list[$i]['original_img_src'] = 'upload/image/no-image.png';
            $gallery_list[$i]['order_view'] = '';
        }
    }

    assign('product_sn', $product_sn);
    assign('gallery_list', $gallery_list);

}

if( 'del-gallery' == $act ) {
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        redirect('virtual_product.php?act=gallery');
    }
    $get_gallery = 'select * from '.$db->table('gallery').' as a';
    $get_gallery .= ' left join '.$db->table('product').' as b on a.product_sn = b.product_sn';
    $get_gallery .= ' where a.id = '.$id.' and b.business_account = \''.$_SESSION['business_account'].'\' limit 1';
    $gallery = $db->fetchRow($get_gallery);
    if( empty($gallery) ) {
        show_system_message('图片不存在', array());
        exit;
    }

    $delete_gallery = 'delete from '.$db->table('gallery').' where id = '.$id.' limit 1';
    if( $db->delete($delete_gallery) ) {
        redirect('virtual_product.php?act=gallery&sn='.$gallery['product_sn']);
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'delete' == $act ) {
    if( !check_purview('pur_virtual_product_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where product_sn = \''.$product_sn.'\'';
    $get_product .= ' and is_virtual = 1';  //虚拟产品
    $get_product .= ' and business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' limit 1';
    $product  = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array());
        exit;
    }

    //检查是否有待发库存
    $get_inventory_list = 'select * from '.$db->table('inventory');
    $get_inventory_list .= ' where product_sn = \''.$product_sn.'\'';
    $inventory_list = $db->fetchAll($get_inventory_list);
    $flag = true;
    if( $inventory_list ) {
        foreach( $inventory_list as $inventory ) {
            if( $inventory['inventory_await'] > 0 ) {
                show_system_message('产品待发中，不可删除', array());
            }
        }
    }

    $update_product = 'update '.$db->table('product').' set status = 5';
    $update_product .= ', prev_status = '.$product['status'];
    $update_product .= ' where product_sn = \''.$product_sn.'\'';
    $update_product .= ' and business_account = \''.$_SESSION['business_account'].'\'';
    $update_product .= ' limit 1';

    if( $db->update($update_product) ) {

        $links = array(
            array('link' => 'virtual_product.php', 'alt' => '虚拟产品列表'),
            array('link' => 'virtual_product.php?act=cycle', 'alt' => '回收站'),
        );

        show_system_message('产品'.$product['product_sn'].'已移入回收站', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'cycle' == $act ) {
    if( !check_purview('pur_virtual_product_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $and_where = ' and status = 5';

    $keyword = trim(getGET('keyword'));
    if( '' != $keyword ) {
        $keyword = $db->escape($keyword);
        $and_where .= ' and a.name like \'%'.$keyword.'%\' || product_sn like \'%'.$keyword.'%\'';
    }
    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('product').' as a';
    $get_total .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_total .= $and_where;
    $get_total .= ' and is_virtual = 1';  //虚拟产品
    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);
    assign('keyword', $keyword);

    $offset = ($page - 1) * $count;

    $get_product_list = 'select a.*, b.name as category_name from '.$db->table('product').' as a';
    $get_product_list .= ' left join '.$db->table('category').' as b on a.category_id = b.id';
    $get_product_list .= ' where a.business_account = \''.$_SESSION['business_account'].'\'';
    $get_product_list .= $and_where;
    $get_product_list .= ' and is_virtual = 1';  //虚拟产品
    $get_product_list .= ' order by order_view asc, id desc';
    $get_product_list .= ' limit '.$offset.','.$count;
//    echo $get_product_list;exit;
    $product_list = $db->fetchAll($get_product_list);

    if( $product_list ) {
        foreach( $product_list as $key => $product ) {
            if( file_exists(realpath('..'.$product['img']))) {
                $product[$key]['img'] = '..'.$product['img'];
            }
        }
    }
    assign('product_list', $product_list);

}

if( 'revoke' == $act ) {
    if( !check_purview('pur_virtual_product_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where product_sn = \''.$product_sn.'\'';
    $get_product .= ' and business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and is_virtual = 1';  //虚拟产品
    $get_product .= ' limit 1';
    $product  = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] != 5 ) {
        show_system_message('产品未被删除', array());
        exit;
    }
    $update_product = 'update '.$db->table('product').' set status = '.$product['prev_status'];
    $update_product .= ', prev_status = \''.$product['status'].'\'';
    $update_product .= ' where product_sn = \''.$product_sn.'\'';
    $update_product .= ' and business_account = \''.$_SESSION['business_account'].'\'';
    $update_product .= ' limit 1';

    if( $db->update($update_product) ) {

        $links = array(
            array('link' => 'virtual_product.php', 'alt' => '虚拟产品列表'),
            array('link' => 'virtual_product.php?act=cycle', 'alt' => '回收站'),
        );

        show_system_message('产品'.$product['product_sn'].'已移出回收站', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'remove' == $act ) {
    if( !check_purview('pur_virtual_product_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where product_sn = \''.$product_sn.'\'';
    $get_product .= ' and business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and is_virtual = 1';  //虚拟产品
    $get_product .= ' limit 1';
    $product  = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] != 5 ) {
        show_system_message('产品未被删除', array(array('link' => 'virtual_product.php', 'alt' => '虚拟产品列表')));
        exit;
    }

    //检查是否有待发库存
    $get_inventory_list = 'select * from '.$db->table('inventory');
    $get_inventory_list .= ' where product_sn = \''.$product_sn.'\'';
    $inventory_list = $db->fetchAll($get_inventory_list);
    $flag = true;
    if( $inventory_list ) {
        foreach( $inventory_list as $inventory ) {
            if( $inventory['inventory_await'] > 0 ) {
                show_system_message('产品待发中，不可删除', array());
            }
        }
    }

    $db->begin();
    $transaction = true;

    $remove_product = 'delete from '.$db->table('product');
    $remove_product .= ' where product_sn = \''.$product_sn.'\'';
    $remove_product .= ' and business_account = \''.$_SESSION['business_account'].'\'';
    $remove_product .= ' limit 1';

    if( !$db->delete($remove_product) ) {
        $transaction = false;
    }

    if( $transaction ) {
        $links = array(
            array('link' => 'virtual_product.php', 'alt' => '虚拟产品列表'),
            array('link' => 'virtual_product.php?act=cycle', 'alt' => '回收站'),
            array('link' => 'virtual_product.php?act=add', 'alt' => '添加虚拟产品'),
        );
        $db->commit();
        show_system_message('产品'.$product['product_sn'].'已被彻底删除', $links);
        exit;
    } else {
        $db->rollback();
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'inventory' == $act ) {
    if( !check_purview('pur_virtual_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select a.* from '.$db->table('product').' as a';
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and is_virtual = 1';  //实体产品
    $get_product .= ' and a.product_sn = \''.$product_sn.'\' and status <> 2 limit 1';
    $product = $db->fetchRow($get_product);
    if( !$product ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array());
        exit;
    }

    $get_attributes_list = 'select * from '.$db->table('product_attributes');
    $get_attributes_list .= ' where product_type_id = '.$product['product_type_id'];
    $attributes_list = $db->fetchAll($get_attributes_list);
    $target = array();
    if( $attributes_list ) {
        foreach( $attributes_list as $k => $v ) {
            $target[$v['id']] = $v['name'];
        }
    }

    $get_inventory_list = 'select * from '.$db->table('inventory');
    $get_inventory_list .= ' where product_sn = \''.$product_sn.'\'';
//    echo $get_inventory_list;exit;

    $inventory_list = $db->fetchAll($get_inventory_list);
    if( $inventory_list ) {
        foreach( $inventory_list as $key => $inventory ) {
            if( $inventory['attributes'] ) {
                $inventory['attributes'] = json_decode($inventory['attributes']);
                $temp = '';
                $i = 0;
                foreach( $inventory['attributes'] as $k => $v ) {
                    $temp .= $target[$k].':'.$v.'&nbsp;&nbsp;';
                    if( (++$i%3) == 0 ) {
                        $temp .= '<br />';
                    }
                }
                $inventory_list[$key]['attributes_str'] = $temp;
            } else {
                $inventory_list[$key]['attributes_str'] = '无属性';
            }
        }
    }
    assign('inventory_list', $inventory_list);
    assign('product', $product);
}

$template .= $act.'.phtml';
$smarty->display($template);