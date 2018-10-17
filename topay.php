<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 15/9/15
 * Time: 上午8:49
 */
include 'library/init.inc.php';

global $config, $db, $loader, $log;

$order_sn = getGET('sn');
$payment = intval(getGET('payment'));

if($payment == 2) {
    $payment = 'alipay';
} else {
    $payment = 'wechat';
}

$operation = 'wechat|alipay';
$opera = check_action($operation, getPOST('opera'));

//支付方式变更时生成支付代码
if('wechat' == $opera)
{
    $mch_id = $config['mch_id'];
    $mch_key = $config['mch_key'];

    $response = array('error'=>1, 'msg'=>'');

    $_SESSION['payment'] = 'wechat';

    $order_sn = $_SESSION['order_sn'];

    $get_order_info = 'select * from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';

    $order = $db->fetchRow($get_order_info);

    $total_fee = $order['amount'];
    $detail = '订单:'.$order_sn;

    $response['price'] = '￥'.sprintf('%.2f', $total_fee);

    $body = $config['site_name'].'订单收款';
    $body = $detail;
    $out_trade_no = $order_sn;

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
    if(isset($res->mweb_url)) {
        $response['mweb_url'] = $res->mweb_url.'&redirect_url=http%3A%2F%2F'.$_SERVER['HTTP_HOST'].'/payresult.php';
    }

    echo json_encode($response);
    exit;
}

if('alipay' == $opera)
{
    $response = array('error'=>1, 'msg'=>'');

    $_SESSION['payment'] = 'alipay';

    $order_sn = $_SESSION['order_sn'];

    $get_order_info = 'select * from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';

    $order = $db->fetchRow($get_order_info);

    $loader = AutoLoader::getInstance();
    $loader->includeClass('Alipay');

    $alipay = new Alipay();

    /**************************业务参数**************************/
    $biz_content = array(
        'subject' => '订单'.$order['order_sn'],
        'out_trade_no' => $order['order_sn'],
        'total_amount' => $order['amount'],
        'product_code' => 'QUICK_WAP_WAY'
    );

    $biz_content = json_encode($biz_content);
    /**************************请求参数**************************/
    $gateway = 'https://openapi.alipaydev.com/gateway.do';

    //服务器异步通知页面路径
    $notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/notify_url.php';
    //需http://格式的完整路径，不能加?id=123这类自定义参数

    //页面跳转同步通知页面路径
    $return_url = 'http://'.$_SERVER['HTTP_HOST'].'/payresult.php';
    //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

    $parameter = array(
        'app_id' => $config['alipay_app_id'],
        'method' => 'alipay.trade.wap.pay',
        'format' => 'JSON',
        'return_url' => $return_url,
        'charset' => 'utf-8',
        'sign_type' => 'RSA2',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0',
        'notify_url' => $notify_url,
        'biz_content' => $biz_content
    );

    ksort($parameter);
    $param_str = '';
    foreach($parameter as $key => $value) {
        if(empty($value)) {
            continue;
        }

        if($param_str != '') {
            $param_str .= '&';
        }

        $param_str .= $key.'='.$value;
    }

    $response['sign_str'] = $param_str;
    $response['sign'] = $alipay->sign($param_str, $config['alipay_private_key']);
    $parameter['sign'] = $response['sign'];

    $query = http_build_query($parameter);

    $submit_form = '<form method="post" action="'.$gateway.'?'.$query.'" id="alipay_submit_form">';
    foreach($parameter as $key => $value) {
        $submit_form .= '<input type="hidden" name="'.$key.'" value=\''.$value.'\'/>';
    }

    $response['form'] = $submit_form;
    $response['error'] = 0;

    echo json_encode($response);
    exit;
}

$get_order_info = 'select * from '.$db->table('order').' where `order_sn`=\''.$order_sn.'\'';

$order = $db->fetchRow($get_order_info);
if($order) {
    $_SESSION['order_sn'] = $order['order_sn'];

    if($order['status'] > 1) {
        redirect('payresult.php');
    }
}

assign('order', $order);

$get_order_detail = 'select * from '.$db->table('order_detail').' where `order_sn`=\''.$order_sn.'\'';
$order_detail = $db->fetchAll($get_order_detail);
assign('order_detail', $order_detail);

//获取平台支付方式
$get_payment_list = 'select `id`,`name`,`plugins` from '.$db->table('payment').' where `status`=1';
$payment_list = $db->fetchAll($get_payment_list);

if(count($payment_list) == 1) {
    $payment = $payment_list[0]['plugins'];
} else {

}

switch($payment) {
    case 'alipay':
        $loader = AutoLoader::getInstance();
        $loader->includeClass('Alipay');

        $alipay = new Alipay();

        /**************************业务参数**************************/
        $biz_content = array(
            'subject' => '订单'.$order['order_sn'],
            'out_trade_no' => $order['order_sn'],
            'total_amount' => $order['amount'],
            'product_code' => 'QUICK_WAP_WAY'
        );

        $biz_content = json_encode($biz_content);
        /**************************请求参数**************************/
        $gateway = 'https://openapi.alipaydev.com/gateway.do';

        //服务器异步通知页面路径
        $notify_url = 'http://'.$_SERVER['HTTP_HOST'].'/notify_url.php';
        //需http://格式的完整路径，不能加?id=123这类自定义参数

        //页面跳转同步通知页面路径
        $return_url = 'http://'.$_SERVER['HTTP_HOST'].'/payresult.php';
        //需http://格式的完整路径，不能加?id=123这类自定义参数，不能写成http://localhost/

        $parameter = array(
            'app_id' => $config['alipay_app_id'],
            'method' => 'alipay.trade.wap.pay',
            'format' => 'JSON',
            'return_url' => $return_url,
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'notify_url' => $notify_url,
            'biz_content' => $biz_content
        );

        ksort($parameter);
        $param_str = '';
        foreach($parameter as $key => $value) {
            if(empty($value)) {
                continue;
            }

            if($param_str != '') {
                $param_str .= '&';
            }

            $param_str .= $key.'='.$value;
        }

        $response['sign_str'] = $param_str;
        $response['sign'] = $alipay->sign($param_str, $config['alipay_private_key']);
        $parameter['sign'] = $response['sign'];

        $submit_form = '<form method="post" action="'.$gateway.'" id="alipay_submit_form">';
        foreach($parameter as $key => $value) {
            $submit_form .= '<input type="hidden" name="'.$key.'" value=\''.$value.'\'/>';
        }

        assign('payment_exec', $submit_form);
        break;

    case 'wechat':
        $mch_id = $config['mch_id'];
        $mch_key = $config['mch_key'];

        $total_fee = $order['amount'];
        $detail = '订单:'.$order_sn;

        $response['price'] = '￥'.sprintf('%.2f', $total_fee);

        $body = $config['site_name'].'订单收款';
        $body = $detail;
        $out_trade_no = $order_sn;
        $openid = isset($_SESSION['openid']) ? $_SESSION['openid'] : '';

        $res = create_prepay($config['appid'], $mch_id, $mch_key, $openid, $total_fee, $body, $detail, $out_trade_no);

        $res = simplexml_load_string($res);

        $nonce_str = get_nonce_str();
        $response['nonce_str'] = $nonce_str;
        $time_stamp = time();

        //最后参与签名的参数有appId, timeStamp, nonceStr, package, signType。
        $sign = 'appId='.$config['appid'].'&nonceStr='.$nonce_str.'&package=prepay_id='.$res->prepay_id.'&signType=MD5&timeStamp='.$time_stamp.'&key='.$mch_key;
        $sign = md5($sign);
        $sign = strtoupper($sign);
        $response['timestamp'] = $time_stamp;
        $response['sign'] = $sign;
        $response['prepay_id'] = isset($res->prepay_id) ? $res->prepay_id : '';
        if(isset($res->mweb_url)) {
            $response['mweb_url'] = $res->mweb_url.'&redirect_url=http%3A%2F%2F'.$_SERVER['HTTP_HOST'].'/payresult.php';
        }

        assign('payment_exec', $response);
        break;
}

assign('payment', $payment);
assign('payment_list', $payment_list);
$smarty->display('topay.phtml');
