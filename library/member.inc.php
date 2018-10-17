<?php
/**
 * 会员操作封装
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:12
 */

/**
 * 新增我的足迹
 * @param $account
 * @param $product_sn
 */
function add_history($account, $product_sn)
{
    global $db;

    $check_history_count = 'select count(*) from '.$db->table('history').' where `account`=\''.$account.'\'';

    $history_count = $db->fetchOne($check_history_count);

    if($history_count > 15)
    {
        $delete_history = 'delete from '.$db->table('history').' order by `add_time` ASC limit 1';

        $db->delete($delete_history);
    }

    $check_history = 'select `product_sn` from '.$db->table('history').' where `product_sn`=\''.$product_sn.'\' and `account`=\''.$account.'\'';

    if(!$db->fetchOne($check_history))
    {
        $history_data = array(
            'account' => $account,
            'product_sn' => $product_sn
        );

        return $db->autoInsert('history', array($history_data));
    } else {

    }
}
/**
 * 会员奖金记录
 * @param string $account
 * @param float $reward
 * @param float $integral
 * @param string $assoc
 * @param string $remark
 * @return bool|resource
 */
function  add_member_reward($account, $reward, $integral, $assoc, $remark = '')
{
    global $db;

    $reward_data = array(
        'account' => $account,
        'reward' => $reward,
        'integral' => $integral,
        'remark' => $remark,
        'settle_time' => time(),
        'assoc' => $assoc,
        'status' => 0
    );

    return $db->autoInsert('member_reward', array($reward_data));
}
 /**
 * 账户明细
 * @param string $account
 * @param float $balance
 * @param float $reward
 * @param float $integral
 * @param float $integral_await
 * @param float $reward_await
 * @param string $operator
 * @param string $remark
 * @return bool
 */
 function add_memeber_exchange_log($account, $balance, $reward, $integral, $integral_await, $reward_await, $operator, $remark = '')
 {
    global $db;

    $exchange_data = array(
        'account' => $account,
        'balance' => $balance,
        'reward' => $reward,
        'integral' => $integral,
        'integral_await' => $integral_await,
        'reward_await' => $reward_await,
        'operator' => $operator,
        'remark' => $remark,
        'add_time' => time()
    );

    $db->begin();

    //更新用户账户
    $update_user = 'update '.$db->table('member').' set `balance`=`balance`+'.$balance.',`reward`=`reward`+'.$reward.','.
                   '`integral`=`integral`+'.$integral.',`integral_await`=`integral_await`+'.$integral_await.','.
                   '`reward_await`=`reward_await`+'.$reward_await.' where `account`=\''.$account.'\'';
    if($db->update($update_user))
    {
        if($db->autoInsert('member_exchange_log', array($exchange_data)))
        {
            $db->commit();
            return true;
        }
    }

    $db->rollback();
    return false;
 }

/**
 * 修改收货地址
 * @param int $province
 * @param int $city
 * @param int $district
 * @param int $group
 * @param string $address
 * @param string $consignee
 * @param string $mobile
 * @param string $zipcode
 * @param string $account
 * @param bool $is_default
 * @param int $id
 * @return bool
 */
 function edit_address($province, $city, $district, $group, $address, $consignee, $mobile, $zipcode, $account, $is_default, $id)
 {
    global $db;

    $address_data = array(
        'province' => $province,
        'city' => $city,
        'district' => $district,
        'group' => $group,
        'address' => $address,
        'consignee' => $consignee,
        'mobile' => $mobile,
        'zipcode' => $zipcode,
        'is_default' => $is_default
    );

    return $db->autoUpdate('address', $address_data, '`id`='.$id.' and `account`=\''.$account.'\'');
 }

/**
 * 新增收货地址
 * @param int $province
 * @param int $city
 * @param int $district
 * @param int $group
 * @param string $address
 * @param string $consignee
 * @param string $mobile
 * @param string $zipcode
 * @param string $account
 * @param bool $is_default
 * @return int
 */
 function add_address($province, $city, $district, $group, $address, $consignee, $mobile, $zipcode, $account, $is_default = true)
 {
    global $db;

    $address_data = array(
        'province' => $province,
        'city' => $city,
        'district' => $district,
        'group' => $group,
        'address' => $address,
        'consignee' => $consignee,
        'mobile' => $mobile,
        'zipcode' => $zipcode,
        'account' => $account,
        'is_default' => $is_default
    );

    if($db->autoInsert('address', array($address_data)))
    {
        return $db->get_last_id();
    } else {
        return false;
    }
 }
/**
 * 取消分销产品
 * @param string $account
 * @param string $product_sn
 * @return bool
 */
 function cancel_distribution_product($account, $product_sn)
 {
    global $db;

    return $db->autoDelete('distribution', '`account`=\''.$account.'\' and `product_sn`=\''.$product_sn.'\'');
 }

 /**
 * 分销产品
 * @param string $account
 * @param string $product_sn
 * @return bool
 */
 function distribution_product($account, $product_sn)
 {
    global $db;
    global $log;

    //检查产品是否已分销
    $check_distribution = 'select `account` from '.$db->table('distribution').' where `account`=\''.$account.'\' and `product_sn`=\''.$product_sn.'\'';
    if($db->fetchOne($check_distribution))
    {
        return true;
    } else {
        $distribution_data = array(
            'account' => $account,
            'product_sn' => $product_sn,
            'add_time' => time()
        );

        $log->record_array($distribution_data);
        return $db->autoInsert('distribution', array($distribution_data));
    }
 }

 /**
 * 取消收藏产品
 * @param string $account
 * @param string $product_sn
 * @return bool
 */
 function cancel_collection_product($account, $product_sn)
 {
    global $db;

    return $db->autoDelete('collection', '`account`=\''.$account.'\' and `product_sn`=\''.$product_sn.'\'');
 }

 /**
 * 收藏产品
 * @param string $account
 * @param string $product_sn
 * @return bool
 */
 function collection_product($account, $product_sn)
 {
    global $db;
    global $log;

    //检查产品是否已收藏
    $check_collection = 'select `account` from '.$db->table('collection').' where `account`=\''.$account.'\' and `product_sn`=\''.$product_sn.'\'';
    if($db->fetchOne($check_collection))
    {
        return true;
    } else {
        $collection_data = array(
            'account' => $account,
            'product_sn' => $product_sn,
            'add_time' => time()
        );

        $log->record_array($collection_data);

        return $db->autoInsert('collection', array($collection_data));
    }
 }

 /**
 * 同步微信信息
 * @param string $openid
 * @param string $code
 * @return bool
 */
 function async_member_info($openid, $code)
 {
    global $db;
    global $config;
    global $log;

    $user_info = get_user_info($code, $config['appid'], $config['appsecret'], 'userinfo');

    if($user_info)
    {
        $member_data = array(
            'sex' => $user_info->sex,
            'nickname' => $user_info->nickname,
            'province' => $user_info->province,
            'city' => $user_info->city,
            'headimg' => $user_info->headimg,
            'unionid' => $user_info->unionid
        );

        $log->record_array($member_data);
        if($db->autoUpdate('member', $member_data, '`openid`=\''.$openid.'\''))
        {
            return true;
        } else {
            return false;
        }
    }
 }

/**
 * 注册会员信息
 * @param string $mobile
 * @param string $password
 * @param int $parent_id
 * @return string 会员账号
 */
function register_mobile($mobile, $password, $parent_id = 0)
{
    global $db;
    global $log;

    $db->begin();
    $member_data = array(
        'account' => get_account(),
        'mobile' => $mobile,
        'password' => $password,
        'add_time' => time(),
        'parent_id' => $parent_id,
        'nickname' => $mobile
    );

    if($db->autoInsert('member', array($member_data)))
    {
        $path = '';
        $id = $db->get_last_id();
        if($parent_id)
        {
            $get_parent_path = 'select `path` from '.$db->table('member').' where `id`='.$parent_id;
            $path = $db->fetchOne($get_parent_path);
        }

        $path .= $id.',';

        $update_data = array(
            'path' => $path
        );

        if($db->autoUpdate('member', $update_data, '`id`='.$id))
        {
            $log->record_array($member_data);
            $db->commit();
            return $member_data['account'];
        }
    }

    $db->rollback();
    return false;
}

 /**
 * 注册会员信息
 * @param string $openid
 * @return string 会员账号
 */
 function register_member($openid, $parent_id = 0)
 {
    global $db;
    global $log;

    $db->begin();
    $member_data = array(
        'account' => get_account(),
        'openid' => $openid,
        'add_time' => time(),
        'parent_id' => $parent_id,
        'sex' => 'N'
    );

    if($db->autoInsert('member', array($member_data)))
    {
        $path = '';
        $id = $db->get_last_id();
        $kf_id = 0;

        if($parent_id)
        {
            $get_parent_path = 'select `path` from '.$db->table('member').' where `id`='.$parent_id;
            $path = $db->fetchOne($get_parent_path);

            $get_kf_id = 'select `kf_id` from '.$db->table('member').' where `id`='.$parent_id;
            $kf_id = $db->fetchOne($get_kf_id);
        } else {
            $get_kf_id = 'select `kf_id`,count(*) as c from '.$db->table('member').' group by `kf_id` order by c';
            $kf_id = $db->fetchOne($get_kf_id);
        }

        $path .= $id.',';

        $update_data = array(
            'path' => $path,
            'kf_id' => $kf_id
        );

        if($db->autoUpdate('member', $update_data, '`id`='.$id))
        {
            $log->record_array($member_data);
            $db->commit();
            return $member_data['account'];
        }
    }

    $db->rollback();
    return false;
 }

 /**
 * 获取会员账号
 * @param int $step 账号池增长的步长，实际账号池增长速度为2*$step
 * @return string 会员账号
 */
 function get_account($step = 500)
 {
    global $db;
    global $log;

    $db->begin();

    $get_account = 'select `account` from '.$db->table('card_pool').' where `status`=1 order by `id` ASC';
    $log->record('get account:'.$get_account);
    $account = $db->fetchOne($get_account);

    //检查剩余的可用账号如果少于step则扩充账号池
    $get_account_count = 'select count(*) from '.$db->table('card_pool').' where `status`=1 for update';
    $account_left = $db->fetchOne($get_account_count);
    if($account_left < $step)
    {
        $get_max_account = 'select `account` from '.$db->table('card_pool').' order by `account` DESC limit 1';
        $max_account = $db->fetchOne($get_max_account);

        $account_prefix = substr($max_account, 0, 2);
        $max = str_replace($account_prefix, '', $max_account);
        $max = intval($max);

        $account_arr = range($max, $max + $step);
        shuffle($account_arr);

        $data = array();
        foreach($account_arr as $account_tail)
        {
            $c = strlen($account_tail);
            while($c < 10)
            {
                $account_tail = '0'.$account_tail;
                $c++;
            }

            $data[] = array('account'=>$account_prefix.$account_tail);
        }
        $log->record_array($data);
        $db->autoInsert('card_pool', $data);
    }
    $status = array('status'=>0);
    $db->autoUpdate('card_pool', $status, '`account`=\''.$account.'\'');
    $db->commit();

    return $account;
 }

/**
 * 发放推广积分
 * @param $account
 * @param $integral
 * @param $remark
 * @return bool
 */
function add_recommend_integral($account, $integral, $remark = '', $operator = 'settle')
{
    return add_memeber_exchange_log($account, 0, 0, $integral, 0,0, $operator, $remark);
}

/**
 * 验证密码
 */
function verify_password($account, $password)
{
    global $db;

    $get_user_password = 'select `password` from '.$db->table('member').' where `account`=\''.$account.'\'';
    $user_password = $db->fetchOne($get_user_password);

    $password = md5($password.PASSWORD_END);

    return $password == $user_password;
}

/**
 * 新增提现记录
 */
function add_withdraw($account, $amount, $bank_id, $remark = '')
{
    global $db;
    global $config;

    $withdraw_fee_rate = array(
        80000 => 0.45,
        55000 => 0.35,
        35000 => 0.30,
        9000 => 0.25,
        4500 => 0.20,
        1500 => 0.10,
        0 => 0.03
    );

    $fee = 0;
    $_amount_tmp = $amount;

    foreach($withdraw_fee_rate as $min_amount => $fee_rate) {
        if($amount > $min_amount) {
            $fee += ($_amount_tmp - $min_amount) * $fee_rate;

            $_amount_tmp = $min_amount;
        }
    }

    $withdraw_sn = '';

    $withdraw_data = array(
        'account' => $account,
        'amount' => $amount,
        'fee' => $fee,
        'status' => 0,
        'remark' => $remark,
        'add_time' => time()
    );

    $get_bank_info = 'select `bank`,`bank_account`,`bank_card` from '.$db->table('bank_card').' where `id`='.$bank_id;
    $bank_info = $db->fetchRow($get_bank_info);

    if($bank_info)
    {
        $withdraw_data = array_merge($withdraw_data, $bank_info);
    } else {
        return false;
    }

    do
    {
        $withdraw_sn = 'W'.time().rand(100, 999);
        $check_withdraw = 'select `withdraw_sn` from '.$db->table('withdraw').' where `withdraw_sn`=\''.$withdraw_sn.'\'';
    } while($db->fetchOne($check_withdraw));

    $withdraw_data['withdraw_sn'] = $withdraw_sn;

    if($db->autoInsert('withdraw', array($withdraw_data)))
    {
        return $withdraw_sn;
    } else {
        return false;
    }
}