<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/22
 * Time: 下午4:13
 */
include 'library/init.inc.php';

if(!isset($_SESSION['business_account']))
{
    redirect('index.php');
}

$get_business_status = 'select `status` from '.$db->table('business').' where `business_account`=\''.$_SESSION['business_account'].'\'';
assign('status', $db->fetchOne($get_business_status));

$smarty->display('apply_status.phtml');