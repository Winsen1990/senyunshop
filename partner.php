<?php
/**
 * 合伙人入口
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-11-2
 * @version 
 */

include 'library/init.inc.php';

$operation = 'apply';
$opera = check_action($operation, getPOST('opera'));
if('apply' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $shop_name = getPOST('shop_name');
    $license = $_FILES['license'];
    $identity = $_FILES['identity'];
    $industry = intval(getPOST('industry'));
    $category = intval(getPOST('category'));
    $province = intval(getPOST('province'));
    $city = intval(getPOST('city'));
    $district = intval(getPOST('district'));
    $group = intval(getPOST('group'));
    $address = getPOST('address');
    $contact = getPOST('contact');
    $mobile = getPOST('mobile');
    $email = getPOST('email');

    $name = '';

    if($shop_name == '')
    {
        $response['msg'] .= '-请填写网店名称<br/>';
    } else {
        $shop_name = $db->escape($shop_name);
    }

    if($industry <= 0)
    {
        $response['msg'] .= '-请选择主营行业<br/>';
    }

    if($category <= 0)
    {
        $response['msg'] .= '-请选择主营分类<br/>';
    }

    if($province <= 0 || $city <= 0 || $district <= 0 || $group <= 0)
    {
        $response['msg'] .= '-请选择所在地区<br/>';
    }

    if($address == '')
    {
        $response['msg'] .= '-请填写详细地址<br/>';
    }

    if($license == '' || $license['error'] > 0)
    {
        $response['msg'] .= '-请上传身份证正面<br/>';
    } else {
        $save_path = 'upload/image/license/';
        $file_name = '';
        $file_name_arr = explode('.', $license['name']);
        $tail = $file_name_arr[count($file_name_arr)-1];

        do
        {
            $file_name = date('YmdHis_').rand(10000, 99999).'.'.$tail;
        } while(file_exists($save_path.$file_name));

        if(move_uploaded_file($license['tmp_name'], $save_path.$file_name))
        {
            $php_url = dirname($_SERVER['PHP_SELF']) . '/';
            $root_url = $php_url . 'upload/image/license/';
            $license = $root_url.$file_name;
        } else {
            $response['msg'] .= '-身份证正面上传失败，请稍后再试<br/>';
        }
    }

    if($identity == '' || $identity['error'] > 0)
    {
        $response['msg'] .= '-请上传身份证反面<br/>';
    } else {
        $save_path = 'upload/image/identity/';
        $file_name = '';
        $file_name_arr = explode('.', $identity['name']);
        $tail = $file_name_arr[count($file_name_arr)-1];

        do
        {
            $file_name = date('YmdHis_').rand(10000, 99999).'.'.$tail;
        } while(file_exists($save_path.$file_name));

        if(move_uploaded_file($identity['tmp_name'], $save_path.$file_name))
        {
            $php_url = dirname($_SERVER['PHP_SELF']) . '/';
            $root_url = $php_url . 'upload/image/identity/';
            $identity = $root_url.$file_name;
        } else {
            $response['msg'] .= '-身份证反面上传失败，请稍后再试<br/>';
        }
    }

    if($contact == '')
    {
        $response['msg'] .= '-请填写联系人<br/>';
    } else {
        $contact = $db->escape($contact);
    }

    if($mobile == '')
    {
        $response['msg'] .= '-请填写手机号码<br/>';
    } else {
        $mobile = $db->escape($mobile);
    }

    if($email == '')
    {
        $response['msg'] .= '-请填写邮箱<br/>';
    } else {
        $email = $db->escape($email);
    }

    if($response['msg'] == '')
    {
        $business_data = array(
            'license' => $license,
            'identity' => $identity,
            'company' => $name,
            'industry_id' => $industry,
            'category_id' => $category,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'group' => $group,
            'address' => $address,
            'contact' => $contact,
            'mobile' => $mobile,
            'email' => $email,
            'status' => 1,
            'shop_name' => $shop_name,
            'comment' => 0,
            'account' => $_SESSION['account']
        );

        $business_account = '';
        do
        {
            $business_account = 'B'.rand(100000, 999999);
            $check_business_account = 'select `id` from '.$db->table('business').' where `business_account`=\''.$business_account.'\'';
        } while($db->fetchOne($check_business_account));

        $business_data['business_account'] = $business_account;
        $business_data['password'] = md5($business_account.PASSWORD_END);

        if($db->autoInsert('business', array($business_data)))
        {
            $response['error'] = 0;
            $response['msg'] = '您的申请已提交，请等待平台审核';
        } else {
            $response['msg'] = '001:系统繁忙，请稍后再试';
        }
    }

    assign('response', json_encode($response));
}

//检查如果已有店铺的不允许再次申请
$get_business_accout = 'select `business_account` from '.$db->table('business').' where `account`=\''.$_SESSION['account'].'\'';
$business_account = $db->fetchOne($get_business_accout);
if($business_account)
{
    $_SESSION['business_account'] = $business_account;
    redirect('apply_status.php');
}


$get_category = 'select `id`,`name`,`parent_id` from '.$db->table('category').' where `business_account`=\'\'';
$category = $db->fetchAll($get_category);
assign('category', $category);

$get_industry = 'select `id`,`name` from '.$db->table('industry');
$industry = $db->fetchAll($get_industry);
assign('industry', $industry);

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

$smarty->display('partner.phtml');


