<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午2:50
 */
include 'library/init.inc.php';

$id = intval(getGET('id'));

$template = 'category.phtml';
$product_list = array();

$flag = false;

if($id > 0)
{
    $state = getGET('state');
    $state_list = 'sale_amount|price|discount|star|add_time';

    $state = check_action($state_list, $state);

    if('' == $state)
    {
        $state = 'sale_amount';
    }

    $get_category_path = 'select `path` from '.$db->table('category').' where `id`='.$id;
    $path = $db->fetchOne($get_category_path);

    $get_category_ids = 'select `id` from '.$db->table('category').' where `path` like \''.$path.'%\' and `id` not in ('.$path.'0)';
    $category_ids = $db->fetchAll($get_category_ids);
    $category_ids_tmp = array();
    if($category_ids)
    {
        foreach($category_ids as $key=>$val)
        {
            $category_ids_tmp[] = $val['id'];
        }
    }
    $category_ids_str = implode(',', $category_ids_tmp);

    $get_product_list = 'select * from '.$db->table('product').' where `category_id` in (\''.$category_ids_str.'\')';

    $product_list = $db->fetchAll($get_product_list);
    if($product_list)
    {
        assign('state', $state);
        assign('product_list', $product_list);
        $template = 'product-list.phtml';
    } else {
        $flag = true;
    }
} else {
    $flag = true;
}

if($flag)
{
    $get_category_list = 'select * from '.$db->table('category').' where `business_account`=\'\' and `parent_id`=0';
    $category_list = $db->fetchAll($get_category_list);
    $target_category_list = array();

    $category_2_list = array();
    foreach($category_list as $cat)
    {
        $get_children = 'select `id`,`name`,`parent_id` from '.$db->table('category').' where `business_account`=\'\' and `parent_id`='.$cat['id'];
        $children = $db->fetchAll($get_children);

        if($children)
        {
            foreach ($children as $key => $cc)
            {
                $get_children = 'select `id`,`name`,`parent_id`,`icon` from ' . $db->table('category') . ' where `business_account`=\'\' and `parent_id`=' . $cc['id'];
                $cc = $db->fetchAll($get_children);
                $children[$key]['children'] = $cc;
            }
        }

        $category_2_list[$cat['id']] = $children;
        $target_category_list[$cat['id']] = $cat;
    }

    assign('category_list', $target_category_list);
    assign('category_2_list', $category_2_list);
}

$smarty->display($template);
