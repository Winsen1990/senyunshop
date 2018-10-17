<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午8:21
 */
include 'library/init.inc.php';

global $db, $log, $config, $smarty;

$id = intval(getGET('id'));

$template = 'category.phtml';
$product_list = array();

$flag = false;

$operation = 'sort';
$opera = check_action($operation, getPOST('opera'));

//产品排序
if('sort' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $filter = getPOST('filter');
    $mode = getPOST('mode');

    $now = time();
    $get_product_list = 'select `integral`,`shop_price`,`given_integral`,`id`,`name`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`img` from '.$db->table('product').' where  `status`=4 ';

    $response['filter'] = $filter;

    //分组使用筛选条件
    //关键词
    if(isset($filter['id']) && $filter['id'] > 0)
    {
        $id = intval($filter['id']);
        $get_category_path = 'select `path` from '.$db->table('category').' where `id`='.$id;
        $path = $db->fetchOne($get_category_path);

        $get_category_ids = 'select `id` from '.$db->table('category').' where `path` like \''.$path.'%\' and `id` not in ('.$path.'0)';
        $category_ids = $db->fetchAll($get_category_ids);
        $category_ids_tmp = array();
        $category_ids_str = '';
        if($category_ids)
        {
            foreach ($category_ids as $key => $val)
            {
                $category_ids_tmp[] = $val['id'];
            }
            $category_ids_str = implode(',', $category_ids_tmp);
        }

        if($category_ids_str == '')
        {
            $category_ids_str = $id;
        } else {
            $category_ids_str .= ','.$id;
        }
        $get_product_list .= ' and `category_id` in ('.$category_ids_str.') and `status`=4';
    }

    switch($mode)
    {
        case 'sale':
            $get_product_list .= ' order by `sale_count` DESC,`order_view` ASC';
            break;
        case 'star':
            $get_product_list .= ' order by `star` DESC,`order_view` ASC';
            break;
        case 'price':
            $get_product_list .= ' order by `price` DESC,`order_view` ASC';
            break;
        case 'new':
            $get_product_list .= ' order by `add_time` DESC,`order_view` ASC';
            break;
        default:
            $get_product_list .= ' order by `order_view` ASC';
            break;
    }

//    $response['sql'] = $get_product_list;
    $product_list = $db->fetchAll($get_product_list);

    assign('product_list', $product_list);
    $response['content'] = $smarty->fetch('product-list-item.phtml');
    $response['error'] = 0;

    echo json_encode($response);
    exit;
}

if($id < 0)
{
    redirect('index.php');
}

$state = getGET('state');
$state_list = 'sale_amount|price|discount|star|add_time';

$state = check_action($state_list, $state);

if('' == $state)
{
    $state = 'new';
}

$filter = array();

$filter['id'] = $id;

$get_category = 'select `path`,`name` from '.$db->table('category').' where `id`='.$id;
$category = $db->fetchRow($get_category);
$path = $category['path'];

$get_category_ids = 'select `id` from '.$db->table('category').' where `path` like \''.$path.'%\' and `id` not in ('.$path.'0)';
$category_ids = $db->fetchAll($get_category_ids);
$category_ids_tmp = array();
$category_ids_str = '';
if($category_ids)
{
    foreach ($category_ids as $key => $val)
    {
        $category_ids_tmp[] = $val['id'];
    }
    $category_ids_str = implode(',', $category_ids_tmp);
}

if($category_ids_str == '')
{
    $category_ids_str = $id;
} else {
    $category_ids_str .= ','.$id;
}

$now = time();
$get_product_list = 'select `integral`,`shop_price`,`given_integral`,`product_sn`,`name`,`id`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`img` from '.$db->table('product').' where `status`=4 '.
                    'and `category_id` in ('.$category_ids_str.')';

switch($state)
{
    case 'price':
        $get_product_list .= ' order by `price` DESC,`order_view` ASC';
        break;

    case 'star':
        $get_product_list .= ' order by `star` DESC,`order_view` ASC';
        break;

    default:
        $get_product_list .= ' order by `order_view` ASC';
        break;
}

$product_list = $db->fetchAll($get_product_list);

$get_category_list = 'select `id`,`name` from ' . $db->table('category') . ' where `business_account`=\'\' and `parent_id`=0';
$category_list = $db->fetchAll($get_category_list);
foreach ($category_list as $key => $c) {
    $get_children = 'select `id`,`name` from ' . $db->table('category') . ' where `business_account`=\'\' and `parent_id`=' . $c['id'];

    $category_list[$key]['children'] = $db->fetchAll($get_children);
}

assign('category', $category);
assign('category_list', $category_list);
assign('state', $state);
assign('product_list', $product_list);
assign('id', $id);

assign('filter', json_encode($filter));
$smarty->display('product-list.phtml');
