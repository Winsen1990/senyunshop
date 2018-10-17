<?php
/**
 *
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-21
 * @version 
 */
//===========================权限与菜单 start================================
global $purview;
$purview = array(
    'pur_business' => array(
        'pur_business_base',
        'pur_business_auth',
    ),

    'pur_category' => array(
        'pur_category_view',
        'pur_category_add',
        'pur_category_edit',
        'pur_category_del',
    ),

    'pur_product' => array(
        'pur_product_view',
        'pur_product_add',
        'pur_product_edit',
        'pur_product_del',
        'pur_virtual_product_view',
        'pur_virtual_product_add',
        'pur_virtual_product_edit',
        'pur_virtual_product_del',
    ),

    'pur_order' => array(
        'pur_order_view',
        'pur_order_edit',
        'pur_order_del',
    ),

//    'pur_virtual_product' => array(
//
//    ),

    'pur_finance' => array(
        'pur_finance_view',
//        'pur_finance_trade',
        'pur_finance_withdraw',
    ),

    'pur_express' => array(
        'pur_express_view',
        'pur_express_add',
        'pur_express_edit',
        'pur_express_del',
    ),


    'pur_virtual_order' => array(
        'pur_virtual_order_view',
        'pur_virtual_order_edit',
    ),

    'pur_eval' => array(
        'pur_eval_view',
        'pur_eval_response',
        'pur_eval_del',
    ),

    'pur_ad' => array(
        'pur_ad_view',
        'pur_ad_add',
        'pur_ad_edit',
        'pur_ad_del'
    ),

    'pur_role' => array(
        'pur_role_view',
        'pur_role_add',
        'pur_role_edit',
        'pur_role_del',
    ),

    'pur_admin' => array(
        'pur_admin_view',
        'pur_admin_add',
        'pur_admin_edit',
        'pur_admin_del',
    ),


);

global $L_purview;
$L_purview = array(

    'pur_business' => '商户信息管理',
    'pur_business_base' => '基本信息',
    'pur_business_auth' => '认证信息',

    'pur_category' => '产品分类管理',
    'pur_category_view' => '分类列表',
    'pur_category_add' => '增加分类',
    'pur_category_edit' => '编辑分类',
    'pur_category_del' => '删除分类',

    'pur_product' => '产品管理',
    'pur_product_view' => '产品列表',
    'pur_product_add' => '增加产品',
    'pur_product_edit' => '编辑产品',
    'pur_product_del' => '删除产品',
    'pur_virtual_product_view' => '虚拟产品列表',
    'pur_virtual_product_add' => '增加虚拟产品',
    'pur_virtual_product_edit' => '编辑虚拟产品',
    'pur_virtual_product_del' => '删除虚拟产品',

    'pur_express' => '物流方式设置',
    'pur_express_view' => '物流列表',
    'pur_express_add' => '增加物流',
    'pur_express_edit' => '编辑物流',
    'pur_express_del' => '删除物流',


    'pur_finance' => '财务管理',
    'pur_finance_view' => '账户明细',
//    'pur_finance_trade' => '交易记录',
    'pur_finance_withdraw' => '提现',

    'pur_order' => '订单管理',
    'pur_order_view' => '查看订单',
    'pur_order_edit' => '编辑订单',
    'pur_order_del' => '删除订单',

    'pur_eval' => '评价管理',
    'pur_eval_view' => '评价列表',
    'pur_eval_response' => '回复',
    'pur_eval_del' => '删除评论',

    'pur_admin' => '管理员',
    'pur_admin_view' => '管理员列表',
    'pur_admin_add' => '增加管理员',
    'pur_admin_edit' => '编辑管理员',
    'pur_admin_del' => '删除管理员',

    'pur_role' => '管理员角色',
    'pur_role_view' => '角色列表',
    'pur_role_add' => '增加角色',
    'pur_role_edit' => '编辑角色',
    'pur_role_del' => '删除角色',

    'pur_virtual_product' => '虚拟产品',

    'pur_virtual_order' => '消费券',
    'pur_virtual_order_view' => '消费券列表',
    'pur_virtual_order_edit' => '使用消费券',

    'pur_ad' => '广告管理',
    'pur_ad_view' => '查看广告',
    'pur_ad_add' => '添加广告',
    'pur_ad_edit' => '编辑广告',
    'pur_ad_del' => '删除广告',
);

global $menus;
$menus = array(
    'pur_business' => array(
        'title' => '商户信息管理',
        'icon' => '&#xe607;',
        'url' => 'business.php',
        'children' => array(
            'pur_business_base' => array('url' => 'business.php', 'title' => '基本信息'),
            'pur_business_auth' => array('url' => 'business.php?act=auth', 'title' => '认证信息'),
        )
    ),
//    'pur_category' => array(
//        'title' => '产品分类管理',
//        'icon' => '&#xe60c;',
//        'url' => 'category.php',
//        'children' => array(
//            'pur_category_view' => array('url' => 'category.php', 'title' => '分类列表'),
//            'pur_category_add' => array('url' => 'category.php?act=add', 'title' => '增加分类'),
//        ),
//    ),

    'pur_product' => array(
        'title' => '产品管理',
        'icon' => '&#xe60d;',
        'url' => 'product.php',
        'children' => array(
            'pur_product_view' => array('url' => 'product.php', 'title' => '产品列表'),
            'pur_product_add' => array('url' => 'product.php?act=add', 'title' => '增加产品'),
            'pur_product_delete' => array('url' => 'product.php?act=cycle', 'title' => '回收站'),
//            'pur_virtual_product_view' => array('url' => 'virtual_product.php', 'title' => '虚拟产品列表'),
//            'pur_virtual_product_add' => array('url' => 'virtual_product.php?act=add', 'title' => '增加虚拟产品'),
//            'pur_virtual_product_delete' => array('url' => 'virtual_product.php?act=cycle', 'title' => '虚拟产品回收站'),
        ),
    ),

//    'pur_virtual_product' => array(
//        'title' => '虚拟产品管理',
//        'icon' => '&#xe608;',
//        'url' => 'virtual_product.php',
//        'children' => array(
//            'pur_virtual_product_view' => array('url' => 'virtual_product.php', 'title' => '虚拟产品列表'),
//            'pur_virtual_product_add' => array('url' => 'virtual_product.php?act=add', 'title' => '增加虚拟产品'),
//            'pur_virtual_product_delete' => array('url' => 'virtual_product.php?act=cycle', 'title' => '虚拟产品回收站'),
//        ),
//    ),

    'pur_order' => array(
        'title' => '订单管理',
        'icon' => '&#xe609;',
        'url' => 'order.php',
    ),


    'pur_finance' => array(
        'title' => '财务管理',
        'icon' => '&#xe60e;',
        'url' => 'finance.php',
        'children' => array(
            'pur_finance_view' => array('url' => 'finance.php', 'title' => '账户明细'),
//            'pur_finance_trade' => array('url' => 'finance.php?act=trade', 'title' => '交易记录'),
            'pur_finance_withdraw' => array('url' => 'finance.php?act=record', 'title' => '提现'),
        ),
    ),

    'pur_virtual_order' => array(
        'title' => '消费券管理',
        'icon' => '&#xe608;',
        'url' => 'virtual_order.php',
    ),

    'pur_express' => array(
        'title' => '物流方式管理',
        'icon' => '&#xe606;',
        'url' => 'express.php',
        'children' => array(
            'pur_express_view' => array('url' => 'express.php', 'title' => '物流方式列表'),
//            'pur_express_add' => array('url' => 'express.php?act=add', 'title' => '新增物流方式'),
        ),
    ),

    'pur_eval' => array(
        'title' => '评价管理',
        'icon' => '&#xe60b;',
        'url' => 'eval.php',
    ),

    'pur_ad' => array(
        'title' => '广告管理',
        'icon' => '&#xe600;',
        'url' => 'ad.php',
    ),

    'pur_role' => array(
        'title' => '角色管理',
        'icon' => '&#xe604;',
        'url' => 'role.php',
        'children' => array(
            'pur_role_view' => array('url' => 'role.php', 'title' => '角色列表'),
            'pur_role_add' => array('url' => 'role.php?act=add', 'title' => '增加角色'),
        ),
    ),

    'pur_admin' => array(
        'title' => '管理员',
        'icon' => '&#xe605;',
        'url' => 'admin.php',
        'children' => array(
            'pur_admin_view' => array('url' => 'admin.php', 'title' => '管理员列表'),
            'pur_admin_add' => array('url' => 'admin.php?act=add', 'title' => '增加管理员'),
        ),
    ),


);

//===========================权限与菜单 end==================================

/**
 * 生成商户后台菜单
 * @author 王仁欢
 * @version 1.0.0n
 */
function create_business_menu() {
    global $menus, $purview;
    $current_purview = $_SESSION['business_purview'];
    $current_purview = json_decode($current_purview);
    $menu = array();
    foreach($current_purview as $key => $value) {
        if( count($value) > 0 ) {
            if( isset($menus[$key]) ) {
                $k = str_replace('pur_', 'menu_', $key);
                $menu[$k] = $menus[$key];
            }
        }
    }
    assign('menus', $menu);
}

/**
 * 商户管理后台初始化
 * @author 王仁欢
 */
function business_base_init()
{
    if (!isset($_SESSION['business_account'])) {
        $links = array(
            array('link' => 'index.php', 'alt' => '登陆'),
        );
        show_system_message('请先登陆', $links);
        exit;
    }

    global $db;
    $get_business = 'select * from ' . $db->table('business');
    $get_business .= ' where business_account = \'' . $_SESSION['business_account'] . '\' and status = 2';
    $business = $db->fetchRow($get_business);

    if (empty($business)) {
        if (isset($_SESSION['business_account'])) {
            unset($_SESSION['business_shop_name']);
            unset($_SESSION['business_account']);
            unset($_SESSION['business_purview']);
        }
        $links = array(
            array('link' => 'index.php', 'alt' => '重新登陆'),
        );
        show_system_message('商户不存在或不在运营中', $links);
        exit;
    }

    $current_shop = $_SESSION['business_shop_name'];
    assign('current_shop', $current_shop);
    assign('pageTitle', '网店' . $current_shop . '管理后台');

    create_business_menu();

    $active_nav = get_active_nav();
    $active_nav = explode('.', $active_nav);
    $active_nav = $active_nav[0];
    assign('active_nav', $active_nav);
    if( $active_nav == 'virtual_product' ) {
        $active_nav = 'product';
    }
    assign('menu_mark', 'menu_' . $active_nav);

    //未读消息数量
    $get_unread_message_count = 'select count(*) from ' . $db->table('message');
    $get_unread_message_count .= ' where business_account = \'' . $_SESSION['business_account'] . '\' and status = 0';
    $unread_message_count = $db->fetchOne($get_unread_message_count);
    assign('unread_message_count', $unread_message_count);

    if ( $_SESSION['business_account'] == $_SESSION['business_admin'] ) {
        $primary_account = true;
    } else {
        $primary_account = false;
    }
    assign('primary_account', $primary_account);

}