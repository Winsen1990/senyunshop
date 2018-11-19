<?php
/**
 * 优惠券管理
 * @author Winsen
 * @email airplace1@gmail.com
 * @date 2018-7-11
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $db, $log, $smarty;

business_base_init();

$template = 'coupon/';
assign('subTitle', '优惠券管理');

$action = 'edit|add|view|delete|shop_selector|category_selector|product_selector';

$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'view' : $act;

$coupon_types = [
    1 => '折扣券',
    2 => '代金券',
    3 => '满减券',
];

$coupon_status = [
    0 => '停用',
    1 => '启用',
];

//调用位置
$hook_list = [
    'after_trade' => '交易完成后',
    'manual' => '手动领取'
];

if('view' == $act) {
    if (!check_purview('pur_coupon_view', $_SESSION['business_purview'])) {
        show_system_message('权限不足', array());
        exit;
    }

    $page = intval(getGET('page'));
    $page = max(1, $page);
    $page_count = intval(getGET('count'));
    $page_count = max(10, $page_count);
    assign('count', $page_count);

    $begin_time = getGET('begin_time');
    $end_time = getGET('end_time');

    if(!empty($begin_time)) {
        $begin_time = strtotime($begin_time);

        if($begin_time > 0) {
            $begin_time = date('Y-m-d', $begin_time);
        } else {
            $begin_time = '';
        }
    } else {
        $begin_time = '';
    }

    if(!empty($end_time)) {
        $end_time = strtotime($end_time);

        if($end_time > 0 && $end_time >= $begin_time) {
            $end_time = date('Y-m-d', $end_time);
        } else {
            $end_time = '';
        }
    } else {
        $end_time = '';
    }

    $keyword = getGET('keyword');

    $api_request = [
        'page_size' => $page_count,
        'page' => $page,
        'keyword' => $keyword,
        'begin_time' => $begin_time,
        'end_time' => $end_time
    ];

    $api_url = build_url('/business/api/coupon.php');

    $api_response = api_request($api_url, $api_request, 'get', true, $_SESSION['business_account']);
    $api_response = json_decode($api_response, true);

    if($api_response['error'] != 0) {
        show_system_message($api_response['message']);
    }

    $total = $api_response['total'];

    $total_page = ceil($total / $page_count);
    create_pager($page, $total_page, $total);

    if($api_response['coupons'] && !empty($api_response['coupons'])) {
        foreach($api_response['coupons'] as &$_coupon) {
            $_coupon['type_name'] = $coupon_types[$_coupon['type']];
            $_coupon['status_show'] = $coupon_status[$_coupon['status']];
        }
    }

    assign('coupons', $api_response['coupons']);
    assign('keyword', $keyword);
    assign('begin_time', $begin_time);
    assign('end_time', $end_time);
}

if('add' == $act)
{
    if( !check_purview('pur_coupon_add', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $member_levels = $db->all('level', ['id', 'name'], null, null, ['id']);

    assign('subTitle', '新增优惠券');
    assign('coupon_type', $coupon_types);
    assign('member_levels', $member_levels);
    assign('hook_list', $hook_list);
}

if('edit' == $act)
{
    if( !check_purview('pur_coupon_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
        exit;
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
        exit;
    }

    $api_url = build_url('/business/api/coupon.php');

    $api_response = api_request($api_url, ['id' => $id], 'get', true);
    $api_response = json_decode($api_response, true);
    $log->record_array($api_response);

    if(empty($api_response)) {
        show_system_message('参数错误');
    }

    $coupon = $api_response['coupon'];

    $category_list = [];
    $product_list = [];

    if(!empty($coupon['category_scope'])) {
        $category_list = $db->all('category', ['id', 'name'], ['id' => ['in', $coupon['category_scope']]]);
    }

    if(!empty($coupon['product_scope'])) {
        $_product_list = $db->all('product', ['id', 'name', 'img', 'price', 'product_sn'], ['id' => ['in', $coupon['product_scope']]]);

        if(!empty($_product_list)) {
            $coupon['product_scope'] = [];
            while($_product = array_shift($_product_list)) {
                $product_list[$_product['product_sn']] = $_product;
                array_push($coupon['product_scope'], $_product['product_sn']);
            }
        }
    }

    $member_levels = $db->all('level', ['id', 'name'], null, null, ['id']);

    assign('subTitle', '编辑优惠券-'.$coupon['name']);
    assign('category_list', $category_list);
    assign('product_list', $product_list);
    assign('coupon', $coupon);
    assign('coupon_type', $coupon_types);
    assign('member_levels', $member_levels);
    assign('hook_list', $hook_list);
}

//分类选择器
if('category_selector' == $act)
{
    $response = [
        'error' => -1,
        'message' => ''
    ];

    if( !check_purview('pur_coupon_edit', $_SESSION['business_purview']) && !check_purview('pur_coupon_add', $_SESSION['business_purview']) ) {
        throw new RestFulException('没有操作权限', 503);
    }

    $categories = $db->all('category', ['id', 'name', 'parent_id']);
    $category_tree = [];

    if($categories) {
        foreach($categories as $category) {
            $node = [
                'id' => intval($category['id']),
                'pId' => intval($category['parent_id']),
                'name' => $category['name'],
                'open' => true
            ];

            array_push($category_tree, $node);
        }
    }

    $response['error'] = 0;
    $response['categories'] = $categories;
    $response['category_tree'] = $category_tree;

    echo json_encode($response);
    exit;
}

//产品选择器
if('product_selector' == $act)
{
    $response = [
        'error' => -1,
        'message' => ''
    ];

    if( !check_purview('pur_coupon_edit', $_SESSION['business_purview']) && !check_purview('pur_coupon_add', $_SESSION['business_purview']) ) {
        throw new RestFulException('没有操作权限', 503);
    }

    $shop_scope = getGET('shop_scope');
    $category_scope = getGET('category_scope');

    $page = intval(getGET('page'));
    $page = max(1, $page);
    $page_size = intval(getGET('page_size'));
    $page_size = max(6, $page_size);

    $offset = ($page - 1) * $page_size;

    $conditions = [
        'price' => ['gt', 0],
        'status' => 4
    ];

    if(!empty($category_scope)) {
        $category_scope = explode(',', $category_scope);

        if(count($category_scope)) {
            foreach($category_scope as &$category_id) {
                $category_id = intval($category_id);
            }
        }

        $product_sn_list = $db->all('product_category_mapper', ['distinct(`product_sn`)'], ['category_id' => ['in', $category_scope]]);

        if($product_sn_list) {
            $product_sn_scope = [];
            while($product_map = array_shift($product_sn_list)) {
                array_push($product_sn_scope, $product_map['product_sn']);
            }

            $conditions['product_sn'] = ['in', $product_sn_scope];
        }
    }

    $total = $db->getColumn('product', 'count(*)', $conditions);
    $product_list = $db->all('product', ['id', 'product_sn', 'name', 'price', 'img'], $conditions,$offset.','.$page_size);

    $response['error'] = 0;
    $response['product_list'] = $product_list;
    assign('product_list', $product_list);
    $total_page = ceil($total/$page_size);
    create_pager($page, $total_page, $total);
    assign('total_page', $total_page);
    $response['selection'] = $smarty->fetch('library/product_selection.phtml');

    echo json_encode($response);
    exit;
}

$template .= $act.'.phtml';
$smarty->display($template);