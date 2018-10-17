<?php
include 'library/init.inc.php';
back_base_init();

global $db, $log, $config;

$template = 'sysconf/';

assign('subTitle', '系统参数设置');

$action = 'edit|add|view';
$operation = 'edit|add';

$act = check_action($action, getGET('act'));
$opera = check_action($operation, getPOST('opera'));

$act = ( $act == '' ) ? 'view' : $act;

//==========================================================

//新增系统参数
if('add' == $opera)
{
    if(!check_purview('pur_sysconf_add', $_SESSION['purview']))
    {
        show_system_message('权限不足', array());
        exit;
    }

}

//修改系统参数
if('edit' == $opera)
{
    if(!check_purview('pur_sysconf_edit', $_SESSION['purview']))
    {
        show_system_message('权限不足', array());
        exit;
    }

    $key = getPOST('key');
    $value = getPOST('value');
    $type = getPOST('type');

    if('' == $key)
    {
        show_system_message('参数错误', array());
        exit;
    }

    switch($type)
    {
        case 'radio':
            $value = intval($value);
        case 'text':
        case 'textarea':
            $value = $db->escape(htmlspecialchars($value));
            $key = $db->escape($key);
            $updateConf = 'update '.$db->table('sysconf').' set `value`=\''.$value.'\' where `key`=\''.$key.'\' limit 1';
            if($db->update($updateConf) !== false)
            {
                $links = array(
                    array('alt'=>'查看系统参数', 'link'=>'sysconf.php')
                );
//            show_system_message('修改系统参数成功', $links);
//            exit;
                header('Location:sysconf.php');
                exit;
            } else {
                show_system_message('系统繁忙，请稍后再试', array());
                exit;
            }
            break;
        case 'img':
            $value = $db->escape(htmlspecialchars($value));
            $key = $db->escape($key);
            $getValue = 'select value from '.$db->table('sysconf').' where `key` = \''.$key.'\' limit 1';
            $oldValue = $db->fetchOne($getValue);
            $updateConf = 'update '.$db->table('sysconf').' set `value`=\''.$value.'\' where `key`=\''.$key.'\' limit 1';
            if($db->update($updateConf) !== false)
            {
                if( file_exists(realpath('../'.$oldValue) ) ) {
                    @unlink(realpath('../'.$oldValue));
                }
                $links = array(
                    array('alt'=>'查看系统参数', 'link'=>'sysconf.php')
                );
                //show_system_message('修改系统参数成功', $links);
                redirect('sysconf.php');
            } else {
                show_system_message('系统繁忙，请稍后再试', array());
                exit;
            }
            break;
        default:
    }
}

if('view' == $act)
{
    if(!check_purview('pur_sysconf_view', $_SESSION['purview']))
    {
        show_system_message('权限不足', array());
        exit;
    }


    $getSysconf = 'select `key`,`name`,`type`,`value`, `remark`, `group` from '.$db->table('sysconf').' order by `group` asc,`key` asc';
    $sysconf = $db->fetchAll($getSysconf);
    assign('sysconf', $sysconf);

}

if('edit' == $act)
{
    if(!check_purview('pur_sysconf_edit', $_SESSION['purview']))
    {
        show_system_message('权限不足', array());
        exit;
    }


    $key = getGET('key');
    if('' == $key)
    {
        show_system_message('参数错误', array());
    } else {
        $getSysconf  = 'select `key`,`name`,`type`,`value` from '.$db->table('sysconf').'';
        $getSysconf .= ' where `key`=\''.$key.'\'';

        assign('conf', $db->fetchRow($getSysconf));
    }
}
assign('act', $act);

$template .= $act.'.phtml';
$smarty->display($template);
