<?php
/**
 * 产品管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-09-06
 * @version 1.0.0
 */

include 'library/init.inc.php';
back_base_init();

$template = 'product/';
assign('subTitle', '产品管理');

$action = 'view|exam|reject|set|unset|activity';
$operation = 'exam|reject|activity';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//===========================================================================
if( 'activity' == $opera ) {
    if( !check_purview('pur_product_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_product = 'select * from '.$db->table('product').' where id = \''.$id.'\' and status = 4 limit 1';
    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    $db->autoDelete('activity_mapper', '`product_sn`=\''.$product['product_sn'].'\'');
    $activity_id_arr = array();
    $activity_id = getPOST('activity_id');
    foreach($activity_id as $aid)
    {
        $aid = intval($aid);

        if($aid > 0)
        {
            $activity_id_arr[] = array(
                'activity_id' => $aid,
                'product_sn' => $product['product_sn']
            );
        }
    }
    if( empty($activity_id_arr) ) {
        show_system_message('活动设置成功', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    if( $db->autoInsert('activity_mapper', $activity_id_arr) ) {
        show_system_message('活动设置成功', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }
}

if( 'exam' == $opera ) {
    if( !check_purview('pur_product_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_product = 'select * from '.$db->table('product').' where id = \''.$id.'\' and status = 2 and is_virtual = 0 limit 1';
    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    $get_account = 'select `account` from '.$db->table('business').' where `business_account` = \''.$product['business_account'].'\' limit 1';
    $product['account'] = $db->fetchOne($get_account);

    $reward = floatval(getPOST('reward'));
//    $integral = intval(getPOST('integral'));
    $integral_given = floatval(getPOST('integral_given'));
    $given_integral = floatval(getPost('given_integral'));
    if( 0 >= $reward ) {
        $reward = 0;
    }

    if( 0 > $integral_given ) {
        $integral_given = 0;
    }

    if( 0 > $given_integral) {
        $given_integral = 0;
    }

    $update_product = 'update '.$db->table('product').' set ';
    $update_product .= ' status = 4';
    $update_product .= ', prev_status = 2';
    $update_product .= ', reward = \''.$reward.'\'';
    $update_product .= ', integral_given = \''.$integral_given.'\'';
    $update_product .= ', given_integral = '.$given_integral;
    $update_product .= ' where id = \''.$id.'\' limit 1';

    if( $db->update($update_product) ) {

        //系统通知商户===
        $data = array(
            'title' => '产品审核',
            'content' => '您的产品，编号'.$product['product_sn'].'审核通过',
            'account' => $product['account'],
            'business_account' => $product['business_account'],
            'add_time' => time(),
            'status' => 0,  //未读
        );
        $db->autoInsert('message', array($data));

        //=============
        show_system_message('产品通过审核', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }
}

if( 'reject' == $opera ) {
    if( !check_purview('pur_product_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getPOST('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }
    $message = trim(getPOST('message'));
    if( '' == $message ) {
        show_system_message('请输入驳回理由', array());
        exit;
    }
    $message = $db->escape($message);

    $get_product = 'select * from '.$db->table('product').' where id = \''.$id.'\' and status = 2 limit 1';
    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    $get_account = 'select `account` from '.$db->table('business').' where `business_account` = \''.$product['business_account'].'\' limit 1';
    $product['account'] = $db->fetchOne($get_account);

    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    $update_product = 'update '.$db->table('product').' set ';
    $update_product .= ' status = 1';
    $update_product .= ' ,prev_status = 2';
    $update_product .= ' where id = \''.$id.'\' limit 1';

    if( $db->update($update_product) ) {

        //系统通知商户==================================
        $data = array(
            'title' => '产品审核',
            'content' => '您的产品，编号'.$product['product_sn'].'审核不通过。'.$message,
            'account' => $product['account'],
            'business_account' => $product['business_account'],
            'add_time' => time(),
            'status' => 0,  //未读
        );
        $db->autoInsert('message', array($data));

        //==============================================
        show_system_message('产品审核已驳回', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }
}


//===========================================================================

if( 'view' == $act ) {
    if( !check_purview('pur_product_view', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $where = '';

    $status = intval(getGET('status'));
    if( $status != 0 ) {
        switch($status) {
            case 1:
                $where .= ' where status = 2';
                break;
            case 2:
                $where .= ' where status = 3';
                break;
            case 3:
                $where .= ' where status = 4';
                break;
            default:
                $where .= ' where status = 0';
                break;
        }
    } else {
        $where .= ' where status > 1 and status < 5';
    }
    assign('status', $status);

    $keyword = trim(getGET('keyword'));
    if( '' != $keyword ) {
        $keyword = $db->escape($keyword);
        $where .= ' and a.name like \'%'.$keyword.'%\' or product_sn like \'%'.$keyword.'%\'';
    }
    $count = intval(getGET('count'));
    $count_array = array(10, 25, 50 , 100);
    $count = ( in_array($count, $count_array) ) ? $count : 10;

    $get_total = 'select count(*) from '.$db->table('product').' as a';
    $get_total .= $where;
    $get_total .= ' and is_virtual = 0';
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
    $get_product_list .= $where;
    $get_product_list .= ' and is_virtual = 0';
    $get_product_list .= ' order by order_view asc, id desc';
    $get_product_list .= ' limit '.$offset.','.$count;
    $product_list = $db->fetchAll($get_product_list);

    $status_array = array(
        1 => '待发布',
        2 => '待审核',
        3 => '已下架',
        4 => '已上架',
    );

    if( $product_list ) {
        foreach( $product_list as $key => $product ) {
            if( file_exists(realpath('..'.$product['img']))) {
                $product[$key]['img'] = '..'.$product['img'];
            }
            $product_list[$key]['status_str'] = $status_array[$product['status']];
            $product_list[$key]['exam_str'] = ( $product['status'] == 2 ) ? '审核' : '';

            //获取参与活动的情况
            $get_activity_name = 'select a.`name` from '.$db->table('activity_mapper').' as am join '.$db->table('activity').
                               ' as a on am.activity_id=a.id where am.product_sn=\''.$product['product_sn'].'\'';
            $activity_name = $db->fetchAll($get_activity_name);
            $activity_list = '';
            if($activity_name)
            {
                foreach($activity_name as $n)
                {
                    $activity_list .= $n['name'].'<br/>';
                }
            }
            $product_list[$key]['activity_list'] = $activity_list;
        }
    }
    assign('product_list', $product_list);

    if( $status == 1 ) {
        $exam_count = $total;
    } else {
        $get_exam_count = 'select count(*) from '.$db->table('product').' where status = 2  and is_virtual = 0';
        $exam_count = $db->fetchOne($get_exam_count);
    }
    assign('exam_count', $exam_count);
}

if( 'activity' == $act ) {
    if( !check_purview('pur_product_exam', $_SESSION['purview']) ) {
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
    $get_product .= ' where a.product_sn = \''.$product_sn.'\' and status = 4 limit 1';
    $product = $db->fetchRow($get_product);
    if( !$product ) {
        show_system_message('产品不存在', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    $product['promote_begin'] = ( $product['promote_begin'] ) ? date('Y-m-d H:i:s', $product['promote_begin']) : '';
    $product['promote_end'] = ( $product['promote_end'] ) ? date('Y-m-d H:i:s', $product['promote_end']) : '';
    $product['img_src'] = $product['img'];

    assign('product', $product);

    $get_gallery_list = 'select * from '.$db->table('gallery').' where product_sn = \''.$product_sn.'\' order by order_view asc';
    $gallery_list = $db->fetchAll($get_gallery_list);
    assign('gallery_list', $gallery_list);

    //获取活动列表
    $get_activity_list = 'select * from '.$db->table('activity');
    $activity_list = $db->fetchAll($get_activity_list);

    $get_mapper = 'select `activity_id` as aid from '.$db->table('activity_mapper').' where `product_sn`=\''.$product_sn.'\'';
    $mapper = $db->fetchAll($get_mapper);

    $activity_json = array();
    foreach ($activity_list as $key => $value) {
        $activity_json[$value['id']] = $value['name'];
    }

    $selected_activity = array();
    if($mapper) {
        foreach ($activity_list as $key => $value) {
            foreach ($mapper as $aid) {
                if ($value['id'] == $aid['aid']) {
                    $selected_activity[] = $value;
                    unset($activity_list[$key]);
                }
            }
        }
    }

    assign('activity_json', json_encode($activity_json));
    assign('selected_activity', $selected_activity);
    assign('activity_list', $activity_list);
}

if( 'exam' == $act ) {
    if( !check_purview('pur_product_exam', $_SESSION['purview']) ) {
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
    $get_product .= ' where a.product_sn = \''.$product_sn.'\' and status = 2 limit 1';
    $product = $db->fetchRow($get_product);
    if( !$product ) {
        show_system_message('产品不存在', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }
    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    $product['promote_begin'] = ( $product['promote_begin'] ) ? date('Y-m-d H:i:s', $product['promote_begin']) : '';
    $product['promote_end'] = ( $product['promote_end'] ) ? date('Y-m-d H:i:s', $product['promote_end']) : '';
    $product['img_src'] = $product['img'];

    assign('product', $product);

    $get_attributes = 'select `attributes` from '.$db->table('inventory');
    $get_attributes .= ' where product_sn = \''.$product_sn.'\'';
    $product_attributes = $db->fetchOne($get_attributes);
//    var_dump($product_attributes);exit;
    if( $product_attributes && $product_attributes != '') {
        foreach( $product_attributes as $key => $attributes ) {
            $product_attributes[$key]['attributes'] = json_decode($attributes['attributes']);
        }
    } else {
        $product_attributes = array();
    }

    assign('attributes', json_encode($product_attributes, JSON_UNESCAPED_UNICODE));

    $get_category_name = 'select `name` from '.$db->table('category');
    $get_category_name .= ' where business_account = \'\' and `id`='.$product['category_id'];
    $category_name = $db->fetchOne($get_category_name);

    assign('category_name', $category_name);

    $get_shop_category_name = 'select `name` from '.$db->table('category');
    $get_shop_category_name .= ' where `id`='.$product['shop_category_id'].' and business_account = \''.$product['business_account'].'\'';
    $shop_category_name = $db->fetchOne($get_shop_category_name);
    assign('shop_category_name', $shop_category_name);

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

    $get_gallery_list = 'select * from '.$db->table('gallery').' where product_sn = \''.$product_sn.'\' order by order_view asc';
    $gallery_list = $db->fetchAll($get_gallery_list);
    assign('gallery_list', $gallery_list);
}

if( 'reject' == $act ) {
    if( !check_purview('pur_product_exam', $_SESSION['purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }
    $id = intval(getGET('id'));
    if( 0 >= $id ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_product = 'select * from '.$db->table('product').' where id = \''.$id.'\' and status = 2 limit 1';
    $product = $db->fetchRow($get_product);
    if( empty($product) ) {
        show_system_message('产品不存在', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }

    if( $product['status'] == 5 ) {
        show_system_message('产品已被删除', array(array('link' => 'product.php', 'alt' => '产品管理')));
        exit;
    }
    assign('id', $id);
}


$template .= $act.'.phtml';
$smarty->display($template);

