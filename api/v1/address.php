<?php
/**
 * Created by PhpStorm.
 * User: pengyuansen
 * Date: 2018/6/22
 * Time: 14:47
 */
include '../library/api.inc.php';
global $db, $log, $config, $current_user;

$operation = 'add|delete|edit|default';
$action = 'view|show|get_default';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));

$response = [
    'error' => -1,
    'message' => ''
];

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
    $province = getPOST('province');
    $city = getPOST('city');
    $district = getPOST('district');
    $group = 0;
    $address = getPOST('address');
    $is_default = 0;
    $mobile = getPOST('mobile');
    $consignee = getPOST('consignee');

    if(empty($province) || empty($city) || empty($district)) {
        $response['message'] .= "-请选择省/市/区";
    } else {
        if(mb_substr($province, -1) == '省') {
            $province = mb_substr($province, 0, -1);
        }

        $province = $db->getColumn('province', 'id', ['province_name' => $province]);

        if ($province <= 0) {
            $response['message'] .= "-请选择省份\n";
        }

        if(mb_substr($city, -1) == '市') {
            $city = mb_substr($city, 0, -1);
        }

        $city = $db->getColumn('city', 'id', ['city_name' => $city]);

        if ($city <= 0) {
            $response['message'] .= "-请选择城市\n";
        }

        if(mb_substr($district, -1) == '区') {
            $district = mb_substr($district, 0, -1);
        }

        $district = $db->getColumn('district', 'id', ['district_name' => $district]);

        if ($district <= 0) {
            $response['message'] .= "-请选择区\n";
        }
    }

    if($address == '') {
        $response['message'] .= "-请填写详细地址\n";
    } else {
        $address = $db->escape($address);
    }

    if(!is_mobile($mobile))
    {
        $response['message'] .= "-手机号码格式错误";
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

    $province = getPOST('province');
    $city = getPOST('city');
    $district = getPOST('district');
    $group = 0;
    $address = getPOST('address');
    $is_default = 0;
    $mobile = getPOST('mobile');
    $consignee = getPOST('consignee');

    if(empty($province) || empty($city) || empty($district)) {
        $response['message'] .= "-请选择省/市/区";
    } else {
        if(mb_substr($province, -1) == '省') {
            $province = mb_substr($province, 0, -1);
        }

        $province = $db->getColumn('province', 'id', ['province_name' => $province]);

        if ($province <= 0) {
            $response['message'] .= "-请选择省份\n";
        }

        if(mb_substr($city, -1) == '市') {
            $city = mb_substr($city, 0, -1);
        }

        $city = $db->getColumn('city', 'id', ['city_name' => $city]);

        if ($city <= 0) {
            $response['message'] .= "-请选择城市\n";
        }

        if(mb_substr($district, -1) == '区') {
            $district = mb_substr($district, 0, -1);
        }

        $district = $db->getColumn('district', 'id', ['district_name' => $district]);

        if ($district <= 0) {
            $response['message'] .= "-请选择区\n";
        }
    }

    if($address == '') {
        $response['message'] .= "-请填写详细地址\n";
    } else {
        $address = $db->escape($address);
    }

    if(!is_mobile($mobile))
    {
        $response['message'] .= "-手机号码格式错误";
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
            'mobile' => $address_info['mobile']
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
        'a.`mobile`,a.`zipcode`,a.`id` from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
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
            'region' => [
                $address['province_name'].'省',
                $address['city_name'].'市',
                $address['district_name'].'区'
            ]
        ];

        $response['error'] = 0;
        $response['message'] = '获取地址信息成功';
    }
}

if('view' == $act)
{
    $response['error'] = 0;
    $response['address'] = [];

    $get_address_list = 'select a.`is_default`,p.`province_name`,c.`city_name`,d.`district_name`,a.`address`,a.`consignee`,'.
        'a.`mobile`,a.`zipcode`,a.`id` from '.$db->table('address').' as a, '.$db->table('province').' as p, '.
        $db->table('city').' as c, '.$db->table('district').' as d where '.
        'a.`province`=p.`id` and a.`city`=c.`id` and a.`district`=d.`id` '.
        ' and a.`account`=\''.$current_user['account'].'\' order by `is_default` DESC';
    $address_list = $db->fetchAll($get_address_list);

    if($address_list)
    {
        foreach ($address_list as &$address)
        {
            $address['is_default'] = $address['is_default'] ? true : false;
            $address['address'] = $address['province_name'] . ' ' . $address['city_name'] . ' ' . $address['district_name'] . ' ' . $address['address'];
            unset($address['province_name']);
            unset($address['city_name']);
            unset($address['district_name']);
            unset($address['zipcode']);
        }

        $response['address'] = $address_list;
    }
}

echo json_encode($response);