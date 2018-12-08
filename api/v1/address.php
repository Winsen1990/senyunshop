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
        $response['message'] = '参数错误';
    }

    if($response['message'] == '')
    {
        //检查如果地址是默认地址，则修改默认地址到下一个地址
        $is_default = $db->getColumn('address', 'is_default', ['id' => $id, 'account' => $current_user['account']]);

        if(!$is_default)
        {
            $data = [
                'is_default' => 0
            ];

            $db->upgrade('address', $data, ['account' => $current_user['account']], 1);

            $data['is_default'] = 1;
            $db->upgrade('address', $data, ['account' => $current_user['account'], 'id' => $id]);

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
        $response['message'] = '参数错误';
    }

    if($response['message'] == '')
    {
        //检查如果地址是默认地址，则修改默认地址到下一个地址
        $is_default = $db->getColumn('address', 'is_default', ['id' => $id, 'account' => $current_user['account']]);

        if($db->destroy('address', ['id' => $id, 'account' => $current_user['account']]))
        {
            if($is_default)
            {
                $data = [
                    'is_default' => 1
                ];

                $db->upgrade('address', $data, ['account' => $current_user['account']], 1);
            }
            $response['error'] = 0;
            $response['message'] = '删除收货地址成功';
        } else {
            $response['message'] = '系统繁忙，请稍后再试';
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
        $response['message'] .= '请选择省/市/区';
    }

    if($address == '') {
        $response['message'] .= '请填写详细地址';
    } else {
        $address = $db->escape($address);
    }

    if(!is_mobile($mobile))
    {
        $response['message'] .= '手机号码格式错误';
    } else {
        $mobile = $db->escape($mobile);
    }

    if($response['message'] == '')
    {
        $province_name = $db->getColumn('province', 'province_name', ['id' => $province]);
        $city_name = $db->getColumn('city', 'city_name', ['id' => $city]);
        $district_name = $db->getColumn('district', 'district_name', ['id' => $district]);
        $group_name = $db->getColumn('group', 'group_name', ['id' => $group]);

        if($is_default)
        {
            $db->upgrade('address', ['is_default' => 0], ['account' => $current_user['account']]);
        } else {
            //检查用户地址如果为空则默认为默认地址
            $address_count = $db->getColumn('address', 'count(*)', ['account' => $current_user['address']]);

            if($address_count == 0)
            {
                $is_default = 1;
            }
        }

        $address_data = array(
            'province' => $province,
            'province_name' => $province_name,
            'city' => $city,
            'city_name' => $city_name,
            'district' => $district,
            'district_name' => $district_name,
            'group' => $group,
            'group_name' => $group_name,
            'address' => $address,
            'mobile' => $mobile,
            'consignee' => $consignee,
            'is_default' => $is_default,
            'account' => $current_user['account']
        );

        if($db->create('address', $address_data))
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
        $response['message'] .= '-请填写详细地址';
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
        $province_name = $db->getColumn('province', 'province_name', ['id' => $province]);
        $city_name = $db->getColumn('city', 'city_name', ['id' => $city]);
        $district_name = $db->getColumn('district', 'district_name', ['id' => $district]);
        $group_name = $db->getColumn('group', 'group_name', ['id' => $group]);

        $address_data = [
            'province' => $province,
            'province_name' => $province_name,
            'city' => $city,
            'city_name' => $city_name,
            'district' => $district,
            'district_name' => $district_name,
            'group' => $group,
            'group_name' => $group_name,
            'address' => $address,
            'mobile' => $mobile,
            'consignee' => $consignee
        ];

        if($db->upgrade('address', $address_data, ['id' => $id, 'account' => $current_user['account']]) !== false)
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
    $address_info = $db->find('address', [
        'id',
        'province',
        'province_name',
        'city',
        'city_name',
        'district',
        'district_name',
        'group',
        'group_name',
        'address',
        'mobile',
        'consignee',
        'zipcode'
    ], ['account' => $current_user['account'], 'is_default' => 1]);

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
        $response['message'] = '参数错误';
    }
    
}

if('show' == $act)
{
    $id = intval(getGET('id'));
    if($id <= 0)
    {
        throw new RestFulException('参数错误', 401);
    }

    $address = $db->find('address', [
        'id',
        'province',
        'province_name',
        'city',
        'city_name',
        'district',
        'district_name',
        'group',
        'group_name',
        'address',
        'mobile',
        'consignee',
        'zipcode'
    ], ['account' => $current_user['account'], 'id' => $id]);

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

    $address_list = $db->all('address', [
        'id',
        'province',
        'province_name',
        'city',
        'city_name',
        'district',
        'district_name',
        'group',
        'group_name',
        'address',
        'mobile',
        'consignee',
        'zipcode'
    ], ['account' => $current_user['account']], null, [['is_default', 'DESC']]);

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