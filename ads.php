<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/11/16
 * Time: 下午4:45
 */
include 'library/init.inc.php';

$id = intval(getGET('id'));

if($id <= 0)
{
    redirect('index.php');
}

$get_ad_pos = 'select `pos_name` from '.$db->table('ad_position').' where `id`='.$id;

$ad_pos = $db->fetchOne($get_ad_pos);

if(!$ad_pos)
{
    redirect('index.php');
}

$get_ad_list = 'select `img`,`url` from '.$db->table('ad').' where `ad_pos_id`='.$id.' order by `order_view`';

$ad_list = $db->fetchAll($get_ad_list);

assign('title', $ad_pos);
assign('ad_list', $ad_list);
$smarty->display('ads.phtml');