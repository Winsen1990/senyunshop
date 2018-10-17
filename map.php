<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/10/22
 * Time: 下午3:07
 */
include 'library/init.inc.php';

$business_account = getGET('business_account');
if($business_account == '')
{
    redirect('index.php');
    exit;
}

$business_account = $db->escape($business_account);
$get_business_info = 'select * from '.$db->table('business').' where `business_account`=\''.$business_account.'\'';

$business_info = $db->fetchRow($get_business_info);
assign('business_info', $business_info);

$smarty->display('map.phtml');