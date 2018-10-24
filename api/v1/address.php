<?php
/**
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/22
 * Time: 14:47
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$operation = 'add|delete|edit|default|delivery_check';
$action = 'view|show|get_default|data';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));

$response = [
    'error' => -1,
    'message' => ''
];

//能否配送检验
if('delivery_check' == $opera)
{
    $province = intval(getPOST('province'));
    $city = intval(getPOST('city'));
    $district = intval(getPOST('district'));

    if($province > 0 && $city > 0 && $district > 0) {
        //读取地址所属区域
        //1.省市区区域
        $area_ids = $db->all('delivery_area_mapper', ['DISTINCT(`area_id`)'], [
            'province' => $province,
            'city' => $city,
            'district' => $district
        ]);

        //2.省市区域
        if(empty($area_ids)) {
            $area_ids = $db->all('delivery_area_mapper', ['DISTINCT(`area_id`)'], [
                'province' => $province,
                'city' => $city,
                'district' => 0
            ]);

            //3.省级区域
            if(empty($area_ids)) {
                $area_ids = $db->all('delivery_area_mapper', ['DISTINCT(`area_id`)'], [
                    'province' => $province,
                    'city' => 0,
                    'district' => 0
                ]);
            }
        }

        if(!empty($area_ids)) {
            $response['error'] = 0;
            $response['message'] = '当前地区支持配送';
        } else {
            $response['message'] = '当前地区不支持配送';
        }
    } else {
        $response['message'] = '参数错误';
    }
}

if('default' == $opera)
{
    $id = getPOST('id');

    $id = intval($id);
    if($id <= 0)
    {
        $response['message'] = '-参数错误<br/>';
    }

    if($response['message'] == '')
    {
        //检查如果地址是默认地址，则修改默认地址到下一个地址
        $check_default = 'select `is_default` from '.$db->table('address').' where `id`='.$id.' and `account`=\''.$current_user['account'].'\'';

        $is_default = $db->fetchOne($check_default);

        if(!$is_default)
        {
            $data = array(
                'is_default' => 0
            );

            $db->autoUpdate('address', $data, '`account`=\''.$current_user['account'].'\'', '', 1);

            $data['is_default'] = 1;
            $db->autoUpdate('address', $data, '`account`=\''.$current_user['account'].'\' and `id`='.$id, '', 1);

            $response['error'] = 0;
            $response['message'] = '设置默认收货地址成功';
        } else {
            $response['error'] = 0;
            $response['message'] = '设置默认收货地址成功';
        }
    }
}

if('delete' == $opera)
{
    $id = getPOST('id');

    $id = intval($id);
    if($id <= 0)
    {
        $response['message'] = '-参数错误<br/>';
    }

    if($response['message'] == '')
    {
        //检查如果地址是默认地址，则修改默认地址到下一个地址
        $check_default = 'select `is_default` from '.$db->table('address').' where `id`='.$id.' and `account`=\''.$current_user['account'].'\'';

        $is_default = $db->fetchOne($check_default);

        if($db->autoDelete('address', '`id`='.$id.' and `account`=\''.$current_user['account'].'\''))
        {
            if($is_default)
            {
                $data = array(
                    'is_default' => 1
                );

                $db->autoUpdate('address', $data, '`account`=\''.$current_user['account'].'\'', '', 1);
            }
            $response['error'] = 0;
            $response['message'] = '删除收货地址成功';
        } else {
            $response['message'] = '001:系统繁忙，请稍后再试';
        }
    }
}

if('add' == $opera)
{
    $province = intval(getPOST('province'));
    $city = intval(getPOST('city'));
    $district = intval(getPOST('district'));
    $group = intval(getPOST('group'));
    $address = getPOST('address');
    $is_default = 0;
    $mobile = getPOST('mobile');
    $consignee = getPOST('consignee');

    $province = max(0, $province);
    $city = max(0, $city);
    $district = max(0, $district);
    $group = max(0, $group);

    if(empty($province) || empty($city) || empty($district) || empty($group)) {
        $response['message'] .= '-请选择省/市/区';
    }

    if($address == '') {
        $response['message'] .= '-请填写详细地址\n';
    } else {
        $address = $db->escape($address);
    }

    if(!is_mobile($mobile))
    {
        $response['message'] .= '-手机号码格式错误';
    } else {
        $mobile = $db->escape($mobile);
    }

    if($response['message'] == '')
    {
        if($is_default)
        {
            $db->autoUpdate('address', ['is_default' => 0], '`account`=\''.$current_user['account'].'\'');
        } else {
            //检查用户地址如果为空则默认为默认地址
            $check_address = 'select count(*) from '.$db->table('address').' where `account`=\''.$current_user['account'].'\'';

            $address_count = intval($db->fetchOne($check_address));

            if($address_count == 0)
            {
                $is_default = 1;
            }
        }

        $address_data = array(
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'group' => $group,
            'address' => $address,
            'mobile' => $mobile,
            'consignee' => $consignee,
            'is_default' => $is_default,
            'account' => $current_user['account']
        );

        if($db->autoInsert('address', array($address_data)))
        {
            $response['error'] = 0;
            $response['message'] = '新增收货地址成功';
            $response['id'] = $db->get_last_id();
        } else {
            $response['message'] = '001:系统繁忙，请稍后再试';
        }
    }
}

if('edit' == $opera)
{
    $id = intval(getPOST('id'));

    if($id <= 0)
    {
        throw new RestFulException('参数错误', 401);
    }

    $province = intval(getPOST('province'));
    $city = intval(getPOST('city'));
    $district = intval(getPOST('district'));
    $group = intval(getPOST('group'));
    $address = getPOST('address');
    $is_default = 0;
    $mobile = getPOST('mobile');
    $consignee = getPOST('consignee');

    $province = max(0, $province);
    $city = max(0, $city);
    $district = max(0, $district);
    $group = max(0, $group);

    if(empty($province) || empty($city) || empty($district) || empty($group)) {
        $response['message'] .= '-请选择省/市/区';
    }

    if($address == '') {
        $response['message'] .= '-请填写详细地址\n';
    } else {
        $address = $db->escape($address);
    }

    if(!is_mobile($mobile))
    {
        $response['message'] .= '-手机号码格式错误';
    } else {
        $mobile = $db->escape($mobile);
    }

    if($response['message'] == '')
    {
        if($is_default)
        {
            $db->autoUpdate('address', array('is_default'=>0), '`account`=\''.$current_user['account'].'\'');
        } else {
            //检查用户地址如果为空则默认为默认地址
            $check_address = 'select count(*) from '.$db->table('address').' where `account`=\''.$current_user['account'].'\'';

            $address_count = $db->fetchOne($check_address);
            $address_count = intval($address_count);

            if($address_count == 1)
            {
                $is_default = 1;
            }
        }

        $address_data = array(
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'group' => $group,
            'address' => $address,
            'mobile' => $mobile,
            'consignee' => $consignee,
//            'is_default' => $is_default
        );

        if($db->autoUpdate('address', $address_data, '`id`='.$id.' and `account`=\''.$current_user['account'].'\'') !== false)
        {
            $response['error'] = 0;
            $response['message'] = '收货地址修改成功';
            $response['id'] = $id;
        } else {
            $response['message'] = '001:系统繁忙，请稍后再试';
        }
    }
}

if('get_default' == $act)
{
    $get_address_detail = 'select a.`is_default`,p.`province_name`,c.`city_name`,d.`district_name`,a.`address`,a.`consignee`,'.
        'a.`province`,a.`city`,a.`district`,a.`group`,'.
        'a.`mobile`,a.`zipcode`,a.`id` from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
        $db->table('city').' as c, '.$db->table('district').' as d where '.
        'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` '.
        ' and a.`account`=\''.$current_user['account'].'\' and a.is_default = 1';

    $address_info = $db->fetchRow($get_address_detail);

    if($address_info)
    {
        $response['error'] = 0;
        $response['address'] = [
            'detail' => $address_info['province_name'].' '.$address_info['city_name'].' '.$address_info['district_name'].' '.$address_info['address'],
            'consignee' => $address_info['consignee'],
            'id' => $address_info['id'],
            'mobile' => $address_info['mobile'],
            'province' => $address_info['province'],
            'city' => $address_info['city'],
            'district' => $address_info['district'],
            'group' => $address_info['group']
        ];
        $response['message'] = '获取默认地址成功';
    } else {
        $response['message'] = '000:参数错误';
    }
    
}

if('show' == $act)
{
    $id = intval(getGET('id'));
    if($id <= 0)
    {
        throw new RestFulException('参数错误', 401);
    }

    $get_address = 'select a.`is_default`,p.`province_name`,c.`city_name`,d.`district_name`,a.`address`,a.`consignee`,'.
        'a.`province`,a.`city`,a.`district`,a.`group`,'.
        'a.`mobile`,a.`zipcode`,a.`id`,(select `group_name` from '.$db->table('group').' where `id`=a.`group`) as group_name'.
        ' from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
        $db->table('city').' as c, '.$db->table('district').' as d where '.
        'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` '.
        ' and a.`account`=\''.$current_user['account'].'\' and a.id = '.$id;

    $address = $db->fetchRow($get_address);

    if(!$address)
    {
        throw new RestFulException('参数错误', 401);
    } else {
        $response['address'] = [
            'id' => $address['id'],
            'consignee' => $address['consignee'],
            'mobile' => $address['mobile'],
            'detail' => $address['address'],
            'province' => $address['province'],
            'city' => $address['city'],
            'district' => $address['district'],
            'group' => $address['group'],
            'region' => [
                $address['province_name'],
                $address['city_name'],
                $address['district_name'],
                $address['group_name']
            ]
        ];

        $response['error'] = 0;
        $response['message'] = '获取地址信息成功';
    }
}

if('view' == $act)
{
    $response['error'] = 0;
    $response['addresses'] = [];

    $get_address_list = 'select a.`is_default`,p.`province_name`,c.`city_name`,d.`district_name`,a.`address`,a.`consignee`,'.
        'a.`mobile`,a.`zipcode`,a.`id`,(select `group_name` from '.$db->table('group').' where `id`=a.`group`) as group_name '.
        ' from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
        $db->table('city').' as c, '.$db->table('district').' as d '.
        ' where '.
        'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` '.
        ' and a.`account`=\''.$current_user['account'].'\' order by `is_default` DESC';
    $address_list = $db->fetchAll($get_address_list);

    if($address_list)
    {
        foreach ($address_list as &$address)
        {
            $address['is_default'] = $address['is_default'] ? true : false;
            $address['address'] = $address['province_name'] . ' ' . $address['city_name'] . ' ' . $address['district_name'] . ' '. $address['group_name'].' ' . $address['address'];
            unset($address['province_name']);
            unset($address['city_name']);
            unset($address['district_name']);
            unset($address['zipcode']);
        }

        $response['addresses'] = $address_list;
    }
}

if('data' == $act)
{
    $response['error'] = 0;

    $province = $db->all('province', ['id', 'province_name']);
    foreach($province as &$_province) {
        $_province['id'] = intval($_province['id']);
        $_province['name'] = $_province['province_name'];
        unset($_province['province_name']);
    }
    $response['province'] = $province;

    $city = $db->all('city', ['id', 'city_name', 'province_id']);
    foreach($city as &$_city) {
        $_city['id'] = intval($_city['id']);
        $_city['province_id'] = intval($_city['province_id']);
        $_city['name'] = $_city['city_name'];
        unset($_city['city_name']);
    }
    $response['city'] = $city;

    $district = $db->all('district', ['id', 'district_name', 'city_id']);
    foreach($district as &$_district) {
        $_district['id'] = intval($_district['id']);
        $_district['city_id'] = intval($_district['city_id']);
        $_district['name'] = $_district['district_name'];
        unset($_district['district_name']);
    }
    $response['district'] = $district;

    $group = $db->all('group', ['id', 'group_name', 'district_id']);
    foreach($group as &$_group) {
        $_group['id'] = intval($_group['id']);
        $_group['district_id'] = intval($_group['district_id']);
        $_group['name'] = $_group['group_name'];
        unset($_group['group_name']);
    }
    $response['group'] = $group;
}

echo json_encode($response);