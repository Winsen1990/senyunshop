<?php
/**
 *
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 15-11-4
 * @version 
 */

include 'library/init.inc.php';

$account = trim(getGET('account'));
if($account == '')
{
    $account = $_SESSION['account'];
}
//是否已是合伙人
$get_level_id = 'select `level_id` from '.$db->table('member').' where account = \''.$account.'\' limit 1';
$level_id = $db->fetchOne($get_level_id);

assign('act', 'apply');
assign('account', $account);
assign('level', $level_id);
$smarty->display('copartner.phtml');