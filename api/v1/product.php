<?php
/**
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/25
 * Time: 13:45
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$operation = 'collection';
$opera = check_action($operation, getPOST('opera'));

$action = 'view|show';
$act = check_action($action, getGET('act'));

$response = [
    'error' => -1,
    'message' => ''
];

//====================== 产品收藏 =================================
if('collection' == $opera) {
    $product_sn = trim(getPOST('product_sn'));


    if(empty($product_sn)) {
        $response['message'] = '参数错误';
    } else {
        $product = $db->find('product', ['id'], ['product_sn' => $product_sn]);

        if(empty($product)) {
            $response['message'] = '产品不存在';
        } else {
            $collection = $db->find('collection', ['product_sn'], ['product_sn' => $product_sn, 'account' => $current_user['account']]);

            if(empty($collection)) {
                $data = [
                    'product_sn' => $product_sn,
                    'account' => $current_user['account'],
                    'add_time' => time()
                ];

                if($db->create('collection', $data)) {
                    $response['error'] = 0;
                    $response['message'] = '收藏成功';
                } else {
                    $response['message'] = '收藏失败';
                }
            } else {
                if($db->destroy('collection', ['product_sn' => $product_sn, 'account' => $current_user['account']])) {
                    $response['error'] = 0;
                    $response['message'] = '取消收藏成功';
                } else {
                    $response['message'] = '取消收藏失败';
                }
            }
        }
    }
}

//====================== 产品列表 =================================
if('view' == $act) {
    $page = intval(getGET('page'));
    $page = max(1, $page);

    $page_size = intval(getGET('page_size'));
    $page_size = max(5, $page_size);

    $keyword = getGET('keyword');

    $order = getGET('order');
    $order_columns = 'star|price|add_time|sale_count';
    $order = check_action($order_columns, $order, 'star');

    $sort = getGET('sort');
    $sort_by = 'ASC|DESC';
    $sort = check_action($sort_by, $sort, 'DESC');

    $category_id = intval(getGET('category_id'));

    $where = ' where p.`status`=4';
    if(!empty($keyword)) {
        $keyword = $db->escape($keyword);
        $where .= ' and p.`name` like \'%'.$keyword.'%\'';
    }

    if($category_id > 0) {
        $categories = [$category_id];

        //获取子分类
        $sub_categories = $db->all('category', ['id'], ['parent_id' => $category_id]);
        if($sub_categories) {
            while($sub_category = array_shift($sub_categories)) {
                array_push($categories, $sub_category['id']);
            }
        }

        $where .= ' and exists (select m.`product_sn` from '.$db->table('product_category_mapper').' as m '.
                  'where m.`product_sn`=p.`product_sn` and m.`category_id` in ('.implode(',', $categories).'))';
    }

    $get_total = 'select count(*) from '.$db->table('product').' as p ';
    $get_products = 'select p.`id`,p.`name`,p.`img`,p.`price`,p.`shop_price` as market_price from '.$db->table('product').' as p';

    $total = $db->fetchOne($get_total.$where);

    $total_page = ceil($total/$page_size);

    $response['total_page'] = $total_page;
    $response['products'] = [];
    $response['page'] = $page;

    if($page <= $total_page) {
        $limit = ' limit '.(($page - 1) * $page_size) . ',' . $page_size;

        $order_by = ' order by ';
        switch ($order) {
            case 'star':
                $order_by .= 'p.`star` DESC,p.`order_view` ASC ';
                break;

            case 'price':
                $order_by .= 'p.`price` ' . $sort . ',p.`order_view` ASC ';
                break;

            case 'add_time':
                $order_by .= 'p.`add_time` DESC,p.`order_view` ASC ';
                break;

            case 'sale_count':
                $order_by .= 'p.`sale_count` DESC,p.`order_view` ASC ';
                break;
        }

        $products = $db->fetchAll($get_products.$where.$order_by.$limit);

        if($products) {
            foreach($products as &$_product) {
                $_product['id'] = intval($_product['id']);
                $_product['price'] = floatval($_product['price']);
                $_product['market_price'] = floatval($_product['market_price']);
            }

            $response['products'] = $products;
            $response['error'] = 0;
        } else {
            $response['message'] = '没有等多产品';
        }
    } else {
        $response['message'] = '没有更多产品';
    }
}

//====================== 产品详情 =================================
if('show' == $act) {
    $response['product'] = null;

    $id = getGET('id');
    $id = intval($id);

    if ($id <= 0) {
        throw new RestFulException('参数错误', 400);
    }

    $columns = [
        'id',
        'product_sn',
        'price',
        'name',
        'shop_price',
        'img',
        'detail',
        'star',
        'sale_count',
        'order_view',
        'business_account',
        'free_delivery',
        'desc'
    ];

    $product = $db->find('product', $columns, ['id' => $id, 'status' => 4]);

    if (empty($product)) {
        throw new RestFulException('产品不存在或已下架', 404);
    }

    $response['product'] = [
        'id' => $product['id'],
        'product_sn' => $product['product_sn'],
        'name' => $product['name'],
        'price' => floatval($product['price']),
        'market_price' => floatval($product['shop_price']),
        'sale_count' => intval($product['sale_count']),
        'img' => $product['img'],
        'gallery' => [$product['img']],
        'desc' => $product['desc'],
        'detail' => $product['detail'],
        'star' => intval($product['star']),
        'collection' => false,
        'comments' => [
            'count' => 0,
            'good' => 0,
            'normal' => 0,
            'bad' => 0,
            'list' => []
        ],
        'inventory' => 0,
        'shipping_free' => $product['free_delivery'] ? true : false,
        'specification' => [], //规格参数
        'after_sale' => '' //售后
    ];

    $product_sn = $product['product_sn'];
    //获取相册
    $gallery = $db->all('gallery', ['big_img'], ['product_sn' => $product_sn]);
    if ($gallery) {
        while ($image = array_shift($gallery)) {
            array_push($response['product']['gallery'], $image['big_img']);
        }
    }

    //获取库存
    $inventory = $db->find('inventory', ['inventory', 'inventory_await', 'inventory_logic'], ['product_sn' => $product_sn]);
    $response['product']['inventory'] = $inventory['inventory_logic'];

    //获取收藏
    if (!empty($current_user)) {
        $has_collection = $db->getColumn('collection', 'product_sn', ['product_sn' => $product_sn, 'account' => $current_user['account']]);
        $response['product']['collection'] = !empty($has_collection);
    }

    //获取评论
    $get_comments = 'select c.`id`,c.`comment`,c.`star`,c.`add_time`,m.`headimg`,m.`nickname`,c.`img`,c.`account` from ' . $db->table('comment') . ' as c' .
        ' join ' . $db->table('member') . ' as m using(`account`) where c.`parent_id`=0 and `product_sn`=\'' . $product_sn . '\' order by c.`add_time` DESC limit 10';
    $comments = $db->fetchAll($get_comments);

    //获取评论分类统计
    /*
    $get_comment_summary = 'select `star`,count(*) as c from '.$db->table('comment').' where `product_sn`=\''.$product_sn.'\' group by `star`';
    $comment_summary = $db->fetchAll($get_comment_summary);

    if ($comments) {
        while ($comment = array_shift($comments)) {
            array_push($response['product']['comments']['list'], [
                'id' => $comment['id'],
                'content' => $comment['comment'],
                'star' => intval($comment['star']),
                'img' => $comment['img'],
                'account' => $comment['account'],
                'add_time' => date('Y-m-d', $comment['add_time']),
                'nickname' => $comment['nickname'],
                'avatar' => $comment['headimg']
            ]);
        }
    }

    if($comment_summary) {
        foreach ($comment_summary as $summary) {
            $response['product']['comments']['count'] += $summary['c'];
            if ($summary['star'] >= 4) {
                $response['product']['comments']['good'] += $summary['c'];
            } else if ($summary['star'] >= 2) {
                $response['product']['comments']['normal'] += $summary['c'];
            } else if ($summary['star'] >= 0) {
                $response['product']['comments']['bad'] += $summary['c'];
            }
        }
    }
    */

    //商户信息
    $shop = $db->find('business', ['shop_name', 'shop_logo', 'id', 'mobile'], ['business_account' => $product['business_account']]);
    if($shop) {
        $shop['id'] = intval($shop['id']);
    }
    $response['shop'] = [
        'name' => $shop['shop_name'],
        'logo' => $shop['shop_logo'],
        'service' => $shop['mobile']
    ];
    $response['member'] = null;
    $response['cart_count'] = 0;

    //用户信息
    if(!empty($current_user)) {
        $member_level = $db->find('level', ['name', 'discount'], ['id' => $current_user['level_id']]);
        if($member_level) {
            $response['member'] = [
                'level_name' => $member_level['name'],
                'level_price' => $member_level['discount'] * $product['price']/100
            ];
        }

        //购物车数量
        $cart_count = $db->getColumn('cart', 'sum(`number`)', ['account' => $current_user['account']]);
        $response['cart_count'] = $cart_count;
    }


    $response['error'] = 0;
}

echo json_encode($response);