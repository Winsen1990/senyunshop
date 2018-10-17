<?php
/**
 * 合伙人交费
 * @author 王仁欢
 * @email wrh4285@163.com
 * @date 2015-11-04
 * @version 1.0.0
 */
include 'library/init.inc.php';

$operation = 'wechat|alipay|wechat_pay';
$opera = check_action($operation, getPOST('opera'));
$action = 'pay|apply';
$act = check_action($action, getGET('act'), 'apply');

//找人代付
if('pay' == $act)
{
    $account = trim(getGET('account'));

    if($account == '')
    {
        echo '参数错误';
        exit;
    }

    $account = $db->escape($account);

    //是否已是合伙人
    $get_level_id = 'select `level_id` from '.$db->table('member').' where account = \''.$account.'\' limit 1';
    $level_id = $db->fetchOne($get_level_id);

    if($level_id)
    {
        echo $account.'已成为合伙人';
        exit;
    }

    $mch_id = '1269390401';
    $mch_key = 'CeciliaZhengWinsenPengwrhltx2015';

    $_SESSION['payment'] = 'wechat';


    $total_fee = sprintf('%.2f', $config['partner_fee']);
    $detail = $account.'-合伙人费用';

    $response['price'] = '￥' . sprintf('%.2f', $total_fee);
    $body = $config['site_name'] . '订单收款';
    $body = $detail;
    $out_trade_no = $account.'-'.time();

    $res = create_prepay($config['appid'], $mch_id, $mch_key, $_SESSION['openid'], $total_fee, $body, $detail, $out_trade_no);

    $res = simplexml_load_string($res);

    if($res->prepay_id)
    {
        $response['error'] = 0;
    } else {
        $response['msg'] = $res->return_code.','.$res->return_msg;
    }

    $nonce_str = get_nonce_str();
    $response['nonce_str'] = $nonce_str;
    $time_stamp = time();

    //最后参与签名的参数有appId, timeStamp, nonceStr, package, signType。
    $sign = 'appId='.$config['appid'].'&nonceStr='.$nonce_str.'&package=prepay_id='.$res->prepay_id.'&signType=MD5&timeStamp='.$time_stamp.'&key='.$mch_key;
    $sign = md5($sign);
    $sign = strtoupper($sign);
    $response['timestamp'] = $time_stamp;
    $response['account'] = $account;
    $response['sign'] = $sign;
    $response['prepay_id'] = "".$res->prepay_id;
    assign('payment', $response);
}

//支付方式变更时生成支付代码
if('wechat_pay' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    echo json_encode($response);
    exit;
}

if('wechat' == $opera)
{
    $mch_id = '1269390401';
    $mch_key = 'CeciliaZhengWinsenPengwrhltx2015';

    $response = array('error'=>1, 'msg'=>'');

    $_SESSION['payment'] = 'wechat';


    $total_fee = sprintf('%.2f', $config['partner_fee']);
    $detail = $_SESSION['account'].'-合伙人费用';

    $response['price'] = '￥' . sprintf('%.2f', $total_fee);
    $body = $config['site_name'] . '订单收款';
    $body = $detail;
    $out_trade_no = $_SESSION['account'].'-'.time();

    $res = create_prepay($config['appid'], $mch_id, $mch_key, $_SESSION['openid'], $total_fee, $body, $detail, $out_trade_no);

    $res = simplexml_load_string($res);

    if($res->prepay_id)
    {
        $response['error'] = 0;
    } else {
        $response['msg'] = $res->return_code.','.$res->return_msg;
    }

    $nonce_str = get_nonce_str();
    $response['nonce_str'] = $nonce_str;
    $time_stamp = time();

    //最后参与签名的参数有appId, timeStamp, nonceStr, package, signType。
    $sign = 'appId='.$config['appid'].'&nonceStr='.$nonce_str.'&package=prepay_id='.$res->prepay_id.'&signType=MD5&timeStamp='.$time_stamp.'&key='.$mch_key;
    $sign = md5($sign);
    $sign = strtoupper($sign);
    $response['timestamp'] = $time_stamp;
    $response['sign'] = $sign;
    $response['prepay_id'] = "".$res->prepay_id;

    echo json_encode($response);
    exit;
}

if('apply' == $act)
{
    //是否已是合伙人
    $get_level_id = 'select `level_id` from ' . $db->table('member') . ' where account = \'' . $_SESSION['account'] . '\' limit 1';
    $level_id = $db->fetchOne($get_level_id);
    assign('level', $level_id);

    $get_payment_list = 'select * from ' . $db->table('payment') . ' where status = 1';
    $payment_list = $db->fetchAll($get_payment_list);
    assign('payment_list', $payment_list);
    assign('account', $_SESSION['account']);
}

assign('act', $act);
$smarty->display('copartner.phtml');








