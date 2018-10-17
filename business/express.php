<?php
/**
 * 物流方式设置
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-19
 * @version 1.0.0
 */

include 'library/init.inc.php';

global $plugins;

$plugins = array();
//商户管理后台初始化
business_base_init();
$template = 'express/';

$action = 'view|edit|delete|install|add|uninstall|delivery_area_set|delivery_area|delivery_area_edit|delivery_area_delete';
$operation = 'edit|area_add|area_edit';
$act = check_action($action, getGET('act'));
$opera = check_action($operation, getPOST('opera'));
$act = ( $act == '' ) ? 'view' : $act;

$delivery_status = array(
    -1 => '未安装',
    0  => '停用',
    1  => '启用'
);
//===============================================================================
if('area_edit' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $id = intval(getPOST('area_id'));
    $area_mapper = getPOST('area_mapper');
    $first_weight = floatval(getPOST('first_weight'));
    $next_weight = floatval(getPOST('next_weight'));
    $free = floatval(getPOST('free'));
    $name = getPOST('name');

    if($name == '')
    {
        $response['msg'] .= "-请填写区域名称\n";
    } else {
        $name = $db->escape($name);
    }

    if($id <= 0)
    {
        $response['msg'] .= "-参数错误\n";
    }

    if($first_weight < 0)
    {
        $response['msg'] .= "-请填写首重费用\n";
    }

    if($next_weight < 0)
    {
        $response['msg'] .= "-请填写续重费用\n";
    }

    if($free < 0)
    {
        $response['msg'] .= "-请填写减免费用\n";
    }

    $area_list = array();
    $delete_condition = array();
    $area_flag = count($area_mapper);
    foreach($area_mapper as $a)
    {
        if($a['checked'] == 1 && !isset($a['id']))
        {
            $area_list[] = array(
                'province' => intval($a['province']),
                'city' => intval($a['city']),
                'district' => intval($a['district'])
            );
        } else if($a['checked'] == 0) {
            if(isset($a['id']) && $did = intval($a['id']))
            {
                $area_flag--;
                $delete_condition[] = '`area_id`='.$id.' and `id`='.$did;
            }
        }
    }

    if($area_flag <= 0)
    {
        $response['msg'] .= "-请选择所辖地区\n";
    }

    if($response['msg'] == '')
    {
        //添加配送区域
        $area_data = array(
            'first_weight' => $first_weight,
            'next_weight' => $next_weight,
            'free' => $free,
            'name' => $name
        );

        if($db->autoUpdate('delivery_area', $area_data, '`id`='.$id) !== false)
        {

            foreach($area_list as $key=>$a)
            {
                $a['area_id'] = $id;
                $area_list[$key] = $a;
            }

            if(!$db->autoInsert('delivery_area_mapper', $area_list))
            {
                $response['errmsg'] = '新增区域信息失败';
            }

            foreach($delete_condition as $condition)
            {
                $db->autoDelete('delivery_area_mapper', $condition);
            }

            $response['msg'] = '修改配送区域成功';
            $response['error'] = 0;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

if('area_add' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $delivery_id = intval(getPOST('delivery_id'));
    $area_mapper = getPOST('area_mapper');
    $first_weight = floatval(getPOST('first_weight'));
    $next_weight = floatval(getPOST('next_weight'));
    $free = floatval(getPOST('free'));
    $name = getPOST('name');

    if($name == '')
    {
        $response['msg'] .= "-请填写区域名称\n";
    } else {
        $name = $db->escape($name);
    }

    if($delivery_id <= 0)
    {
        $response['msg'] .= "-参数错误\n";
    }

    if($first_weight < 0)
    {
        $response['msg'] .= "-请填写首重费用\n";
    }

    if($next_weight < 0)
    {
        $response['msg'] .= "-请填写续重费用\n";
    }

    if($free < 0)
    {
        $response['msg'] .= "-请填写减免费用\n";
    }

    $area_list = array();
    foreach($area_mapper as $a)
    {
        if($a['checked'] == 1)
        {
            $area_list[] = array(
                'province' => intval($a['province']),
                'city' => intval($a['city']),
                'district' => intval($a['district'])
            );
        }
    }

    if(count($area_list) == 0)
    {
        $response['msg'] .= "-请选择所辖地区\n";
    }

    if($response['msg'] == '')
    {
        //添加配送区域
        $area_data = array(
            'first_weight' => $first_weight,
            'next_weight' => $next_weight,
            'free' => $free,
            'delivery_id' => $delivery_id,
            'name' => $name
        );

        if($db->autoInsert('delivery_area', array($area_data)))
        {
            $area_id = $db->get_last_id();

            foreach($area_list as $key=>$a)
            {
                $a['area_id'] = $area_id;
                $area_list[$key] = $a;
            }

            $db->autoInsert('delivery_area_mapper', $area_list);

            $response['msg'] = '设置配送区域成功';
            $response['error'] = 0;
            $response['id'] = $delivery_id;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}
//===============================================================================
if('delivery_area_delete' == $act)
{
    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    if($db->autoDelete('delivery_area', '`id`='.$id))
    {
        show_system_message('删除配送区域成功');
    } else {
        show_system_message('系统繁忙，请稍后再试');
    }
}

if('delivery_area' == $act)
{
    $id = intval(getGET('plugin'));

    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    $get_delivery_info = 'select `id`,`name` from '.$db->table('delivery').' where `id`='.$id;
    $delivery = $db->fetchRow($get_delivery_info);
    assign('delivery', $delivery);

    $get_area_list = 'select `id`,`first_weight`,`next_weight`,`free`,`delivery_id`,`name` from '.$db->table('delivery_area').
                     ' where `delivery_id`='.$id;

    $area_list = $db->fetchAll($get_area_list);
    assign('area_list', $area_list);
}

if('delivery_area_edit' == $act)
{
    $id = intval(getGET('id'));

    if($id <= 0)
    {
        show_system_message('参数错误');
    }

    $get_area_info = 'select `id`,`first_weight`,`next_weight`,`free`,`delivery_id`,`name` from '.$db->table('delivery_area').
                     ' where `id`='.$id;

    $area = $db->fetchRow($get_area_info);
    assign('area', $area);
    $delivery_id = $area['delivery_id'];

    $get_delivery_info = 'select `name`,`id` from '.$db->table('delivery').' where `id`='.$delivery_id;
    $delivery = $db->fetchRow($get_delivery_info);
    assign('delivery', $delivery);

    $get_province = 'select `id`,`province_name` as `name` from '.$db->table('province');
    $province = $db->fetchAll($get_province);
    assign('province', $province);

    $get_city = 'select `id`,`city_name` as `name`,`province_id` from '.$db->table('city');
    $city = $db->fetchAll($get_city);
    assign('city', $city);
    $city_json = array();
    foreach($city as $c)
    {
        if(!isset($city_json[$c['province_id']]))
        {
            $city_json[$c['province_id']] = array();
        }

        $city_json[$c['province_id']][] = $c;
    }
    assign('city_json', json_encode($city_json));

    $get_district = 'select `id`,`district_name` as `name`,`city_id` from '.$db->table('district');
    $district = $db->fetchAll($get_district);
    assign('district', $district);
    $district_json = array();
    foreach($district as $d)
    {
        if(!isset($district_json[$d['city_id']]))
        {
            $district_json[$d['city_id']] = array();
        }

        $district_json[$d['city_id']][] = $d;
    }
    assign('district_json', json_encode($district_json));

    $get_group = 'select `id`,`group_name` as `name`,`district_id` from '.$db->table('group');
    $group = $db->fetchAll($get_group);
    assign('group', $group);
    $group_json = array();
    foreach($group as $g)
    {
        if(!isset($group_json[$g['district_id']]))
        {
            $group_json[$g['district_id']] = array();
        }

        $group_json[$g['district_id']][] = $g;
    }
    assign('group_json', json_encode($group_json));

    //获取区域映射
    $get_area_mapper = 'select a.`province`,a.`city`,a.`district`,a.`id`,p.`province_name`,c.`city_name`,d.`district_name` '.
                       ' from '.$db->table('delivery_area_mapper').' as a left join '.$db->table('province').' as p on a.`province`=p.`id` '.
                       ' left join '.$db->table('city'). ' as c on a.`city`=c.`id` left join '.$db->table('district').' as d '.
                       ' on a.`district`=d.`id` where `area_id`='.$id;

    $area_mapper = $db->fetchAll($get_area_mapper);
    assign('area_mapper', $area_mapper);

    $area_mapper_json = array();
    if( $area_mapper ) {
        foreach ($area_mapper as $a) {
            unset($a['province_name']);
            unset($a['city_name']);
            unset($a['district_name']);
            $a['checked'] = 1;
            $area_mapper_json[] = $a;
        }
    }

    assign('area_mapper_json', json_encode($area_mapper_json));
}

if('delivery_area_set' == $act)
{
    $delivery_id = intval(getGET('delivery_id'));

    if($delivery_id <= 0)
    {
        show_system_message('参数错误');
    }

    $get_delivery_info = 'select `name`,`id` from '.$db->table('delivery').' where `id`='.$delivery_id;
    $delivery = $db->fetchRow($get_delivery_info);
    assign('delivery', $delivery);

    $get_province = 'select `id`,`province_name` as `name` from '.$db->table('province');
    $province = $db->fetchAll($get_province);
    assign('province', $province);

    $get_city = 'select `id`,`city_name` as `name`,`province_id` from '.$db->table('city');
    $city = $db->fetchAll($get_city);
    assign('city', $city);
    $city_json = array();
    if($city) {
        foreach ($city as $c) {
            if (!isset($city_json[$c['province_id']])) {
                $city_json[$c['province_id']] = array();
            }

            $city_json[$c['province_id']][] = $c;
        }
    }
    assign('city_json', json_encode($city_json));

    $get_district = 'select `id`,`district_name` as `name`,`city_id` from '.$db->table('district');
    $district = $db->fetchAll($get_district);
    assign('district', $district);
    $district_json = array();
    if($district) {
        foreach ($district as $d) {
            if (!isset($district_json[$d['city_id']])) {
                $district_json[$d['city_id']] = array();
            }

            $district_json[$d['city_id']][] = $d;
        }
    }
    assign('district_json', json_encode($district_json));

    $get_group = 'select `id`,`group_name` as `name`,`district_id` from '.$db->table('group');
    $group = $db->fetchAll($get_group);
    assign('group', $group);
    $group_json = array();
    if($group) {
        foreach ($group as $g) {
            if (!isset($group_json[$g['district_id']])) {
                $group_json[$g['district_id']] = array();
            }

            $group_json[$g['district_id']][] = $g;
        }
    }
    assign('group_json', json_encode($group_json));
}

if('install' == $act)
{
    $plugin = getGET('plugin');

    if($plugin == '')
    {
        show_system_message('参数错误');
    }

    $plugin = $db->escape($plugin);
    $plugin_path = ROOT_PATH.'plugins/express/';

    include $plugin_path.$plugin;

    $delivery_data = $plugins[0];

    $delivery_data['status'] = 1;

    if($db->autoInsert('delivery', array($delivery_data)))
    {
        $delivery_id = $db->get_last_id();
        $links = array(
            array('alt'=>'设置配送区域', 'link'=>'express.php?act=delivery_area_set&delivery_id='.$delivery_id)
        );
        show_system_message('插件安装成功，请设置配送区域', $links);
    } else {
        show_system_message('系统繁忙，请稍后再试');
    }
    exit;
}

if('uninstall' == $act)
{
    $plugin = getGET('plugin');

    if($plugin == '')
    {
        show_system_message('参数错误');
    }

    $plugin = $db->escape($plugin);

    //读取物流方式信息
    $get_delivery_id = 'select `id` from '.$db->table('delivery').
                       ' where `plugins`=\''.$plugin.'\'';
    $delivery_id = $db->fetchOne($get_delivery_id);

    if($delivery_id)
    {
        if($db->autoDelete('delivery', '`id`='.$delivery_id))
        {
            //获取区域信息
            $get_area_id = 'select `id` from '.$db->table('delivery_area').' where `delivery_id`='.$delivery_id;
            $area_ids = $db->fetchAll($get_area_id);

            $area_str = '';
            foreach($area_ids as $area)
            {
                $area_str .= $area['id'].',';
            }

            $area_str = substr($area_str, 0, strlen($area_str)-1);
            //删除区域信息
            $db->autoDelete('delivery_area', '`delivery_id`='.$delivery_id);
            //删除区域映射信息
            $db->autoDelete('delivery_area_mapper', '`area_id` in ('.$area_str.')');

            show_system_message('卸载物流方式成功');
        } else {
            show_system_message('系统繁忙，请稍后再试');
        }
    } else {
        show_system_message('插件已删除或不存在');
    }
    exit;
}

if('view' == $act)
{
    $plugin_path = ROOT_PATH.'plugins/express/';

    $dir = dir($plugin_path);

    $pattern = '/^[a-zA-Z]{1}[a-zA-Z0-9].*?\.class\.php$/';
    $files = array();
    while($file = $dir->read())
    {
        if(preg_match($pattern, $file))
        {
            $files[] = $file;
        }
    }

    foreach($files as $file)
    {
        include $plugin_path.$file;
    }

    $express_list = array();

    foreach($plugins as $plugin)
    {
        //检查该插件是否已经安装
        $check_plugin_status = 'select `id`,`self_delivery`,`name`,`status`,`desc`,`plugins` from '.$db->table('delivery').
                               ' where `plugins`=\''.$plugin['plugins'].'\'';

        $delivery_plugin = $db->fetchRow($check_plugin_status);
        if($delivery_plugin)
        {
            $delivery_plugin['show_status'] = $delivery_status[$delivery_plugin['status']];
            $express_list[] = $delivery_plugin;
        } else {
            $plugin['id'] = 0;
            $plugin['status'] = -1;
            $plugin['show_status'] = $delivery_status[-1];
            $express_list[] = $plugin;
        }
    }

    assign('express_list', $express_list);
}

$template .= $act.'.phtml';
$smarty->display($template);