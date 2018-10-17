<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 下午4:46
 */
include 'library/init.inc.php';

$operation = 'add|delete|cancel';
$action = 'list|opera|detail';
$act = check_action($action, getGET('act'));
$opera = check_action($operation, getPOST('opera'));

$template = 'withdrawals.phtml';

$get_user_info = 'select `balance`,`reward`,`password`,`level_id` from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';

$user_info = $db->fetchRow($get_user_info);

//获取未提现的金额
$get_withdraw_await = 'select sum(`amount`+`fee`) from '.$db->table('withdraw').' where `account`=\''.$_SESSION['account'].'\'';
$withdraw_await = $db->fetchOne($get_withdraw_await);

//$withdraw_await = $user_info['balance'] + $user_info['reward'] - $withdraw_await;
$withdraw_await = $user_info['reward'] - $withdraw_await;

$withdraw_fee_rate = array(
    80000 => 0.45,
    55000 => 0.35,
    35000 => 0.30,
    9000 => 0.25,
    4500 => 0.20,
    1500 => 0.10,
    0 => 0.03
);

assign('withdraw_fee_rate', $withdraw_fee_rate);

if('cancel' == $opera)
{
    $response = array('error'=>0, 'msg'=>'');

    $withdraw_sn = getPOST('withdraw_sn');

    if($withdraw_sn == '')
    {
        $response['msg'] = '000:参数错误';
    } else {
        $withdraw_sn = $db->escape($withdraw_sn);
    }

    if($response['msg'] == '')
    {
        $db->begin();
        $check_withdraw = 'select `withdraw_sn` from '.$db->table('withdraw').' where `account`=\''.$_SESSION['account'].'\' and '.
                          ' `withdraw_sn`=\''.$withdraw_sn.'\' and `status`=0 for update;';

        if($db->fetchOne($check_withdraw))
        {
            $db->autoDelete('withdraw', '`withdraw_sn`=\''.$withdraw_sn.'\'');
            $response['error'] = 0;
            $response['msg'] = '取消申请成功';
        } else {
            $response['msg'] = '该申请已处理或不存在';
        }

        $db->commit();
    }

    echo json_encode($response);
    exit;
}

if('add' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    if(!check_cross_domain())
    {
        $bank_id = intval(getPOST('bank_id'));
        $amount = floatval(getPOST('amount'));
        $password = getPOST('password');

        if($bank_id <= 0)
        {
            $response['msg'] .= '-请选择银行卡<br/>';
        } else {
            $check_bank_info = 'select `account` from '.$db->table('bank_card').' where `id`='.$bank_id;

            $card_account = $db->fetchOne($check_bank_info);

            if($card_account != $_SESSION['account'])
            {
                $response['msg'] .= '-参数错误<br/>';
            }
        }

        if($amount <= 0)
        {
            $response['msg'] .= '-请填写提现金额<br/>';
        } else {
            $fee = 0;
            $_amount_tmp = $amount;

            foreach($withdraw_fee_rate as $min_amount => $fee_rate) {
                if($amount > $min_amount) {
                    $fee += ($_amount_tmp - $min_amount) * $fee_rate;

                    $_amount_tmp = $min_amount;
                }
            }
            $total_amount = $amount + $fee;

            if($withdraw_await < $total_amount)
            {
                $response['msg'] .= '-可提现金额不足<br/>';
            }
        }

        if($password == '')
        {
            $response['msg'] .= '-请填写账户密码<br/>';
        } else {
            if(!verify_password($_SESSION['account'], $password))
            {
                $response['msg'] .= '-账户密码错误<br/>';
            }
        }

        if($response['msg'] == '')
        {
            if($withdraw_sn = add_withdraw($_SESSION['account'], $amount, $bank_id))
            {
                $response['error'] = 0;
                $response['msg'] = '提现申请提交成功';
                $response['withdraw_sn'] = $withdraw_sn;
            } else {
                $response['msg'] = '001:系统繁忙，请稍后再试';
            }
        }
    } else {
        $response['msg'] = '404:参数错误';
    }

    echo json_encode($response);
    exit;
}

if($act == '')
{
    $act = 'opera';
}

if('opera' == $act)
{
    //检查密码是否已设置
    if($user_info['password'] == '')
    {
        redirect('bind_mobile.php');
    }
    assign('withdraw_await', $withdraw_await);

    //检查是否是合伙人身份
    if($user_info['level_id'] < 1)
    {
        assign('no_privicy', true);
    } else {
        assign('no_privicy', false);
    }

    //获取用户银行卡
    $get_bank_list = 'select `id`,`bank`,`bank_account`,`bank_card` from '.$db->table('bank_card').' where `account`=\''.$_SESSION['account'].'\'';
    $bank_list = $db->fetchAll($get_bank_list);
    if($bank_list)
    {
        foreach ($bank_list as $key => $bank)
        {
            $bank_list[$key]['show_name'] = $bank['bank'] . '(尾号' . substr($bank['bank_card'], -4) . ')';
        }
    }

    assign('bank_list', $bank_list);
}

if('list' == $act)
{
    $template = 'withdraw_list.phtml';

    $get_withdraw_list = 'select `withdraw_sn`,`amount`,`fee`,`status`,`bank`,`bank_account`,`bank_card`,`add_time`,`solve_time` from '.
                         $db->table('withdraw').' where `account`=\''.$_SESSION['account'].'\' order by `add_time` DESC';
    $withdraw_list = $db->fetchAll($get_withdraw_list);

    assign('withdraw_list', $withdraw_list);
}

$smarty->display($template);