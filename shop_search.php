<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午3:34
 */
include 'library/init.inc.php';

$operation = 'sort';
$opera = getPOST('opera');

if('sort' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $filter = getPOST('filter');
    $mode = getPOST('mode');

    $now = time();
    $get_product_list = 'select p.`given_integral`,p.`shop_price`,p.`id`,p.`name`,if(p.`promote_end`>'.$now.',p.`promote_price`,p.`price`) as `price`,p.`img`,p.`product_sn`,(select `account` from '.$db->table('collection').
                        ' where `account`=\''.$_SESSION['account'].'\' and `product_sn`=p.`product_sn`) as collection from ' . $db->table('product') .
                        ' as p where p.`status`=4 ';

    $response['filter'] = $filter;

    //分组使用筛选条件
    //关键词
    if(isset($filter['keyword']) && $filter['keyword'] != '')
    {
        $keyword = $db->escape($filter['keyword']);
        $get_product_list .= ' and p.`name` like \'%'.$keyword.'%\'';
    }

    $promote_condition = '';
    //价格区间
    if(isset($filter['price_l']))
    {
        $price_l = $filter['price_l'];
        $price_l = floatval($price_l);
        $get_product_list .= ' and `price`>='.$price_l;
        $promote_condition .= ' and `promote_price`>='.$price_l;
    }

    if(isset($filter['price_h']))
    {
        $price_h = $filter['price_h'];
        $price_h = floatval($price_h);
        $get_product_list .= ' and `price`<='.$price_h;
        $promote_condition .= ' and `promote_price`<='.$price_h;
    }

    if($promote_condition != '')
    {
        $get_product_list .= ' or (`promote_end`>'.$now.$promote_condition.')';
    }

    //免运费
    if(isset($filter['free_delivery']))
    {
        $get_product_list .= ' and p.`free_delivery`=1';
    }
    //积分换购
    if(isset($filter['integral_exchange']))
    {
        $get_product_list .= ' and p.`integral`>0';
    }

    //店铺
    $filter['sn'] = $db->escape($filter['sn']);
    $get_product_list .= ' and p.`business_account`=\''.$filter['sn'].'\'';

    switch($mode)
    {
        case 'sale':
            $get_product_list .= ' order by p.`sale_count` DESC';
            break;
        case 'star':
            $get_product_list .= ' order by p.`star` DESC';
            break;
        case 'price':
            $orderby = getPOST('orderby');
            $orderby_list = 'up|down';
            $orderby = check_action($orderby_list, $orderby);
            if($orderby == '')
            {
                $orderby = 'up';
            }

            if($orderby == 'up')
            {
                $get_product_list .= ' order by p.`price` ASC';
            } else {
                $get_product_list .= ' order by p.`price` DESC';
            }
            break;

        case 'new':
            $get_product_list .= ' order by p.`add_time` DESC';
            break;

        default:
            $get_product_list .= ' order by p.`order_view` ASC';
            break;
    }

    $product_list = $db->fetchAll($get_product_list);

    assign('product_list', $product_list);
    $response['content'] = $smarty->fetch('search-product-item.phtml');
    $response['error'] = 0;

    echo json_encode($response);
    exit;
}

$keyword = getGET('keyword');

$keyword = $db->escape($keyword);

$filter = array();

$filter['keyword'] = $keyword;

$sn = getGET('sn');
if($sn == '')
{
    redirect('index.php');
}

$sn = $db->escape($sn);
$filter['sn'] = $sn;

$get_product_list = 'select p.`given_integral`,p.`shop_price`,p.`id`,p.`name`,p.`price`,p.`img`,p.`product_sn`,(select `account` from '.$db->table('collection').
                    ' where `account`=\''.$_SESSION['account'].'\' and `product_sn`=p.`product_sn`) as collection from ' . $db->table('product') .
                    ' as p where p.`status`=4 and p.`business_account`=\''.$sn.'\' order by p.`order_view` ASC';
$product_list = $db->fetchAll($get_product_list);
assign('business_account', $sn);
assign('product_list', $product_list);
assign('keyword', $keyword);
assign('filter', json_encode($filter));

$smarty->display('shop-search.phtml');