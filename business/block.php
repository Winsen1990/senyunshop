<?php
/**
 * 专区管理
 * @author Winsen
 * @email airplace1@gmail.com
 * @date 2015-8-6
 * @version 1.0.0
 */
include 'library/init.inc.php';
global $db, $log, $smarty;

business_base_init();

$template = 'block/';
assign('subTitle', '专区管理');

$action = 'edit|add|view|delete|assoc';
$operation = 'edit|add|assoc';
$act = check_action($action, getGET('act'));

$act = ( $act == '' ) ? 'view' : $act;

$opera = check_action($operation, getPOST('opera'));
//----------------------------- 操作 ------------------------------------------
if('assoc' == $opera)
{
    $response = [
        'error' => 1,
        'msg' => '',
        'errmsg' => []
    ];

    if(!check_purview('pur_block_edit', $_SESSION['business_purview']))
    {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $id = intval(getPOST('eid'));
    $products = getPOST('products');
    $sorts = getPOST('sorts');

    if($id <= 0) {
        $response['msg'] = '参数错误';
    } else {
        $block = $db->find('blocks', ['id'], ['id' => $id]);

        if (empty($block)) {
            $response['msg'] = '专区不存在';
        }
    }

    if(count($response['errmsg']) == 0 && $response['msg'] == '')
    {
        $db->destroy('block_product_mapper', ['block_id' => $id]);

        if(count($products)) {
            $blocks = [];
            foreach($products as $i => $product_sn) {
                array_push($blocks, [
                    'block_id' => $id,
                    'product_sn' => $product_sn,
                    'sort' => isset($sorts[$i]) ? $sorts[$i] : 50
                ]);
            }

            if($db->autoInsert('block_product_mapper', $blocks))
            {
                $response['msg'] = '设置专区关联产品成功';
                $response['error'] = 0;
            } else {
                $response['msg'] = '系统繁忙，请稍后再试';
            }
        } else {
            $response['msg'] = '设置专区关联产品成功';
            $response['error'] = 0;
        }
    }

    echo json_encode($response);
    exit;
}

if('edit' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_block_edit', $_SESSION['business_purview']))
    {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $id = intval(getPOST('eid'));
    $block = [
        'name' => trim(getPOST('name')),
        'cover' => trim(getPOST('cover')),
        'url' => trim(getPOST('url')),
        'sort' => intval(getPOST('sort')),
        'status' => intval(getPOST('status')) ? 1 : 0
    ];

    if($id <= 0) {
        $response['msg'] = '参数错误';
    }

    if(empty($block['name'])) {
        $response['errmsg']['name'] = '-请输入活动名称';
    }

    if(empty($block['cover'])) {
        $response['errmsg']['cover'] = '-请选择封面图片';
    }

    $block['sort'] = max(1, $block['sort']);

    if(count($response['errmsg']) == 0 && $response['msg'] == '')
    {

        if($db->autoUpdate('blocks', $block, '`id`='.$id) !== false)
        {
            $response['msg'] = '编辑专区成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = array('error'=>1, 'msg'=>'', 'errmsg'=>array());

    if(!check_purview('pur_block_add', $_SESSION['business_purview'])) {
        $response['msg'] = '没有操作权限';
        echo json_encode($response);
        exit;
    }

    $block = [
        'name' => trim(getPOST('name')),
        'cover' => trim(getPOST('cover')),
        'url' => trim(getPOST('url')),
        'sort' => intval(getPOST('sort')),
        'status' => intval(getPOST('status')) ? 1 : 0
    ];

    if(empty($block['name'])) {
        $response['errmsg']['name'] = '-请输入活动名称';
    }

    if(empty($block['cover'])) {
        $response['errmsg']['cover'] = '-请选择封面图片';
    }

    $block['sort'] = max(1, $block['sort']);

    if(count($response['errmsg']) == 0)
    {
        if($db->create('blocks', $block))
        {
            $response['msg'] = '新增专区成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

//--------------------------------- 页面 ------------------------------------------
if('view' == $act)
{
    if(!check_purview('pur_block_view', $_SESSION['business_purview']))
    {
        show_system_message('权限不足', array());
    }
    
    $blocks = $db->all('blocks', ['id', 'name', 'cover', 'sort'], null, null, ['sort']);

    assign('blocks', $blocks);
}

if('edit' == $act)
{
    if( !check_purview('pur_block_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $block = $db->find('blocks', ['id', 'name', 'cover', 'url', 'status', 'sort'], ['id' => $id]);

    if(empty($block)) {
        show_system_message('专区不存在');
    }

    if(empty($block['cover'])) {
        $block['cover'] = '../upload/image/no-image.png';
    }

    assign('block', $block);
}

if('delete' == $act)
{
    if( !check_purview('pur_block_del', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
    }

    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    if($db->destroy('blocks', ['id' => $id]))
    {
        $db->destroy('block_product_mapper', ['block_id' => $id]);
        show_system_message('删除专区成功');
    } else {
        show_system_message('系统繁忙，请稍后再试');
    }
}

if('assoc' == $act)
{
    if( !check_purview('pur_block_edit', $_SESSION['business_purview']) ) {
        show_system_message('权限不足');
    }

    $id = intval(getGET('id'));

    if($id <= 0) {
        show_system_message('参数错误');
    }

    $block = $db->find('blocks', ['id', 'name', 'cover', 'url', 'status', 'sort'], ['id' => $id]);

    if(empty($block)) {
        show_system_message('专区不存在');
    }

    if(empty($block['cover'])) {
        $block['cover'] = '../upload/image/no-image.png';
    }

    $get_assoc_products = 'select p.`product_sn`,p.`name`,p.`price`,p.`img`,m.`sort` from '.$db->table('product').' as p inner join '.
        $db->table('block_product_mapper').' as m on m.`product_sn`=p.`product_sn` and m.`block_id`='.$id;

    $assoc_products = $db->fetchAll($get_assoc_products);

    assign('block', $block);
    assign('assoc_products', $assoc_products);

    $product_scope = [];
    $product_selected = [];

    if($assoc_products) {
        foreach ($assoc_products as $_product) {
            array_push($product_scope, $_product['product_sn']);
            $product_selected[$_product['product_sn']] = [
                'id' => $_product['product_sn'],
                'img' => $_product['img'],
                'name' => $_product['name'],
                'price' => $_product['price']
            ];
        }
    }

    assign('product_scope', $product_scope);
    assign('product_selected', $product_selected);
}

$template .= $act.'.phtml';
$smarty->display($template);