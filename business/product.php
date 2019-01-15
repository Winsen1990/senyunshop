<?php
/**
 * 商户产品管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-19
 * @version 1.0.0
 */

include 'library/init.inc.php';
global $db, $log, $config, $smarty;

//商户管理后台初始化
business_base_init();
$template = 'product/';

$action = 'view|add|edit|delete|cycle|revoke|remove|sale|release|gallery|del-gallery|inventory|mini_qr_code';
$operation = 'add|edit|gallery|inventory|check_inventory|add_attr';
$act = check_action($action, getGET('act'));
$opera = check_action($operation, getPOST('opera'));
$act = ( $act == '' ) ? 'view' : $act;

$business = $db->find('business', '*', ['business_account' => $_SESSION['business_account']]);
assign('business', $business);
//===============================================================================
if( 'add' == $opera ) {
    if( !check_purview('pur_product_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $status = intval(getPOST('status'));
    $name = trim(getPOST('name'));
    $category = getPOST('category');
    $shop_category = intval(getPOST('shop_category'));
    $shop_category = 0;//单商户模式
    $product_type = intval(getPOST('type'));

    $brand = trim(getPOST('brand'));
    $price = floatval(getPOST('price'));
    $integral = intval(getPOST('integral'));
    $shop_price = floatval(getPOST('shop_price'));
    $lowest_price = floatval(getPOST('lowest_price'));

    $img = trim(getPOST('img'));
    $desc = trim(getPOST('desc'));
    $detail = trim(getPOST('detail'));
    $promote_price = floatval(getPOST('promote_price'));
    $promote_begin = trim(getPOST('promote_begin'));
    $promote_end = trim(getPOST('promote_end'));
    $weight = floatval(getPOST('weight'));
    $order_view = intval(getPOST('order_view'));
    $free_delivering = intval(getPOST('free_delivering'));

    $inventory = getPOST('inventory');
    $single_inventory = getPOST('single_inventory');
    $single_inventory = intval($single_inventory);

    $attr = getPOST('attr');

    $status = ( $status == 1 ) ? 2 : 1;

    if($status == 2 && $business['product_authorization']) {
        $status = 4;
    }

    if( '' == $name ) {
        show_system_message('产品名称不能为空', array());
        exit;
    }
    $name = $db->escape($name);

    if( !is_array($category) && (0 >= $category || 0 >= $shop_category) ) {
        show_system_message('产品分类参数错误', array());
        exit;
    }

    if(is_array($category)) {
        $category_tmp = [];
        foreach($category as $_category_id_tmp) {
            $_category_id_tmp = intval($_category_id_tmp);

            if($_category_id_tmp > 0) {
                array_push($category_tmp, $_category_id_tmp);
            }
        }

        $category = $category_tmp;
    } else {
        $category = intval($category);
    }

    if( 0 > $product_type ) {
        show_system_message('产品类型参数错误', array());
        exit;
    }


    $brand_id = 0;
    if( $brand == '' ) {
        show_system_message('品牌参数错误', array());
        exit;
    } else {
        //检查是否存在,如果存在,则读取id,否则插入记录
        $brand_id = $db->getColumn('brand', 'id', ['name' => $brand]);

        if(empty($brand_id))
        {
            $brand_data = array(
                'name' => $brand,
                'img' => '',
                'desc' => $brand
            );

            if($db->autoInsert('brand', array($brand_data)))
            {
                $brand_id = $db->get_last_id();
            } else {
                show_system_message('系统繁忙,请稍后再试');
            }
        }
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
        show_system_message('请选择一张图片作为产品主图', array());
        exit;
    }
    $img = $db->escape($img);

    $desc = $desc == '可不填' ? '' : $desc;
    $desc = $db->escape($desc);

    if($detail != '') {
        $detail = str_replace('"/upload', '"'.$config['domain'].'/upload', $detail);
    }

    $detail = $db->escape($detail);

    $promote_price = ( $promote_price <= 0 ) ? 0 : $promote_price;

    if( '' != $promote_begin ) {
        if(strtotime($promote_begin) != -1) {
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
        if(strtotime($promote_end) != -1) {
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

    $weight = ( 0 >= $weight ) ? 0 : $weight;

    $order_view = ( 0 >= $order_view ) ? 50 : $order_view;

    $free_delivering = ( 1 == $free_delivering ) ? 1 : 0;

    if(!is_array($category)) {
        $category = [$category];
    }

    $category = array_unique($category);

    foreach($category as $_category_id) {
        $category_exists = $db->getColumn('category', 'id', ['id' => $_category_id, 'business_account' => '']);
        if (empty($category_exists)) {
            show_system_message('不存在的产品系统分类', array());
            exit;
        }
    }

    if($product_type)
    {
        $check_type = 'select * from ' . $db->table('product_type') . ' where id = ' . $product_type . ' limit 1';
        $type_exists = $db->fetchRow($check_type);
        if (!$type_exists)
        {
            show_system_message('不存在的产品类型', array());
            exit;
        }
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
        'category_id' => $category[0],
        'shop_category_id' => $shop_category,
        'product_type_id' => $product_type,
        'status' => $status,
        'promote_price' => $promote_price,
        'promote_begin' => $promote_begin,
        'promote_end' => $promote_end,
        'add_time' => time(),
        'weight' => $weight,
        'brand_id' => $brand_id,
        'order_view' => $order_view,
        'free_delivery' => $free_delivering,
        'prev_status' => 0,
    );
    $table = 'product';
    $db->begin();
    $transaction = true;
    do {
        $sn = rand(100000, 999999);
        $product_sn = PRODUCT_PREFIX.$sn;

        $data['product_sn'] = $product_sn;

        if( !$db->autoInsert($table, array($data)) ) {
            $transaction = false;
        }
    } while(!$transaction);

    //创建产品分类关联
    $product_category_mapper = [];
    foreach($category as $_category_id) {
        array_push($product_category_mapper, [
            'product_sn' => $data['product_sn'],
            'category_id' => $_category_id
        ]);
    }
    $db->autoInsert('product_category_mapper', $product_category_mapper);

    $links = array(
        array('link' => 'product.php', 'alt' => '产品列表'),
        array('link' => 'product.php?act=add', 'alt' => '继续添加'),
    );


    //新增产品库存
    if( is_array($attr) ) {
        $log->record_array($attr);
        foreach ($attr as $k => $v) {
            $attributes = json_encode($v);
            $attributes = decodeUnicode($attributes);
            $attributes = $db->escape($attributes);
            $attributes = str_replace('\/', '/', $attributes);
            $data = array(
                'product_sn' => $product_sn,
                'attributes' => $attributes,
                'inventory' => $inventory[$k],
                'inventory_logic' => $inventory[$k]
            );
            $table = 'inventory';
            if (!$db->autoInsert($table, array($data))) {
                $transaction = false;
            }
        }
    } else {
        $inventory = intval($single_inventory);
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
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $product = $db->find('product', '*', ['id' => $id]);
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

    $product_sn = trim(getPOST('product_sn'));
    $name = trim(getPOST('name'));
    $category = getPOST('category');
    $shop_category = intval(getPOST('shop_category'));
    $shop_category = 0;
    $product_type = intval(getPOST('type'));

    $brand = trim(getPOST('brand'));
    $price = floatval(getPOST('price'));
    $shop_price = floatval(getPOST('shop_price'));
    $lowest_price = floatval(getPOST('lowest_price'));
    $reward = floatval(getPOST('reward'));

    $integral = intval(getPOST('integral'));

    $img = trim(getPOST('img'));
    $desc = trim(getPOST('desc'));
    $detail = trim(getPOST('detail'));
    $promote_price = floatval(getPOST('promote_price'));
    $promote_begin = trim(getPOST('promote_begin'));
    $promote_end = trim(getPOST('promote_end'));
    $weight = floatval(getPOST('weight'));
    $order_view = intval(getPOST('order_view'));
    $free_delivering = intval(getPOST('free_delivering'));

    if( '' == $name ) {
        show_system_message('产品名称不能为空', array());
        exit;
    }
    $name = $db->escape($name);

    if( !is_array($category) && (0 >= $category || 0 >= $shop_category) ) {
        show_system_message('产品分类参数错误', array());
        exit;
    }

    if(is_array($category)) {
        $category_tmp = [];
        foreach($category as $_category_id_tmp) {
            $_category_id_tmp = intval($_category_id_tmp);

            if($_category_id_tmp > 0) {
                array_push($category_tmp, $_category_id_tmp);
            }
        }

        $category = $category_tmp;
    } else {
        $category = intval($category);
    }

    if( 0 > $product_type ) {
        show_system_message('产品类型参数错误', array());
        exit;
    }

    $brand_id = 0;
    if( $brand == '' ) {
        show_system_message('品牌参数错误', array());
        exit;
    } else {
        $brand = $db->escape($brand);
        //检查是否存在,如果存在,则读取id,否则插入记录
        $get_brand_id = 'select `id` from '.$db->table('brand').' where `name`=\''.$brand.'\'';
        $brand_id = $db->fetchOne($get_brand_id);

        if(empty($brand_id))
        {
            $brand_data = array(
                'name' => $brand,
                'img' => '',
                'desc' => $brand
            );

            if($db->autoInsert('brand', array($brand_data)))
            {
                $brand_id = $db->get_last_id();
            } else {
                show_system_message('系统繁忙,请稍后再试');
            }
        }
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
        show_system_message('请选择一张图片作为产品主图', array());
        exit;
    }
    $img = $db->escape($img);

    $desc = $db->escape($desc);

    if($detail != '') {
        $detail = str_replace('"/upload', '"'.$config['domain'].'/upload', $detail);
    }

    $promote_price = ( $promote_price <= 0 ) ? 0 : $promote_price;

    if( '' != $promote_begin ) {
        if(strtotime($promote_begin) != -1) {
            $dateTime = explode(' ', $promote_begin);
            $date = explode('-', $dateTime[0]);
            $time = explode(':', $dateTime[1]);

            $promote_begin = mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
        } else {
            show_system_message('开始时间格式不正确'.$promote_begin, array());
            exit;
        }
    } else {
        $promote_begin = 0;
    }

    if( '' != $promote_end ) {
        if(strtotime($promote_end) != -1) {
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

    $weight = ( 0 >= $weight ) ? 0 : $weight;

    $order_view = ( 0 >= $order_view ) ? 50 : $order_view;

    $free_delivering = ( 1 == $free_delivering ) ? 1 : 0;

    if(!is_array($category)) {
        $category = [$category];
    }

    $category = array_unique($category);

    foreach($category as $_category_id) {
        $category_exists = $db->getColumn('category', 'id', ['id' => $_category_id, 'business_account' => '']);
        if (empty($category_exists)) {
            show_system_message('不存在的产品系统分类', array());
            exit;
        }
    }

    if($product_type)
    {
        $check_type = 'select * from ' . $db->table('product_type') . ' where id = ' . $product_type . ' limit 1';
        $type_exists = $db->fetchRow($check_type);
        if (!$type_exists)
        {
            show_system_message('不存在的产品类型', array());
            exit;
        }
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
        'name' => $name,
        'shop_price' => $shop_price,
        'price' => $price,
        'lowest_price' => $lowest_price,
        'img' => $img,
        'desc' => $desc,
        'detail' => $detail,
        'category_id' => $category[0],
        'shop_category_id' => $shop_category,
//        'product_type_id' => $product_type,
        'promote_price' => $promote_price,
        'promote_begin' => $promote_begin,
        'promote_end' => $promote_end,
        'weight' => $weight,
        'brand_id' => $brand_id,
        'order_view' => $order_view,
        'free_delivery' => $free_delivering,
        'status' => ( $product['status'] == 1 ) ? 1 : 2,
        'prev_status' => $product['status'],
        'integral' => $integral,
    );

    if($business['product_authorization']) {
        $data['status'] = 4;
    }

    $table = 'product';
    $where = 'business_account = \''.$_SESSION['business_account'].'\' and id = \''.$id.'\'';
    $order = '';
    $limit = '1';
    if( $db->autoUpdate($table, $data, $where, $order, $limit) !== false ) {
        //检查分类，如果不存在则新增
        $db->autoDelete('product_category_mapper', '`product_sn`=\''.$product['product_sn'].'\' and `category_id` not in ('.implode(',', $category).')');
        $product_category_mapper = [];
        foreach($category as $_category_id) {
            array_push($product_category_mapper, [
                'product_sn' => $product['product_sn'],
                'category_id' => $_category_id
            ]);
        }
        $db->autoReplace('product_category_mapper', $product_category_mapper);

        //更新购物车价格
        $db->upgrade('cart', [
            'price' => $data['price'],
            'img' => $data['img'],
            'name' => $data['name'],
            'weight' => $data['weight'],
            'free_delivery' => $data['free_delivery']
        ], [
            'product_sn' => $product['product_sn'],
            'special_price' => 0
        ]);

        $links = array(
            array('link' => 'product.php', 'alt' => '产品列表'),
            array('link' => 'product.php?act=add', 'alt' => '添加产品'),
        );

        show_system_message('修改产品成功', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'gallery' == $opera ) {
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
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
            if( '' == $img_list[$key] ) {
                continue;
            }
            $insert_data[] = array(
                'original_img' => $img_list[$key],
                'order_view' => $order_view_list[$key],
                'big_img' => $img_list[$key],
                'thumb_img' => $img_list[$key],
                'product_sn' => $product_sn,
            );
        } else {
            if( '' == $img_list[$key] ) {
                continue;
            }
            $update_data[] = array(
                'original_img' => $img_list[$key],
                'order_view' => $order_view_list[$key],
                'big_img' => $img_list[$key],
                'thumb_img' => $img_list[$key],
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
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
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

if( 'check_inventory' == $opera ) {
    $response = array('error' => 1, 'msg' => '', 'errmsg' => array());
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
        $response['msg'] = '权限不足';
        echo json_encode($response);
        exit;
    }
    $sn = trim(getPOST('sn'));
    $type = intval(getPOST('type'));
    if( '' == $sn ) {
        $response['msg'] = '参数错误';
        echo json_encode($response);
        exit;
    }
    if( 0 >= $type ) {
        $response['msg'] = '参数错误';
        echo json_encode($response);
        exit;
    }
    $sn = $db->escape($sn);

    $get_product_type_id = 'select product_type_id from '.$db->table('product');
    $get_product_type_id .= ' where product_sn = \''.$sn.'\' and is_virtual = 0 limit 1';
    $product_type_id = $db->fetchOne($get_product_type_id);

    if( empty($product_type_id) ) {
        $response['msg'] = '产品不存在';
        echo json_encode($response);
        exit;
    }

    $get_inventory_list = 'select * from '.$db->table('inventory').' where product_sn = \''.$sn.'\'';
    $inventory_list = $db->fetchAll($get_inventory_list);
    //添加同类型属性
    if( $type == $product_type_id ) {
        if( $inventory_list ) {
            //空属性且有待发库存
            if( count($inventory_list) == 1 && $inventory_list[0]['attributes'] == '' && $inventory_list[0]['inventory_await'] > 0 ) {
                $response['msg'] = '有产品待发，无法添加新属性';
            } else {
               $response['error'] = 0;
            }
        } else {
            $response['msg'] = '系统繁忙，请稍后重试';
            echo json_encode($response);
            exit;
        }
    } else {
        if( $inventory_list ) {
            //存在待发库存则不能变换类型
            $addible = true;
            foreach( $inventory_list as $inventory ) {
                if( $inventory['inventory_await'] > 0 ) {
                    $addible = false;
                    break;
                }
            }
            if( $addible ) {
                $response['error'] = 0;
            } else {
                $response['msg'] = '有产品待发，无法添加新属性';
            }

        } else {
            $response['msg'] = '系统繁忙，请稍后重试';
        }
    }
    echo json_encode($response);
    exit;


}

if( 'add_attr' == $opera ) {
    $response = array('error' => 1, 'msg' => '', 'errmsg' => array());
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
        $response['msg'] = '权限不足';
        echo json_encode($response);
        exit;
    }
    $sn = trim(getPOST('sn'));
    $type = intval(getPOST('type'));
    $attr = trim(getPOST('attr'));
    $inventory = intval(getPOST('inventory'));
    if( '' == $sn ) {
        $response['msg'] = '参数错误';
        echo json_encode($response);
        exit;
    }
    if( 0 >= $type ) {
        $response['msg'] = '参数错误';
        echo json_encode($response);
        exit;
    }
    $sn = $db->escape($sn);
    $attr = $db->escape($attr);
    $inventory = ( 0 > $inventory ) ? 0 : $inventory;

    $get_product_type_id = 'select product_type_id from '.$db->table('product');
    $get_product_type_id .= ' where product_sn = \''.$sn.'\' and is_virtual = 0 limit 1';
    $product_type_id = $db->fetchOne($get_product_type_id);

    if( empty($product_type_id) ) {
        $response['msg'] = '产品不存在';
        echo json_encode($response);
        exit;
    }

    $get_inventory_list = 'select * from '.$db->table('inventory').' where product_sn = \''.$sn.'\'';
    $inventory_list = $db->fetchAll($get_inventory_list);
    //添加同类型属性
    if( $type == $product_type_id ) {
        if( $inventory_list ) {
            //空属性且有待发库存
            if( count($inventory_list) == 1 && $inventory_list[0]['attributes'] == '' && $inventory_list[0]['inventory_await'] > 0 ) {
                $response['msg'] = '有产品待发，无法添加新属性';
            } else {
                //可添加
                $data = array(
                    'product_sn' => $product_sn,
                    'attributes' => $attr,
                    'inventory' => $inventory,
                    'inventory_logic' => $inventory,
                );

                if( $db->autoInsert('inventory', array($data)) ) {
                    $response['error'] = 0;
                } else {
                    $response['msg'] = '系统繁忙，请稍后重试';
                }
            }
        } else {
            $response['msg'] = '系统繁忙，请稍后重试';
        }
    } else {
        //可添加，删除原来所有，事务
        $db->begin();
        $transaction = true;

        $delete_inventory = 'delete from '.$db->table('inventory').' where product_sn = \''.$sn.'\'';
        if( !$db->delete($delete_inventory) ) {
            $transaction = false;
        }

        $data = array(
            'product_sn' => $product_sn,
            'attributes' => $attr,
            'inventory' => $inventory,
            'inventory_logic' => $inventory,
        );

        if( !$db->autoInsert('inventory', array($data)) ) {
            $transaction = false;
        }

        if( $transaction ) {
            $db->commit();
            $response['error'] = 0;
        } else {
            $db->rollback();
            $response['msg'] = '系统繁忙，请稍后重试';
        }
    }
    echo json_encode($response);
    exit;
}

//===============================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_product_view', $_SESSION['business_purview']) ) {
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
        $and_where .= ' and name like \'%'.$keyword.'%\' || `product_sn like` \'%'.$keyword.'%\'';
    }
    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('product');
    $get_total .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_total .= $and_where;
    $get_total .= ' and is_virtual = 0';    //实体产品
    $total = $db->fetchOne($get_total);
    $total_page = ceil( $total / $count );

    $page = intval(getGET('page'));
    $page = ( $page > $total_page ) ? $total_page : $page;
    $page = ( 0 >= $page ) ? 1 : $page;

    create_pager($page, $total_page, $total);
    assign('count', $count);
    assign('keyword', $keyword);

    $offset = ($page - 1) * $count;


    $get_product_list = 'select * from '.$db->table('product');
    $get_product_list .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product_list .= $and_where;
    $get_product_list .= ' and is_virtual = 0';    //实体产品
    $get_product_list .= ' order by order_view asc, id desc';
    $get_product_list .= ' limit '.$offset.','.$count;
    $product_list = $db->fetchAll($get_product_list);

    if($product_list) {
        foreach($product_list as &$_product) {
            $category_id_list = $db->all('product_category_mapper', ['category_id'], ['product_sn' => $_product['product_sn']]);
            $category_ids = [];
            while($mapper_item = array_shift($category_id_list)) {
                array_push($category_ids, $mapper_item['category_id']);
            }

            $category_list = $db->all('category', ['name'], ['id' => ['exp', 'in ('.implode(',', $category_ids).')']]);

            $categories = [];
            while($category_alias = array_shift($category_list)) {
                array_push($categories, $category_alias['name']);
            }
            $_product['category_name'] = implode(', ', $categories);
        }
    }

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
    if( !check_purview('pur_product_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where business_account = \'\' and parent_id > 0';
    $get_category_list .= ' order by `path` ASC';
    $category_list = $db->fetchAll($get_category_list);
    $target_category_list = array();
    if( $category_list ) {
        foreach ($category_list as $key => $category) {
            $count = count(explode(',', $category['path']));
            /*
            if( $count != 4 ) {
                continue;
            }
            */
            if ($count > 2) {
                $temp = '|--' . $category['name'];
                $count = 1;
                while ($count--) {
                    $temp = '&nbsp;&nbsp;' . $temp;
                }

                $category['name'] = $temp;
            }
            $target_category_list[$key] = $category;
        }
    }
    assign('category_list', $target_category_list);

    $get_business_category = 'select `category_id` from '.$db->table('business');
    $get_business_category .= ' where business_account = \''.$_SESSION['business_account'].'\' limit 1';
    $business_category = $db->fetchOne($get_business_category);
    $get_prefix_path = 'select `path` from '.$db->table('category').' where `id` = \''.$business_category.'\'';
    $prefix_path = $db->fetchOne($get_prefix_path);

    $get_shop_category_list = 'select * from '.$db->table('category');
    $get_shop_category_list .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_shop_category_list .= ' order by `path` ASC';
    $shop_category_list = $db->fetchAll($get_shop_category_list);
    if( $shop_category_list ) {
        foreach ($shop_category_list as $key => $category) {
            $category['path'] = str_replace($prefix_path, '', $category['path']);
            $count = count(explode(',', $category['path']));
            if ($count > 1) {
                $temp = '|--' . $category['name'];
                while ($count--) {
                    $temp = '&nbsp;&nbsp;' . $temp;
                }

                $category['name'] = $temp;
            }
            $shop_category_list[$key] = $category;
        }
    }
    assign('shop_category_list', $shop_category_list);

    $get_product_type_list = 'select  * from '.$db->table('product_type').' where 1 order by id asc';
    $product_type_list = $db->fetchAll($get_product_type_list);
    assign('product_type_list', $product_type_list);

    $get_product_attr_list = 'select * from '.$db->table('product_attributes').' where 1 order by product_type_id asc, id asc';
    $product_attr_list = $db->fetchAll($get_product_attr_list);

    $target_attr_list = array();
    $length = count($product_attr_list);
    for( $i = 0; $i < $length; ) {
        $pid = $product_attr_list[$i]['product_type_id'];
        $temp = array();
        do {
            $temp[] = $product_attr_list[$i];
            $i++;
        } while( $i < $length && $pid == $product_attr_list[$i]['product_type_id'] );
        $target_attr_list[$pid] = $temp;
    }
    assign('json_attr_list', json_encode($target_attr_list));


    $get_brand_list = 'select * from '.$db->table('brand').' where 1 order by id asc';
    $brand_list = $db->fetchAll($get_brand_list);
    assign('brand_list', $brand_list);


}

if( 'release' == $act ) {
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
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
    $get_product .= ' and is_virtual = 0';
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
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
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
    $get_product .= ' and is_virtual = 0';  //实体产品
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
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $get_product = 'select a.*, b.name as brand_name from '.$db->table('product').' as a';
    $get_product .= ' left join '.$db->table('brand').' as b on a.brand_id = b.id';
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and is_virtual = 0';  //实体产品
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
    $product['img_src'] = $product['img'];
    assign('product', $product);


    $inventory = 0;
    $get_attributes = 'select * from '.$db->table('inventory');
    $get_attributes .= ' where product_sn = \''.$product_sn.'\'';
    $product_attributes = $db->fetchAll($get_attributes);
//    var_dump($product_attributes);exit;
    $has_attr = false;
    if( $product_attributes ) {
        foreach( $product_attributes as $key => $attributes ) {
            if( $attributes['attributes'] ) {
                $has_attr = true;
            }
            $product_attributes[$key]['attributes'] = json_decode($attributes['attributes']);
        }
    } else {
        $product_attributes = array();
    }

    if(count($product_attributes) == 1)
    {
        $inventory = $product_attributes[0]['inventory'];
    }

    assign('has_attr', $has_attr);
    assign('inventory', $inventory);
    assign('attributes', json_encode($product_attributes));

    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where business_account = \'\' and parent_id > 0';
    $get_category_list .= ' order by `path` ASC';
    $category_list = $db->fetchAll($get_category_list);
    $target_category_list = array();
    if( $category_list ) {
        foreach ($category_list as $key => $category) {
            $count = count(explode(',', $category['path']));
            /*
            if( $count != 4 ) {
                continue;
            }
            */
            if ($count > 2) {
                $temp = '|--' . $category['name'];
                $count = 1;
                while ($count--) {
                    $temp = '&nbsp;&nbsp;' . $temp;
                }

                $category['name'] = $temp;
            }
            $target_category_list[$key] = $category;
        }
    }
    assign('category_list', $target_category_list);

    $category_alias = $db->all('product_category_mapper', ['category_id'], ['product_sn' => $product['product_sn']]);
    assign('category_alias', $category_alias);

    $get_business_category = 'select `category_id` from '.$db->table('business');
    $get_business_category .= ' where business_account = \''.$_SESSION['business_account'].'\' limit 1';
    $business_category = $db->fetchOne($get_business_category);
    $get_prefix_path = 'select `path` from '.$db->table('category').' where `id` = \''.$business_category.'\'';
    $prefix_path = $db->fetchOne($get_prefix_path);

    $get_shop_category_list = 'select * from '.$db->table('category');
    $get_shop_category_list .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_shop_category_list .= ' order by `path` ASC';
    $shop_category_list = $db->fetchAll($get_shop_category_list);
    if( $shop_category_list ) {
        foreach ($shop_category_list as $key => $category) {
            $category['path'] = str_replace($prefix_path, '', $category['path']);
            $count = count(explode(',', $category['path']));
            if ($count > 1) {
                $temp = '|--' . $category['name'];
                while ($count--) {
                    $temp = '&nbsp;&nbsp;' . $temp;
                }

                $category['name'] = $temp;
            }
            $shop_category_list[$key] = $category;
        }
    }
    assign('shop_category_list', $shop_category_list);

    $get_product_type_list = 'select  * from '.$db->table('product_type').' where 1 order by id asc';
    $product_type_list = $db->fetchAll($get_product_type_list);
    assign('product_type_list', $product_type_list);

    $get_product_attr_list = 'select * from '.$db->table('product_attributes').' where product_type_id = \''.$product['product_type_id'].'\' order by product_type_id asc, id asc';
    $product_attr_list = $db->fetchAll($get_product_attr_list);

    $target_attr_list = array();
    $length = count($product_attr_list);
    for( $i = 0; $i < $length; ) {
        $pid = $product_attr_list[$i]['product_type_id'];
        $temp = array();
        do {
            $temp[] = $product_attr_list[$i];
            $i++;
        } while( $i < $length && $pid == $product_attr_list[$i]['product_type_id'] );
        $target_attr_list[$pid] = $temp;
    }
    assign('attr_list', $target_attr_list);
    assign('json_attr_list', json_encode($target_attr_list));

    $get_brand_name = 'select `name` from '.$db->table('brand').' where `id`='.$product['brand_id'].' order by id asc';
    $brand_name = $db->fetchOne($get_brand_name);
    assign('brand_name', $brand_name);

}

if( 'gallery' == $act ) {
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
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
    $get_product .= ' and is_virtual = 0';  //实体产品
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
            if( file_exists(realpath('../'.$gallery['original_img'])) ) {
                $gallery_list[$key]['original_img_src'] = $gallery['original_img'];
            } else {
                $gallery_list[$key]['original_img_src'] = $gallery['original_img'];
            }

        }
        for( $i = $count; $i < $gallery_count; $i++ ) {
            $gallery_list[$i]['id'] = '';
            $gallery_list[$i]['original_img'] = '';
            $gallery_list[$i]['original_img_src'] = '../upload/image/no-image.png';
            $gallery_list[$i]['order_view'] = '';
        }
    } else {
        for( $i = 0; $i < $gallery_count; $i++ ) {
            $gallery_list[$i]['id'] = '';
            $gallery_list[$i]['original_img'] = '';
            $gallery_list[$i]['original_img_src'] = '../upload/image/no-image.png';
            $gallery_list[$i]['order_view'] = '';
        }
    }

    assign('product_sn', $product_sn);
    assign('gallery_list', $gallery_list);

}

if( 'del-gallery' == $act ) {
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        redirect('product.php?act=gallery');
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
        redirect('product.php?act=gallery&sn='.$gallery['product_sn']);
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'delete' == $act ) {
    if( !check_purview('pur_product_del', $_SESSION['business_purview']) ) {
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
    $get_product .= ' and is_virtual = 0';  //实体产品
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
            array('link' => 'product.php', 'alt' => '产品列表'),
            array('link' => 'product.php?act=cycle', 'alt' => '回收站'),
        );

        show_system_message('产品'.$product['product_sn'].'已移入回收站', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }

}

if( 'cycle' == $act ) {
    if( !check_purview('pur_product_del', $_SESSION['business_purview']) ) {
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
    $get_total .= ' and is_virtual = 0';  //实体产品
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
    $get_product_list .= ' and is_virtual = 0';  //实体产品
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
    if( !check_purview('pur_product_del', $_SESSION['business_purview']) ) {
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
    $get_product .= ' and is_virtual = 0';  //实体产品
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
            array('link' => 'product.php', 'alt' => '产品列表'),
            array('link' => 'product.php?act=cycle', 'alt' => '回收站'),
        );

        show_system_message('产品'.$product['product_sn'].'已移出回收站', $links);
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}

if( 'remove' == $act ) {
    if( !check_purview('pur_product_del', $_SESSION['business_purview']) ) {
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
    $get_product .= ' and is_virtual = 0';  //实体产品
    $get_product .= ' limit 1';
    $product  = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array());
        exit;
    }
    if( $product['status'] != 5 ) {
        show_system_message('产品未被删除', array(array('link' => 'product.php', 'alt' => '产品列表')));
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

    $delete_gallery = 'delete from '.$db->table('gallery');
    $delete_gallery .= ' where product_sn = \''.$product_sn.'\'';
    $delete_gallery .= ' limit 1';
    if( !$db->delete($delete_gallery) ) {
        $transaction = false;
    }

    $delete_inventory = 'delete from '.$db->table('inventory');
    $delete_inventory .= ' where product_sn = \''.$product_sn.'\'';
    if( !$db->delete($delete_inventory) ) {
        $transaction = false;
    }

    $db->destroy('cart', ['product_sn' => $product_sn]);

    if( $transaction ) {
        $links = array(
            array('link' => 'product.php', 'alt' => '产品列表'),
            array('link' => 'product.php?act=cycle', 'alt' => '回收站'),
            array('link' => 'product.php?act=add', 'alt' => '添加产品'),
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
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
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
    $get_product .= ' and is_virtual = 0';  //实体产品
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

if('mini_qr_code' == $act) {
    if( !check_purview('pur_product_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $product_sn = trim(getGET('sn'));
    if( '' == $product_sn ) {
        show_system_message('参数错误', array());
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $product = $db->find('product', ['id'], ['product_sn' => $product_sn]);

    if($product) {
        header('Content-type:image/png');
        echo get_min_program_qrcode('product_id='.$product['id'], 'pages/product/detail');
        exit;
    } else {
        exit;
    }
}

$template .= $act.'.phtml';
$smarty->display($template);

