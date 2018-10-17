<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/22
 * Time: 上午7:17
 */
include 'library/init.inc.php';

$operation = 'edit';
$opera = check_action($operation, getPOST('opera'));

$action = 'product_edit';
$act = check_action($action, getGET('act'), 'view');

$template = 'self_shop.phtml';

//获取用户信息
$get_user_info = 'select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user_info = $db->fetchRow($get_user_info);

if('edit' == $opera) {
    $response = array('error' => -1, 'msg' => '');

    if(!check_cross_domain()) {
        $shop_name = getPOST('shop_name');
        $shop_name = trim($shop_name);

        if($shop_name == '') {
            $response['msg'] .= '-请填写店铺名称<br/>';
        } else {
            $shop_name = $db->escape($shop_name);
        }

        if($user_info['level_id'] <= 0) {
            $response['msg'] .= '-成为商业会员后才能获得店铺<br/>';
        }

        if($response['msg'] == '') {
            $shop_config = $db->fetchRow('select * from '.$db->table('member_shop').' where `account`=\''.$_SESSION['account'].'\'');

            if(empty($shop_config)) {
                $shop_config_data = array(
                    'logo' => $user_info['headimg'],
                    'name' => $shop_name,
                    'account' => $_SESSION['account'],
                    'add_time' => time()
                );

                if($db->autoInsert('member_shop', array($shop_config_data))) {
                    $response['msg'] = '设置店铺名称成功';
                    $response['shop_name'] = $shop_name;
                    $response['error'] = 0;
                } else {
                    $response['msg'] = '系统繁忙，请稍后再试';
                }
            } else {
                $shop_config_data = array(
                    'name' => $shop_name
                );

                if($db->autoUpdate('member_shop', $shop_config_data, '`account`=\''.$_SESSION['account'].'\'') !== false) {
                    $response['msg'] = '设置店铺名称成功';
                    $response['shop_name'] = $shop_name;
                    $response['error'] = 0;
                } else {
                    $response['msg'] = '系统繁忙，请稍后再试';
                }
            }
        }
    }

    echo json_encode($response);
    exit;
}

//=================================================================================================================

if($user_info['level_id'] <= 0) {
    redirect('index.php');
}

assign('user_info', $user_info);

if('product_edit' == $act) {
    $template = 'self_shop_edit_product.phtml';

    //获取全部商品
    $now = time();
    $get_product_list = 'select if(d.`account` is not null, 1, 0) as distributed,p.`product_sn`,p.`name`,p.`img`,p.`id`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price` from '.$db->table('product').' as p '.
        'left join'.$db->table('distribution').' as d '.
        ' on d.`product_sn`=p.`product_sn` and d.`account`=\''.$_SESSION['account'].'\' where p.`status`=4 order by d.`add_time` DESC';

    $product_list = $db->fetchAll($get_product_list);
    assign('product_list', $product_list);
    assign('title', '商品分销');
}

if('view' == $act) {
    $get_product_list = '';
    $user = null;
    if ($user_info) {
        $get_product_sns = 'select `product_sn` from ' . $db->table('distribution') . ' where `account`=\'' . $user_info['account'] . '\'';
        $product_sns = $db->fetchAll($get_product_sns);

        if ($product_sns) {
            $_product_sns = array();
            foreach ($product_sns as $_product_sn) {
                array_push($_product_sns, $_product_sn['product_sn']);
            }

            $product_sns = $_product_sns;
            $product_sn_str = implode('\', \'', $product_sns);
            $get_product_list = 'select `name`,if(`promote_end`>' . time() . ',`promote_price`,`price`) as `price`,`id`,`img` from ' . $db->table('product') . ' where `product_sn` in (\'' . $product_sn_str . '\')';
        }
    }

    $shop_config = $db->fetchRow('select * from '.$db->table('member_shop').' where `account`=\''.$_SESSION['account'].'\'');
    if(empty($shop_config)) {
        $shop_config = array(
            'name' => $user_info['nickname'].'的店铺',
            'logo' => $user_info['headimg'],
            'header_bg' => ''
        );
    }
    assign('shop_config', $shop_config);

//获取平台分类
    $get_category = 'select `id`,`name` from ' . $db->table('category') . ' where `business_account`=\'\' and `parent_id`=0';
    $category = $db->fetchAll($get_category);
    foreach ($category as $key => $c) {
        $get_children = 'select `id`,`name` from ' . $db->table('category') . ' where `parent_id`=' . $c['id'];

        $category[$key]['children'] = $db->fetchAll($get_children);
    }

//获取轮播广告
    $get_cycle_ad = 'select `img`,`url` from ' . $db->table('ad') . ' where `ad_pos_id`=1 order by `order_view`';
    $cycle_ad = $db->fetchAll($get_cycle_ad);
    assign('cycle_ad', $cycle_ad);

    $now = time();
    assign('category', $category);
//获取商家全部产品
    if ($get_product_list != '') {
        $product_list = $db->fetchAll($get_product_list);
        assign('product_list', $product_list);
    } else {
        assign('product_list', null);
    }
//获取新添加的产品
    $get_product_list = 'select `name`,if(`promote_end`>' . $now . ',`promote_price`,`price`) as `price`,`id`,`img` from ' . $db->table('product') . ' where `status`=4 and `add_time`>' . (time() - 3600 * 24 * 7);
    $new_product = $db->fetchAll($get_product_list);
    assign('new_product_count', count($new_product));

//获取猜你喜欢
    $get_fav_products = 'select `name`,if(`promote_end`>' . $now . ',`promote_price`,`price`) as `price`,`img`,`id` from ' . $db->table('product') . ' where `status`=4 order by `add_time` DESC limit 3';
    $fav_products = $db->fetchAll($get_fav_products);
    assign('fav_products', $fav_products);
}

$smarty->display($template);