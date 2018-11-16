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
            coupon_translate($_coupon);
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

    $api_response = api_request($api_url, ['id' => $id], 'get', true, $_SESSION['business_account']);
    $api_response = json_decode($api_response, true);
    $log->record_array($api_response);

    if(empty($api_response)) {
        show_system_message('参数错误');
    }

    $coupon = $api_response['coupon'];

    $shop_list = [];
    $category_list = [];
    $product_list = [];

    if(!empty($coupon['shop_scope'])) {
        $_shop_list = $db->all('business', ['id', 'shop_name', 'shop_logo'], ['id' => ['in', $coupon['shop_scope']]]);

        if(!empty($_shop_list)) {
            while($_shop = array_shift($_shop_list)) {
                $shop_list[$_shop['id']] = $_shop;
            }
        }
    }

    if(!empty($coupon['category_scope'])) {
        $category_list = $db->all('category', ['id', 'name'], ['id' => ['in', $coupon['category_scope']]]);
    }

    if(!empty($coupon['product_scope'])) {
        $_product_list = $db->all('product', ['id', 'name', 'img', 'price'], ['id' => ['in', $coupon['product_scope']]]);

        if(!empty($_product_list)) {
            while($_product = array_shift($_product_list)) {
                $product_list[$_product['id']] = $_product;
            }
        }
    }

    $member_levels = $db->all('level', ['id', 'name'], null, null, ['id']);

    assign('subTitle', '编辑优惠券-'.$coupon['name']);
    assign('shop_list', $shop_list);
    assign('category_list', $category_list);
    assign('product_list', $product_list);
    assign('coupon', $coupon);
    assign('coupon_type', $coupon_types);
    assign('member_levels', $member_levels);
}

//店铺选择器
if('shop_selector' == $act)
{
    $response = [
        'error' => -1,
        'message' => ''
    ];

    if( !check_purview('pur_coupon_edit', $_SESSION['business_purview']) && !check_purview('pur_coupon_add', $_SESSION['business_purview']) ) {
        throw new RestFulException('没有操作权限', 503);
    }

    $keyword = getGET('keyword');
    $page = intval(getGET('page'));
    $page = max(1, $page);
    $page_size = intval(getGET('page_size'));
    $page_size = max(10, $page_size);

    $offset = ($page - 1) * $page_size;

    $conditions = [];

    if(!empty($keyword)) {
        $conditions['shop_name'] = ['like', '%'.$keyword.'%'];
    }

    $total = $db->getColumn('business', 'count(*)', $conditions);
    $shop_list = $db->all('business', ['id', 'shop_name', 'shop_logo'], $conditions, $offset.','.$page_size);

    $response['error'] = 0;
    $response['shop_list'] = $shop_list;
    assign('shop_list', $shop_list);
    $total_page = ceil($total/$page_size);
    create_pager($page, $total, $total_page);
    assign('total_page', $total_page);
    $response['selection'] = $smarty->fetch('library/shop_selection.phtml');

    echo json_encode($response);
    exit;
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

    $conditions = [
        'business_account' => $_SESSION['business_account']
    ];

    $shop_list = $db->all('business', ['id', 'shop_name', 'business_account', 'category_id'], ['id' => $_SESSION['business_id']], null, 'id');

    $categories = $db->all('category', ['id', 'name', 'parent_id', 'business_account'], $conditions);
    $category_tree = [];
    foreach($shop_list as $shop) {
        array_push($category_tree, [
            'id' => -1 * $shop['id'],
            'pId' => 0,
            'name' => $shop['shop_name'],
            'open' => true
        ]);

        foreach($categories as $category) {
            if($category['business_account'] == $shop['business_account']) {
                $node = [
                    'id' => intval($category['id']),
                    'pId' => intval($category['parent_id']),
                    'name' => $category['name'],
                    'open' => true
                ];

                if($node['pId'] == 0 || $node['pId'] == $shop['category_id']) {
                    $node['pId'] = -1 * $shop['id'];
                }

                array_push($category_tree, $node);
            }
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
    $page_size = max(10, $page_size);

    $offset = ($page - 1) * $page_size;

    $conditions = [
        'price' => ['gt', 0],
        'status' => 4,
        'business_account' => $_SESSION['business_account']
    ];

    if(!empty($category_scope)) {
        $category_scope = explode(',', $category_scope);

        if(count($category_scope)) {
            foreach($category_scope as &$category_id) {
                $category_id = intval($category_id);
            }
        }

        $conditions['shop_category_id'] = ['in', $category_scope];
    }

    $total = $db->getColumn('product', 'count(*)', $conditions);
    $product_list = $db->all('product', ['id', 'name', 'price', 'img'], $conditions,$offset.','.$page_size);

    $response['error'] = 0;
    $response['product_list'] = $product_list;
    assign('product_list', $product_list);
    $total_page = ceil($total/$page_size);
    create_pager($page, $total, $total_page);
    assign('total_page', $total_page);
    $response['selection'] = $smarty->fetch('library/product_selection.phtml');

    echo json_encode($response);
    exit;
}

$template .= $act.'.phtml';
$smarty->display($template);