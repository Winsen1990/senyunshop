<?php
/**
 * 首页
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/14
 * Time: 下午10:11
 */
include 'library/init.inc.php';

$operation = 'wechat|alipay';
$opera = check_action($operation, getPOST('opera'));

//支付方式变更时生成支付代码
if('wechat' == $opera)
{
    $amount = getPOST('amount');
    $amount = floatval($amount);

    $mch_id = '1259544101';//商户编号
    $mch_key = 'WinsenCeciliaWrhLtx2015KWANSONCC';//商户API密钥

    $response = array('error'=>1, 'msg'=>'');

    $_SESSION['payment'] = 'wechat';

    if($amount <= 0)
    {
        $response['msg'] = '充值金额必须大于0';
    } else {
        $total_fee = $amount;

        $recharge_sn = add_recharge($_SESSION['account'], $total_fee);
        if($recharge_sn) {
            $response['price'] = '￥' . sprintf('%.2f', $total_fee);
            $detail = '充值流水号:' . $recharge_sn;

            $body = $config['site_name'] . '充值';
            $body = $detail;
            $out_trade_no = $recharge_sn;

            $res = create_prepay($config['appid'], $mch_id, $mch_key, $_SESSION['openid'], $total_fee, $body, $detail, $out_trade_no);

            $res = simplexml_load_string($res);

            if ($res->prepay_id) {
                $response['error'] = 0;
            } else {
                $response['msg'] = $res->return_code . ',' . $res->return_msg;
            }

            $nonce_str = get_nonce_str();
            $response['nonce_str'] = $nonce_str;
            $time_stamp = time();

            //最后参与签名的参数有appId, timeStamp, nonceStr, package, signType。
            $sign = 'appId=' . $config['appid'] . '&nonceStr=' . $nonce_str . '&package=prepay_id=' . $res->prepay_id . '&signType=MD5&timeStamp=' . $time_stamp . '&key=' . $mch_key;
            $sign = md5($sign);
            $sign = strtoupper($sign);
            $response['timestamp'] = $time_stamp;
            $response['sign'] = $sign;
            $response['prepay_id'] = "" . $res->prepay_id;
        } else {
            $response['msg'] = '系统繁忙，请稍后再试';
        }
    }

    echo json_encode($response);
    exit;
}

//获取用户信息
$get_user_info = 'select * from '.$db->table('member').' where `account`=\''.$_SESSION['account'].'\'';
$user_info = $db->fetchRow($get_user_info);
assign('user_info', $user_info);

$smarty->display('recharge.phtml');