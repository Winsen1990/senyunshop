<?php
/**
 *
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-02
 * @version 1.0.0
 */

include 'library/init.inc.php';

if( !isset($_SESSION['business_account']) ) {
    echo json_decode(array(
        'error' => 1,
        'message' => '请先登陆',
    ));
    exit;
}

if( check_cross_domain() ) {
    echo json_decode(array(
        'error' => 1,
        'message' => '请从本站提交数据',
    ));
    exit;
}

$operation = 'order_detail|edit_attr|add_attr|delete_attr|add_content|edit_content|delete_content|get_attr';
$opera = check_action($operation, getPOST('opera'));

//编辑消费内容
if( 'edit_content' == $opera ) {
    $product_sn = trim(getPOST('sn'));
    $content = trim(getPOST('content'));
    $count = trim(getPOST('count'));
    $total = trim(getPOST('total'));
    $id = intval(getPOST('id'));

    if( '' == $product_sn ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $product_sn = $db->escape($product_sn);
    if( 0 >= $id ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }

    $content = $db->escape($content);
    $count = $db->escape($count);
    $total = $db->escape($total);

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and product_sn = \''.$product_sn.'\' and is_virtual = 1 limit 1';

    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '产品不存在',
        ));
        exit;
    }

    $data = array(
        'content' => $content,
        'count' => $count,
        'total' => $total,
    );

    $where = 'id = '.$id.' and product_sn = \''.$product_sn.'\' limit 1';

    $get_content = 'select `id` from '.$db->table('virtual_content');
    $get_content .= $where;

    $exists = $db->fetchRow($get_content);
    if( empty($exists) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '消费内容不存在',
        ));
        exit;
    } else {
        if( $db->autoUpdate('virtual_content', $data, $where, '', 1) ) {
            echo json_encode(array(
                'error' => 0,
                'message' => '消费内容更新成功',
            ));
            exit;
        } else {
            echo json_encode(array(
                'error' => 1,
                'message' => '系统繁忙，请稍后重试',
            ));
            exit;
        }
    }



}


//增加消费内容
if( 'add_content' == $opera )  {
    $product_sn = trim(getPOST('sn'));
    $content = trim(getPOST('content'));
    $count = trim(getPOST('count'));
    $total = trim(getPOST('total'));

    if( '' == $product_sn ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $product_sn = $db->escape($product_sn);

    $content = $db->escape($content);
    $count = $db->escape($count);
    $total = $db->escape($total);

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and product_sn = \''.$product_sn.'\' and is_virtual = 1 limit 1';

    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '产品不存在',
        ));
        exit;
    }

    $data = array(
        'product_sn' => $product_sn,
        'content' => $content,
        'count' => $count,
        'total' => $total,
    );

    if( $db->autoInsert('virtual_content', array($data)) ) {
        echo json_encode(array(
            'error' => 0,
            'message' => '增加消费内容成功',
        ));
        exit;
    } else {
        echo json_encode(array(
            'error' => 1,
            'message' => '系统繁忙，请稍后重试',
        ));
        exit;
    }
}

//删除消费内容
if( 'delete_content' == $opera ) {
    $product_sn = trim(getPOST('sn'));
    $id = intval(getPOST('id'));

    if( '' == $product_sn ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $product_sn = $db->escape($product_sn);
    if( 0 >= $id ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and product_sn = \''.$product_sn.'\' and is_virtual = 1 limit 1';

    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '产品不存在',
        ));
        exit;
    }

    $get_content = 'select * from '.$db->table('virtual_content');
    $get_content .= ' where id = '.$id;
    $get_content .= ' and product_sn = \''.$product_sn.'\' limit 1';

    $content = $db->fetchRow($get_content);
    if( empty($content) ) {
        echo json_encode(array(
            'error' => 1,
//            'message' => $get_content
            'message' => '消费内容不存在',
        ));
        exit;
    }

    $delete_content = 'delete from '.$db->table('virtual_content');
    $delete_content .= ' where id = '.$id;
    $delete_content .= ' and product_sn = \''.$product_sn.'\' limit 1';

    if( $db->delete($delete_content) ) {
        echo json_encode(array(
            'error' => 0,
            'message' => '删除消费内容成功',
        ));
        exit;
    } else {
        echo json_encode(array(
            'error' => 1,
            'message' => '系统繁忙，请稍后重试',
        ));
        exit;
    }
}

if( 'order_detail' == $opera ) {

    $status_str = array(
        1 => '待支付',
        2 => '支付中',
        3 => '支付完成',
        4 => '待发货',
        5 => '配货中',
        6 => '已发货',
        7 => '已收货',
        8 => '申请退单',
        9 => '退单中',
        10 => '已退单',
        11 => '无效订单',
    );


    $order_sn = trim(getPOST('sn'));

    if( '' == $order_sn ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $order_sn = $db->escape($order_sn);

    $get_order = 'select a.*, p.province_name, city.city_name, d.district_name, g.group_name, e.name as express_name from '.$db->table('order').' as a';
    $get_order .= ' left join '.$db->table('province').' as p on a.province = p.id';
    $get_order .= ' left join '.$db->table('city').' as city on a.city = city.id';
    $get_order .= ' left join '.$db->table('district').' as d on a.district = d.id';
    $get_order .= ' left join '.$db->table('group').' as g on a.group = g.id';
    $get_order .= ' left join '.$db->table('express').' as e on a.express_id = e.id';

    $get_order .= ' where `business_account` = \''.$_SESSION['business_account'].'\'';
    $get_order .= ' and order_sn = \''.$order_sn.'\'';
    $get_order .= ' limit 1';

    $order = $db->fetchRow($get_order);

    if( empty($order) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '订单不存在',
        ));
        exit;
    }

    $order['add_time_str'] = $order['add_time'] ? date('Y-m-d H:i:s', $order['add_time']) : '';
    $order['delivery_time_str'] = $order['delivery_time'] ? date('Y-m-d H:i:s', $order['delivery_time']) : '未发货';
    $order['receive_time_str'] = $order['receive_time'] ? date('Y-m-d H:i:s', $order['receive_time']) : '未收货';
    $order['pay_time_str'] = $order['pay_time'] ? date('Y-m-d H:i:s', $order['pay_time']) : '未支付';
    $order['status_str'] = $status_str[$order['status']];

    $get_order_detail = 'select o.*, p.img from '. $db->table('order_detail').' as o';
    $get_order_detail .= ' left join '.$db->table('product').' as p on o.product_sn = p.product_sn';
    $get_order_detail .= ' where o.business_account = \''.$_SESSION['business_account'].'\'';
    $get_order_detail .= ' and o.order_sn = \''.$order_sn.'\'';

    $order_detail = $db->fetchAll($get_order_detail);

    echo json_encode(array(
        'error' => 0,
        'order' => $order,
        'order_detail' => $order_detail,
        'message' => '成功',
    ));exit;
}

//更新产品属性
if( 'edit_attr' == $opera ) {
    $product_sn = trim(getPOST('sn'));
    $id = intval(getPOST('id'));
    $inventory = intval(getPOST('inventory'));
    $attr = trim(getPOST('attr'));

    if( '' == $product_sn ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $product_sn = $db->escape($product_sn);
    if( '' == $attr ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $attr = $db->escape($attr);

    if( 0 >= $id ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }

    $inventory = ( 0 > $inventory ) ? 0 : $inventory;

    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and product_sn = \''.$product_sn.'\' limit 1';

    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '产品不存在',
        ));
        exit;
    }

    $get_attribute = 'select * from '.$db->table('inventory');
    $get_attribute .= ' where id = '.$id;
    $get_attribute .= ' and product_sn = \''.$product_sn.'\' limit 1';

    $attribute = $db->fetchRow($get_attribute);

    $data = array(
        'attributes' => $attr,
//        'product_sn' => $product_sn,
        'inventory' => $inventory,
        'inventory_logic' => $inventory,
    );
    $table = 'inventory';
    $where = 'id = '.$id;

    if( empty($attribute) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '产品属性不存在',
        ));
        exit;
    } else {
        if(modify_inventory($product_sn, $attribute['attributes'], $inventory)) {
            echo json_encode(array(
                'error' => 0,
                'message' => '产品属性更新成功',
            ));
            exit;
        } else {
            echo json_encode(array(
                'error' => 1,
                'message' => '系统繁忙，请稍后重试',
            ));
            exit;
        }
    }
}

//增加产品属性
if( 'add_attr' == $opera ) {
    $product_sn = trim(getPOST('sn'));
    $attr = trim(getPOST('attr'));
    $inventory = intval(getPOST('inventory'));

    if( '' == $product_sn ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $product_sn = $db->escape($product_sn);
    if( '' == $attr ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $attr = $db->escape($attr);
    $inventory = ( 0 > $inventory ) ? 0 : $inventory;

    $data = array(
        'product_sn' => $product_sn,
        'attributes' => $attr,
        'inventory' => $inventory,
        'inventory_logic' => $inventory,
    );

    if( $db->autoInsert('inventory', array($data)) ) {
        echo json_encode(array(
            'error' => 0,
            'message' => '添加产品属性成功',
            'id' => $db->get_last_id(),
        ));
        exit;
    } else {
        echo json_encode(array(
            'error' => 1,
            'message' => '系统繁忙，请稍后重试',
        ));
        exit;
    }


}

//删除产品属性
if( 'delete_attr' == $opera ) {
    $product_sn = trim(getPOST('sn'));
    $id = intval(getPOST('id'));
    $inventory = intval(getPOST('inventory'));

    if( '' == $product_sn ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $product_sn = $db->escape($product_sn);
    if( 0 >= $id ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $inventory = ( 0 > $inventory ) ? 0 : $inventory;


    $get_product = 'select * from '.$db->table('product');
    $get_product .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_product .= ' and product_sn = \''.$product_sn.'\' limit 1';

    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '产品不存在',
        ));
        exit;
    }

    $get_attribute = 'select * from '.$db->table('inventory');
    $get_attribute .= ' where id = '.$id;
    $get_attribute .= ' and product_sn = \''.$product_sn.'\' limit 1';

    $attribute = $db->fetchRow($get_attribute);
    if( empty($attribute) ) {
        echo json_encode(array(
            'error' => 1,
//            'message' => $get_attribute
            'message' => '产品属性不存在',
        ));
        exit;
    }

    $delete_attribute = 'delete from '.$db->table('inventory');
    $delete_attribute .= ' where id = '.$id;
    $delete_attribute .= ' and product_sn = \''.$product_sn.'\' limit 1';

    if( $db->delete($delete_attribute) ) {
        echo json_encode(array(
            'error' => 0,
            'message' => '删除产品属性成功',
        ));
        exit;
    } else {
        echo json_encode(array(
            'error' => 1,
            'message' => '系统繁忙，请稍后重试',
        ));
        exit;
    }
}

//获取某个分类下的产品属性名
if( 'get_attr' == $opera ) {
    $type_id = intval(getPOST('id'));
    if( 0 >= $type_id ) {
        echo json_encode(array(
            'error' => 1,
            'message' => '参数错误',
        ));
        exit;
    }
    $get_attr_list = 'select * from '.$db->table('product_attributes').' where product_type_id = \''.$type_id.'\'';
    $attr_list = $db->fetchAll($get_attr_list);
    if( $attr_list ) {

        $target = array();
        foreach( $attr_list as $key => $attr ) {
            $target[$attr['id']] = $attr;
        }

        echo json_encode(array(
            'error' => 0,
            'message' => '参数错误',
            'data' => $target,
        ));
        exit;
    }
}

echo json_encode(array(
    'error' => 1,
    'message' => '系统繁忙，请稍后重试',
));
exit;
