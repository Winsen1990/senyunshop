<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午3:34
 */
include 'library/init.inc.php';

$operation = 'sort|sort_shop';
$opera = getPOST('opera');
$template = 'search-result.phtml';

if('sort_shop' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $filter = getPOST('filter');
    $response['filter'] = $filter;
    $mode = getPOST('mode');

    $get_shop_list = 'select `shop_name`,`comment`,`shop_logo`,`business_account` from '.$db->table('business').' where '.
        ' `status`=2 ';

    //分组使用筛选条件
    //关键词
    if(isset($filter['keyword']) && $filter['keyword'] != '')
    {
        $keyword = $db->escape($filter['keyword']);
        $get_shop_list .= ' and `shop_name` like \'%'.$keyword.'%\'';
    }

    switch($mode)
    {
        case 'comment':
            $get_shop_list .= ' order by `comment` DESC';
            break;
        case 'near':
            $longitude = 0;
            $latitude = 0;
            if(isset($filter['position']))
            {
                $longitude = $filter['position']['longitude'];
                $latitude = $filter['position']['latitude'];
            }
            $get_shop_list .= ' order by ((`longitude`-'.$longitude.')*(`longitude`+'.$longitude.')+(`latitude`-'.$latitude.')*(`latitude`+'.$latitude.'))';
            break;
    }

    $shop_list = $db->fetchAll($get_shop_list);
    if($shop_list)
    {
        foreach ($shop_list as $key => $s)
        {
            $get_product_list = 'select `shop_price`,`given_integral`,`id`,`img`,`name`,`price` from ' . $db->table('product') .
                ' where `business_account`=\'' . $s['business_account'] . '\' and `status`=4 order by `star` DESC limit 3';
            $shop_list[$key]['product_list'] = $db->fetchAll($get_product_list);
        }
    }

    assign('shop_list', $shop_list);
    $response['content'] = $smarty->fetch('search-shop-item.phtml');
    $response['error'] = 0;

    echo json_encode($response);
    exit;
}

if('sort' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $filter = getPOST('filter');
    $mode = getPOST('mode');


    $now = time();
    $get_product_list = 'select p.`shop_price`,p.`given_integral`,p.`id`,p.`name`,if(p.`promote_end`>'.$now.',p.`promote_price`,p.`price`) as `price`,p.`img`,p.`product_sn`,(select `account` from '.$db->table('collection').
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
                $get_product_list .= ' order by `price` ASC';
            } else {
                $get_product_list .= ' order by `price` DESC';
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

$mode = getGET('mode');
$mode_list = 'shop|product';
$mode = check_action($mode_list, $mode);
if($mode == '')
{
    $mode = 'product';
}

if($mode == 'product')
{
    $now = time();
    $get_product_list = 'select p.`shop_price`,p.`given_integral`,p.`id`,p.`name`,if(p.`promote_end`>'.$now.',p.`promote_price`,p.`price`) as `price`,p.`img`,p.`product_sn`,(select `account` from '.$db->table('collection').
                        ' where `account`=\''.$_SESSION['account'].'\' and `product_sn`=p.`product_sn`) as collection from ' . $db->table('product') .
                        ' as p where p.`status`=4 and p.`name` like \'%' . $keyword . '%\'';

    $product_list = $db->fetchAll($get_product_list);

    assign('product_list', $product_list);
    assign('keyword', $keyword);

    $filter = array();

    $filter['keyword'] = $keyword;
//获取其他筛选条件
    $where = '`name` like \'%' . $keyword . '%\'';
//根据产品的分类获取筛选价格区间、品牌
    $attributes = array();
    $get_brand_ids = 'select DISTINCT `brand_id` from ' . $db->table('product') . ' where ' . $where;
    $brand_ids = $db->fetchAll($get_brand_ids);
    $brand_id_str = '';
    if ($brand_ids) {
        foreach ($brand_ids as $bid) {
            $brand_id_str .= $bid['brand_id'] . ',';
        }
    }
    $brand_id_str = substr($brand_id_str, 0, strlen($brand_id_str) - 1);
    $get_brand_list = 'select `id`,`name` from ' . $db->table('brand') . ' where `id` in (' . $brand_id_str . ')';
    $brand_list = $db->fetchAll($get_brand_list);
    $attributes[] = array(
        'key' => 'brand',
        'name' => '品牌',
        'values' => $brand_list
    );
    /*
    $get_price_limit = 'select max(`price`) as `max`,min(`price`) as `min` from '.$db->table('product').' where '.$where;
    $price_limit = $db->fetchRow($get_price_limit);

    $divide = 1;
    if($price_limit['max'] != $price_limit['min'])
    {

    }
    */

    assign('attributes', $attributes);

    assign('filter', json_encode($filter));
} else {
    $filter = array();

    $filter['keyword'] = $keyword;

    $get_shop_list = 'select `shop_name`,`comment`,`shop_logo`,`business_account` from '.$db->table('business').' where '.
                     ' `status`=2 and `shop_name` like \'%'.$keyword.'%\'';

    $shop_list = $db->fetchAll($get_shop_list);
    if($shop_list)
    {
        foreach ($shop_list as $key => $s)
        {
            $get_product_list = 'select `id`,`img`,`name`,`price`,`given_integral`,`shop_price` from ' . $db->table('product') .
                ' where `business_account`=\'' . $s['business_account'] . '\' and `status`=4 order by `star` DESC limit 3';
            $shop_list[$key]['product_list'] = $db->fetchAll($get_product_list);
        }
    }
    assign('shop_list', $shop_list);

    assign('filter', json_encode($filter));

    $template = 'search-shop.phtml';
}
$smarty->display($template);
