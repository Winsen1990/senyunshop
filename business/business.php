<?php
/**
 * 商户信息管理
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-08-19
 * @version 1.0.0
 */

include 'library/init.inc.php';

//商户管理后台初始化
business_base_init();
$template = 'business/';

$action = 'base|auth';
$operation = 'base|auth|partner';

$opera = check_action($operation, getPOST('opera'));
$act = check_action($action, getGET('act'));
$act = ( $act == '' ) ? 'base' : $act;
//======================================================================
if( 'base' == $opera ) {
    if( !check_purview('pur_business_base', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

//    var_dump($_POST);exit;
    $shop_name = trim(getPOST('shop_name'));
    $shop_sign = trim(getPOST('sign'));
    $pc_shop_sign = trim(getPOST('pc-sign'));
    $shop_logo = trim(getPOST('img'));
    $address = trim(getPOST('address'));
    $province = intval(getPOST('province'));
    $city = intval(getPOST('city'));
    $district = intval(getPOST('district'));
    $group = intval(getPOST('group'));
    $longitude = floatval(getPOST('lng'));
    $latitude = floatval(getPOST('lat'));

    if( '' == $shop_name ) {
        show_system_message('店名不能为空', array());
        exit;
    }
    $shop_name = $db->escape($shop_name);

    if( '' == $shop_logo ) {
        show_system_message('请选择一张图片作为logo', array());
        exit;
    } else {
        $shop_logo = str_replace('upload', $config['domain'].'/upload', $shop_logo);
    }
    $shop_logo = $db->escape($shop_logo);

    $shop_sign = $db->escape($shop_sign);

    $pc_shop_sign = $db->escape($pc_shop_sign);


    if( '' == $address ) {
        show_system_message('详细地址不能为空', array());
        exit;
    }
    $address = $db->escape($address);

    if( 0 >= $province ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( 0 >= $city ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( 0 >= $district ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( 0 > $group ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( 0 > $longitude || 360 < $longitude ) {
        show_system_message('参数错误', array());
        exit;
    }

    if( -90 > $latitude || 90 < $latitude ) {
        show_system_message('参数错误', array());
        exit;
    }

    $get_old_shop_logo = 'select `shop_logo` from '.$db->table('business');
    $get_old_shop_logo .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_old_shop_logo .= ' limit 1';

    $old_shop_logo = $db->fetchOne($get_old_shop_logo);

    $data = array(
        'shop_name' => $shop_name,
        'shop_logo' => $shop_logo,
        'shop_sign' => $shop_sign,
        'address' => $address,
        'province' => $province,
        'city' => $city,
        'district' => $district,
        'group' => $group,
        'longitude' => $longitude,
        'latitude' => $latitude,
        'pc_shop_sign' => $pc_shop_sign,
    );
    $table = 'business';
    $where = 'business_account = \''.$_SESSION['business_account'].'\'';
    $order = '';
    $limit = '';

    if( $db->autoUpdate($table, $data, $where, $order, $limit) ) {

//        if( !empty($old_shop_logo) && ($old_shop_logo != $shop_logo) && file_exists(realpath('..'.$old_shop_logo)) ) {
//            unlink(realpath('..'.$old_shop_logo));
//        }
        show_system_message('更新商户信息成功', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }


}

if( 'auth' == $opera ) {
    if( !check_purview('pur_business_auth', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $company = trim(getPOST('company'));
    $desc = trim(getPOST('desc'));
    $industry_id = trim(getPOST('industry'));
    $category_id = trim(getPOST('classification'));
    $contact = trim(getPOST('contact'));
    $mobile = trim(getPOST('mobile'));
    $email = trim(getPOST('email'));
    $license = trim(getPOST('license'));
    $identity = trim(getPOST('identity'));

    if( '' == $company ) {
        show_system_message('公司名称不能为空', array());
        exit;
    }
    $company = $db->escape($company);

    if( '' == $desc ) {
        show_system_message('请输入公司简介，让会员了解您的公司', array());
        exit;
    }
    $desc = $db->escape($desc);

    $industry_id = intval($industry_id);
    if( 0 >= $industry_id ) {
        show_system_message('主营行业参数错误', array());
        exit;
    }

    $category_id = intval($category_id);
    if( 0 >= $category_id ) {
        show_system_message('主营分类参数错误', array());
        exit;
    }

    if( '' == $contact ) {
        show_system_message('负责人不能为空', array());
        exit;
    }
    $contact = $db->escape($contact);

    if( '' == $mobile ) {
        show_system_message('联系电话不能为空', array());
        exit;
    }
    $mobile = $db->escape($mobile);

    if( '' == $email ) {
        show_system_message('电子邮箱不能为空', array());
        exit;
    }
    $email = $db->escape($email);

    if( '' == $license ) {
        show_system_message('请选择营业执照', array());
        exit;
    }
    $license = $db->escape($license);

    if( '' == $identity ) {
        show_system_message('请选择法人身份证', array());
        exit;
    }
    $identity = $db->escape($identity);

    $check_email = 'select * from '.$db->table('business');
    $check_email .= ' where email = \''.$email.'\'';
    $check_email .= ' and business_account <> \''.$_SESSION['business_account'].'\'';
    $check_email .= ' limit 1';
    $email_exists = $db->fetchRow($check_email);
    if( $email_exists ) {
        show_system_message('邮箱已被使用', array());
        exit;
    }

    $check_mobile = 'select * from '.$db->table('business');
    $check_mobile .= ' where mobile = \''.$mobile.'\'';
    $check_mobile .= ' and business_account <> \''.$_SESSION['business_account'].'\'';
    $check_mobile .= ' limit 1';
    $mobile_exists = $db->fetchRow($check_mobile);
    if( $mobile_exists ) {
        show_system_message('号码已被使用', array());
        exit;
    }


    $data = array(
        'company' => $company,
        'desc' => $desc,
        'industry_id' => $industry_id,
        'category_id' => $category_id,
        'contact' => $contact,
        'mobile' => $mobile,
        'email' => $email,
        'license' => $license,
        'identity' => $identity,
        'add_time' => time(),
        'business_account' => $_SESSION['business_account'],
        'status' => 0,
    );

    if( $db->autoInsert('auth', array($data)) ) {
        show_system_message('您的申请已提交，请静候佳音。', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }


}

if( 'partner' == $opera ) {
    if( !check_purview('pur_business_auth', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $industry_id = trim(getPOST('industry'));
    $category_id = trim(getPOST('classification'));
    $contact = trim(getPOST('contact'));
    $mobile = trim(getPOST('mobile'));
    $email = trim(getPOST('email'));
    $license = trim(getPOST('license'));
    $identity = trim(getPOST('identity'));

    $industry_id = intval($industry_id);
    if( 0 >= $industry_id ) {
        show_system_message('主营行业参数错误', array());
        exit;
    }

    $category_id = intval($category_id);
    if( 0 >= $category_id ) {
        show_system_message('主营分类参数错误', array());
        exit;
    }

    if( '' == $contact ) {
        show_system_message('负责人不能为空', array());
        exit;
    }
    $contact = $db->escape($contact);

    if( '' == $mobile ) {
        show_system_message('联系电话不能为空', array());
        exit;
    }
    $mobile = $db->escape($mobile);

    if( '' == $email ) {
        show_system_message('电子邮箱不能为空', array());
        exit;
    }
    $email = $db->escape($email);

    if( '' == $license ) {
        show_system_message('请选择身份证正面', array());
        exit;
    }
    $license = $db->escape($license);

    if( '' == $identity ) {
        show_system_message('请选择身份证反面', array());
        exit;
    }
    $identity = $db->escape($identity);

    $check_email = 'select * from '.$db->table('business');
    $check_email .= ' where email = \''.$email.'\'';
    $check_email .= ' and business_account <> \''.$_SESSION['business_account'].'\'';
    $check_email .= ' limit 1';
    $email_exists = $db->fetchRow($check_email);
    if( $email_exists ) {
        show_system_message('邮箱已被使用', array());
        exit;
    }

    $check_mobile = 'select * from '.$db->table('business');
    $check_mobile .= ' where mobile = \''.$mobile.'\'';
    $check_mobile .= ' and business_account <> \''.$_SESSION['business_account'].'\'';
    $check_mobile .= ' limit 1';
    $mobile_exists = $db->fetchRow($check_mobile);
    if( $mobile_exists ) {
        show_system_message('号码已被使用', array());
        exit;
    }


    $data = array(
        'company' => '',
        'desc' => '',
        'industry_id' => $industry_id,
        'category_id' => $category_id,
        'contact' => $contact,
        'mobile' => $mobile,
        'email' => $email,
        'license' => $license,
        'identity' => $identity,
        'add_time' => time(),
        'business_account' => $_SESSION['business_account'],
        'status' => 0,
    );

    if( $db->autoInsert('auth', array($data)) ) {
        show_system_message('您的申请已提交，请静候佳音。', array());
        exit;
    } else {
        show_system_message('系统繁忙，请稍后重试', array());
        exit;
    }
}
//======================================================================

if( 'base' == $act ) {
    if( !check_purview('pur_business_base', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $get_business = 'select `business_account`, `shop_logo`, `shop_sign`,`pc_shop_sign`, `shop_name`, `address`, `province`, `city`, `district`, `group`, `longitude`, `latitude`';
    $get_business .= ' from '.$db->table('business');
    $get_business .= ' where `business_account` = \''.$_SESSION['business_account'].'\'';
    $get_business .= ' limit 1';
    $business = $db->fetchRow($get_business);
    $business['shop_logo_src'] = $business['shop_logo'];
    $business['shop_sign_src'] = !empty($business['shop_sign']) ? $business['shop_sign'] : '';
//    $business['pc_shop_sign_src'] = $business['pc_shop_sign'];

    if( !empty($business['longitude']) && !empty($business['latitude']) ) {
        $map_init = true;
    } else {
        $map_init = false;
    }

    assign('business', $business);

    $get_province_list = 'select * from '.$db->table('province').' where 1 order by id asc';
    $province_list = $db->fetchAll($get_province_list);

    $get_city_list = 'select * from '.$db->table('city').' where 1 order by id asc';
    $city_list = $db->fetchAll($get_city_list);

    $get_district_list = 'select * from '.$db->table('district').' where 1 order by id asc';
    $district_list = $db->fetchAll($get_district_list);

    $get_group_list = 'select * from '.$db->table('group').' where 1 order by id asc';
    $group_list = $db->fetchAll($get_group_list);

    //转换城市的结构
    $target_city_list = array();
    $count = count($city_list);
    for($i = 0; $i < $count; ) {
        $pid = $city_list[$i]['province_id'];
        $temp = array();
        do {
            $temp[] = $city_list[$i];
            $i++;
        } while($i < $count && $city_list[$i]['province_id'] == $pid);
        $target_city_list[$pid] = $temp;
    }
    //转换地区的结构
    $target_district_list = array();
    $count = count($district_list);
    for($i = 0; $i < $count; ) {
        $pid = $district_list[$i]['city_id'];
        $temp = array();
        do {
            $temp[] = $district_list[$i];
            $i++;
        } while($i < $count && $district_list[$i]['city_id'] == $pid);
        $target_district_list[$pid] = $temp;
    }

    //转换商圈的结构
    $target_group_list = array();
    $count = count($group_list);
    for( $i = 0; $i < $count; ) {
        $pid = $group_list[$i]['district_id'];
        $temp = array();
        do {
            $temp[] = $group_list[$i];
            $i++;
        } while( $i < $count && $group_list[$i]['district_id'] == $pid );
        $target_group_list[$pid] = $temp;
    }

    assign('province_list', $province_list);
    assign('city_list', $city_list);
    assign('district_list', $district_list);
    assign('group_list', $group_list);
    assign('json_cities', json_encode($target_city_list));
    assign('json_districts', json_encode($target_district_list));
    assign('json_groups', json_encode($target_group_list));
    assign('map_init', json_encode($map_init));
}

if( 'auth' == $act ) {
    if( !check_purview('pur_business_auth', $_SESSION['business_purview']) ) {
        show_system_message('权限不足', array());
        exit;
    }

    $check_auth = 'select * from '.$db->table('auth');
    $check_auth .= ' where `business_account` = \''.$_SESSION['business_account'].'\' and status = 0';
    $check_auth .= ' order by id desc limit 1';

    $check_auth = $db->fetchRow($check_auth);
    if( $check_auth ) {
        $edit_disable = true;
    } else {
        $edit_disable = false;
    }
    assign('edit_disable', $edit_disable);

    $get_business = 'select * from '.$db->table('business');
    $get_business .= ' where business_account = \''.$_SESSION['business_account'].'\'';
    $get_business .= ' limit 1';
    $business = $db->fetchRow($get_business);
    if( file_exists(realpath('../'.$business['license'])) ) {
        $business['license_src'] = '../'.$business['license'];
    } else {
        $business['license_src'] = $business['license'];
    }
    if( file_exists(realpath('../'.$business['identity'])) ) {
        $business['identity_src'] = '../'.$business['identity'];
    } else {
        $business['identity_src'] = $business['identity'];
    }

    assign('business', $business);

    $get_industry_list = 'select * from '.$db->table('industry');
    $get_industry_list .= ' where 1 order by id asc';
    $industry_list = $db->fetchAll($get_industry_list);
    assign('industry_list', $industry_list);

    $get_category_list = 'select * from '.$db->table('category');
    $get_category_list .= ' where business_account = \'\' and `parent_id`=0 order by id asc';
    $category_list = $db->fetchAll($get_category_list);
    assign('category_list', $category_list);
}
$template .= $act.'.phtml';
$smarty->display($template);
