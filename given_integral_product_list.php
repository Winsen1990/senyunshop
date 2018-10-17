<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午8:21
 */
include 'library/init.inc.php';

global $db, $log, $config, $smarty;

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
    $get_product_list = 'select `shop_price`,`id`,`name`,`integral`,`img`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`given_integral` from '.$db->table('product').' where  `status`=4 and `given_integral`>0';

    $response['filter'] = $filter;

    //分组使用筛选条件
    //关键词
    if(isset($filter['id']) && $filter['id'] > 0)
    {
        $id = intval($filter['id']);

        $get_product_list .= ' and `integral`>0 and `status`=4';
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
            $get_product_list .= ' order by `order_view` ASC';
            break;
        default:
            break;
    }

    $response['sql'] = $get_product_list;
    $product_list = $db->fetchAll($get_product_list);

    assign('product_list', $product_list);
    $response['content'] = $smarty->fetch('product-list-item.phtml');
    $response['error'] = 0;

    echo json_encode($response);
    exit;
}

$state = getGET('state');
$state_list = 'sale_amount|price|discount|star|add_time';

$state = check_action($state_list, $state);

if('' == $state)
{
    $state = 'new';
}

$filter = array();

$now = time();
$get_product_list = 'select `shop_price`,`product_sn`,`name`,`id`,if(`promote_end`>'.$now.',`promote_price`,`price`) as `price`,`given_integral`,`img` from '.
    $db->table('product').' where `status`=4 and `given_integral`>0';

switch($state)
{
    case 'price': $get_product_list .= ' order by `price` ASC,`order_view` ASC'; break;
    case 'star': $get_product_list .= ' order by `star` DESC,`order_view` ASC'; break;
    default: $get_product_list .= ' order by `order_view` ASC'; break;
}

$product_list = $db->fetchAll($get_product_list);

assign('state', $state);
assign('product_list', $product_list);

assign('filter', json_encode($filter));
$smarty->display('given-integral-product-list.phtml');