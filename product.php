<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/7
 * Time: 上午11:22
 */
include 'library/init.inc.php';

global $db, $log, $config;

$operation = 'collection|distribution|delete_history';

$opera = check_action($operation, getPOST('opera'));

$user_info = null;

if(isset($_SESSION['account'])) {
    $user_info = $db->fetchRow('select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'');
}

//我的足迹
if('delete_history' == $opera)
{
    $product_sn = getPOST('product_sn');

    $response = array('error'=>1, 'msg'=>'');

    if(!check_cross_domain() && !empty($_SESSION['account']))
    {
        if($product_sn != '')
        {
            $product_sn = $db->escape($product_sn);

            $delete_history = 'delete from '.$db->table('history').' where `account`=\''.$_SESSION['account'].'\' and `product_sn`=\''.$product_sn.'\'';
            if($db->delete($delete_history))
            {
                $response['error'] = 0;
                $response['msg'] = '删除足迹成功';
            } else {
                $response['msg'] = '001:系统繁忙，请稍后再试';
            }
        } else {
            $response['msg'] = '000:参数错误';
        }
    } else {
        if(empty($_SESSION['account']))
        {
            $response['msg'] = '请先登录';
            $response['error'] = 2;
        } else {
            $response['msg'] = '404:参数错误';
        }
    }

    echo json_encode($response);
    exit;
}
//产品分销
if('distribution' == $opera)
{
    $product_sn = getPOST('product_sn');

    $response = array('error'=>1, 'msg'=>'');

    if(!check_cross_domain() && !empty($_SESSION['account']))
    {
        if($product_sn != '')
        {
            if($user_info['level_id'] <= 0) {
                $response['msg'] = '只有商业会员才能分销';
            } else {
                $product_sn = $db->escape($product_sn);
                //检查产品的收藏状态
                $get_distribution = 'select `product_sn` from ' . $db->table('distribution') .
                    ' where `account`=\'' . $_SESSION['account'] . '\' and `product_sn`=\'' . $product_sn . '\'';
                $distribution_flag = $db->fetchOne($get_distribution) ? true : false;

                if ($distribution_flag) {
                    if (cancel_distribution_product($_SESSION['account'], $product_sn)) {
                        $response['error'] = 0;
                        $response['status'] = !$distribution_flag;
                        $response['msg'] = '取消分销成功';
                    } else {
                        $response['msg'] = '001:系统繁忙，请稍后再试';
                    }
                } else {
                    if (distribution_product($_SESSION['account'], $product_sn)) {
                        $response['error'] = 0;
                        $response['status'] = !$distribution_flag;
                        $response['msg'] = '分销产品成功';
                    } else {
                        $response['msg'] = '001:系统繁忙，请稍后再试';
                    }
                }
            }
        } else {
            $response['msg'] = '000:参数错误';
        }
    } else {
        if(empty($_SESSION['account']))
        {
            $response['msg'] = '请先登录';
            $response['error'] = 2;
        } else {
            $response['msg'] = '404:参数错误';
        }
    }

    echo json_encode($response);
    exit;
}
//产品收藏
if('collection' == $opera)
{
    $product_sn = getPOST('product_sn');

    $response = array('error'=>1, 'msg'=>'');

    if(!check_cross_domain() && !empty($_SESSION['account']))
    {
        if($product_sn != '')
        {
            $product_sn = $db->escape($product_sn);
            //检查产品的收藏状态
            $get_collection = 'select `product_sn` from '.$db->table('collection').
                              ' where `account`=\''.$_SESSION['account'].'\' and `product_sn`=\''.$product_sn.'\'';
            $collection_flag = $db->fetchOne($get_collection) ? true : false;

            if($collection_flag)
            {
                if(cancel_collection_product($_SESSION['account'], $product_sn))
                {
                    $response['error'] = 0;
                    $response['status'] = !$collection_flag;
                    $response['product_sn'] = $product_sn;
                    $response['msg'] = '取消收藏成功';
                } else {
                    $response['msg'] = '001:系统繁忙，请稍后再试';
                }
            } else {
                if(collection_product($_SESSION['account'], $product_sn))
                {
                    $response['error'] = 0;
                    $response['status'] = !$collection_flag;
                    $response['product_sn'] = $product_sn;
                    $response['msg'] = '收藏产品成功';
                } else {
                    $response['msg'] = '001:系统繁忙，请稍后再试';
                }
            }
        } else {
            $response['msg'] = '000:参数错误';
        }
    } else {
        if(empty($_SESSION['account']))
        {
            $response['msg'] = '请先登录';
            $response['error'] = 2;
        } else {
            $response['msg'] = '404:参数错误';
        }
    }

    echo json_encode($response);
    exit;
}

$id = intval(getGET('id'));

if($id <= 0)
{
    redirect('index.php');
}

$get_product = 'select * from '.$db->table('product').' where  `status`=4 and `id`='.$id;

$product = $db->fetchRow($get_product);

if($product)
{
    $product_sn = $product['product_sn'];
    //如果产品正在促销时间，则将产品价格赋值为促销价格
    //促销的产品不参与砍价
    $now = time();
    if($product['promote_end'] > $now && $product['promote_begin'] <= $now)
    {
        $product['price'] = $product['promote_price'];
        $product['promote_left'] = $product['promote_end'] - $now;
        $left_time = $product['promote_left'];
        $product['hour'] = intval($left_time/3600);
        $left_time = $left_time%3600;
        $product['min'] = intval($left_time/60);
        $left_time = $left_time%60;
        $product['second'] = $left_time;
    } else {
        //获取产品砍价总额
        $get_product_discount = 'select sum(`reduce`) from '.$db->table('discount').
                                ' where `product_sn`=\''.$product_sn.'\' and `owner`=\''.$_SESSION['account'].'\'';

        $discount = $db->fetchOne($get_product_discount);
        $product['price'] -= $discount;
    }

    $product['gallery'] = array($product['img']);

    //读取产品相册
    $get_gallery = 'select `big_img` from '.$db->table('gallery').' where `product_sn`=\''.$product_sn.'\'';
    $gallery = $db->fetchAll($get_gallery);

    if($gallery)
    {
        $gallery_arr = array();
        foreach($gallery as $g)
        {
            $gallery_arr[] = $g['big_img'];
        }
        $product['gallery'] = array_merge($product['gallery'], $gallery_arr);
    }

    //读取产品属性表
    if($product['product_type_id'])
    {
        $attributes_map = array();
        $attributes_mode = array();
        $get_product_attributes = 'select `id`,`name` from ' . $db->table('product_attributes') . ' where `product_type_id`=' . $product['product_type_id'];

        $attributes = $db->fetchAll($get_product_attributes);

        foreach ($attributes as $a)
        {
            $attributes_map[$a['id']] = array('id' => $a['id'], 'name' => $a['name']);
            $attributes_mode[$a['id']] = '.*';
        }

        assign('attributes_mode', json_encode($attributes_mode));

        //读取产品库存表
        $get_inventory = 'select `attributes`,`inventory`,`inventory_await`,`inventory_logic` from ' . $db->table('inventory') . ' where `product_sn`=\'' . $product_sn . '\'';
        $inventory = $db->fetchAll($get_inventory);

        $inventory_json = array();
        foreach ($inventory as $inventory_tmp)
        {
            if($inventory_tmp)
            {
                $attribute_obj = json_decode($inventory_tmp['attributes']);
                foreach ($attribute_obj as $aid => $aval) {
                    if (!isset($attributes_map[$aid]['values'])) {
                        $attributes_map[$aid]['values'] = array();
                    }

                    $attributes_map[$aid]['values'][] = mb_convert_encoding($aval, 'UTF-8');
                }
            }

            $inventory_json[$inventory_tmp['attributes']] = $inventory_tmp['inventory_logic'];
        }

        //如果产品只有一组属性，则默认选中
        if(count($inventory) == 1)
        {
            assign('attributes_mode', $inventory[0]['attributes']);
        }

        assign('inventory_json', json_encode($inventory_json));

        foreach ($attributes_map as $aid => $value)
        {
            if(isset($attributes_map[$aid]['values']))
            {
                $attributes_map[$aid]['values'] = array_unique($value['values']);
            }
        }

        if(count($attributes_map) == 1)
        {
            assign('inventory_logic', $inventory_tmp['inventory_logic']);
        }
        assign('attributes', $attributes_map);
        assign('attributes_json', json_encode($attributes_map));
    } else {
        //产品没有属性表的情况
        assign('attributes_mode', '""');
        $get_inventory = 'select `attributes`,`inventory`,`inventory_await`,`inventory_logic` from ' . $db->table('inventory') . ' where `product_sn`=\'' . $product_sn . '\'';
        $inventory = $db->fetchAll($get_inventory);

        $inventory_json = array();
        foreach ($inventory as $inventory_tmp)
        {
            $attribute_obj = '';
            if($inventory_tmp['attributes'] != '')
            {
                $attribute_obj = json_decode($inventory_tmp['attributes']);

                foreach ($attribute_obj as $aid => $aval)
                {
                    if (!isset($attributes_map[$aid]['values']))
                    {
                        $attributes_map[$aid]['values'] = array();
                    }

                    $attributes_map[$aid]['values'][] = mb_convert_encoding($aval, 'UTF-8');
                }
            } else {
                $attributes_map = '';
            }

            $inventory_json[$inventory_tmp['attributes']] = $inventory_tmp['inventory_logic'];
            assign('inventory_logic', $inventory_tmp['inventory_logic']);
        }

        assign('inventory_json', json_encode($inventory_json));
        assign('attributes', array());
        assign('attributes_json', '""');
    }
    //读取评论信息
    $get_comments = 'select c.`id`,c.`comment`,c.`star`,c.`add_time`,m.`headimg`,m.`nickname` from '.$db->table('comment').' as c'.
                    ' join '.$db->table('member').' as m using(`account`) where c.`parent_id`=0 and `product_sn`=\''.$product_sn.'\'';
    $comments = $db->fetchAll($get_comments);
    if($comments)
    {
        foreach($comments as $key=>$c)
        {
            $get_reply = 'select `comment`,`add_time` from '.$db->table('comment').' where `parent_id`='.$c['id'];

            $comments[$key]['reply'] = $db->fetchRow($get_reply);
        }
    }
    $product['comments'] = $comments;
    assign('comment_count', count($comments));

    //获取商家信息
    $get_business = 'select * from '.$db->table('business').' where `business_account`=\''.$product['business_account'].'\'';
    $product['business'] = $db->fetchRow($get_business);

    //检查产品的分销状态
    $get_distribution = 'select `product_sn` from '.$db->table('distribution').
                        ' where `account`=\''.$_SESSION['account'].'\' and `product_sn`=\''.$product_sn.'\'';
    $distribution_flag = $db->fetchOne($get_distribution) ? true : false;
    assign('distribution_flag', $distribution_flag);

    //检查产品的收藏状态
    $get_collection = 'select `product_sn` from '.$db->table('collection').
                      ' where `account`=\''.$_SESSION['account'].'\' and `product_sn`=\''.$product_sn.'\'';
    $collection_flag = $db->fetchOne($get_collection) ? true : false;
    assign('collection_flag', $collection_flag);

    //检查是否首单产品
    $check_activity = 'select `id` from '.$db->table('activity_mapper').' where `activity_id`=1 and `product_sn`=\''.$product_sn.'\'';
    $join_activity = $db->fetchOne($check_activity);
    assign('join_activity', $join_activity);

    //获取产品的推广链接
    $param = array('url'=>'product.php?id='.$product['id'], 'opera'=>'get_url', 'account'=>$_SESSION['account']);
    $get_url_response = post('http://'.$_SERVER['HTTP_HOST'].'/d/index.php', $param);

    $get_url_response = json_decode($get_url_response);
    if($get_url_response->error == 0)
    {
        assign('recommend_url', $get_url_response->url);
    } else {
        assign('recommend_url', '');
    }

    //加入我的足迹
    if(isset($_SESSION['account']) && $_SESSION['account'] != '')
    {
        add_history($_SESSION['account'], $product_sn);
    }
} else {
    redirect('index.php');
}

assign('user_info', $user_info);
assign('product', $product);
$smarty->display('product.phtml');