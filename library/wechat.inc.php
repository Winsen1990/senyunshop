<?php
/**
 * 微信操作
 * Created by PhpStorm.
 * User: apple
 * Date: 15/8/15
 * Time: 上午1:07
 */

/**
 * 判断数据源是否是微信
 * @return bool
 * @author winsen
 * @date 2014-10-24
 */
function is_weixin()
{
    if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false)
    {
        return true;
	}
	return false;
}

/*
 * 非授权的方式获取用户信息
 */
function get_user_wechat_info($access_token, $openid)
{
    global $db, $log;

    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN';
    $url = sprintf($url, $access_token, $openid);

    $user_info = get($url);
    $log->record($user_info);

    $user_info = json_decode($user_info);

    if($user_info)
    {
        if($user_info->subscribe == 0) {
            $db->autoUpdate('member', array('is_subscribe' => 0), '`openid`=\'' . $openid . '\'');
        }

        if(isset($user_info->nickname)) {
            $db->query('set names utf8mb4');
            $data = array(
                'nickname' => $user_info->nickname,
                'headimg' => $user_info->headimgurl,
                'is_subscribe' => 1
            );

            if (isset($user_info->unionid)) {
                $data['unionid'] = $user_info->unionid;
            }

            $db->autoUpdate('member', $data, '`openid`=\'' . $openid . '\'');
            $db->query('set names utf8');
        }
    }

    return $user_info;
}

 //获取用户信息
 /**
  * @param string $code
  * @param string $appid
  * @param string $appsecret
  * @param string $mode
  * @return mixed
  * @author winsen
  * @date 2015-03-30
  */
function get_user_info($code, $appid, $appsecret, $mode = 'base')
{
    //获取access_token
    $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code';
    $url = sprintf($url, $appid, $appsecret, $code);

    $response = get($url);
    global $log;
    $log->record($response);
    $response = json_decode($response);

    if(isset($response->errcode))
    {
        echo $response->errcode.':'.$response->errmsg;
        return false;
    } else {
        switch($mode)
        {
        case 'base':
            return $response;
        case 'userinfo':
            //获取用户信息
            $url = 'https://api.weixin.qq.com/sns/userinfo?access_token=%s&openid=%s&lang=zh_CN';
            $url = sprintf($url, $response->access_token, $response->openid);
            $response = get($url);
            $response = json_decode($response);
            if(!isset($response->error))
            {
                return $response;
            } else {
                return false;
            }
        }
    }
}

/**
 * 获得小程序登录的openid
 * @param $code
 * @param $appid
 * @param $appsecret
 * @return mixed
 */
function get_openid($code, $appid, $appsecret) {
    $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$code.'&grant_type=authorization_code';

    $response = get($url);

    global $log;
    $log->record('get mimi program grant '.$response);
    return json_decode($response, true);
}

/**
 * 获取用户永久推广二维码
 */
function get_forever_qrcode($openid, $access_token)
{
    global $db;
    global $log;

    $get_ticket = 'select `ticket` from '.$db->table('member').' where `openid`=\''.$openid.'\' and `expired`=-1';
    $ticket = $db->fetchOne($get_ticket);

    if($ticket)
    {
        $qrcode = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
        $log->record('ticket is avaiable.');
        return $qrcode;
    }

    $update_user = 'update '.$db->table('member').' set `scene_id`=0 where `expired`>0 and `expired`<'.time();
    $db->update($update_user);

    $scene_arr = range(1, 100000);

    $scene_id = 0;
    foreach($scene_arr as $id)
    {
        $check_scene_id = 'select count(*) from '.$db->table('member').' where `scene_id`='.$id.' and (`expired`=-1 or `expired`>'.time().')';
        $log->record($check_scene_id);

        if(!$db->fetchOne($check_scene_id))
        {
            $db->autoUpdate('member', array('scene_id' => $scene_id), '`openid`=\''.$openid.'\'');
            $scene_id = $id;
            break;
        }
    }
    //scene_id已满
    if($scene_id == 0)
    {
        return false;
    }
    //临时二维码申请
    $data = '{"action_name": "QR_LIMIT_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
    $response = post('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token, $data, false);

    $response = json_decode($response);

    if(isset($response->errcode))
    {
        $log->record('get qrcode fail:'.$response->errcode.':'.$response->errmsg);
        $sysconf_data = array(
            'value' => 0
        );
        $db->autoUpdate('sysconf', $sysconf_data, '`key`=\'expired\'');
        return false;
    } else {
        $data = array(
            'scene_id' => $scene_id,
            'ticket' => $response->ticket,
            'expired' => -1
        );

        $db->autoUpdate('member', $data, '`openid`=\''.$openid.'\'');
        $qrcode = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$response->ticket;
        return $qrcode;
    }
}

/**
 * 获取用户临时推广二维码
 */
function get_qrcode($openid, $access_token)
{
    global $db;
    global $log;

    $get_ticket = 'select `ticket` from '.$db->table('member').' where `openid`=\''.$openid.'\' and (`expired`=-1 or `expired`>'.time().')';
    $ticket = $db->fetchOne($get_ticket);

    if($ticket)
    {
        $qrcode = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$ticket;
        $log->record('ticket is avaiable.');
        return $qrcode;
    }

    $update_user = 'update '.$db->table('member').' set `scene_id`=0 where `expired`>0 and `expired`<'.time();
    $db->update($update_user);

    $scene_arr = range(1, 100000);

    $scene_id = 0;
    foreach($scene_arr as $id)
    {
        $check_scene_id = 'select count(*) from '.$db->table('member').' where `scene_id`='.$id.' and (`expired`=-1 or `expired`>'.time().')';
        $log->record($check_scene_id);

        if(!$db->fetchOne($check_scene_id))
        {
            $db->autoUpdate('member', array('scene_id' => $scene_id), '`openid`=\''.$openid.'\'');
            $scene_id = $id;
            break;
        }
    }
    //scene_id已满
    if($scene_id == 0)
    {
        return false;
    }
    //临时二维码申请
    $data = '{"expire_seconds": 2592000, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": '.$scene_id.'}}}';
    $response = post('https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token, $data, false);

    $response = json_decode($response);

    if(isset($response->errcode))
    {
        $log->record('get qrcode fail:'.$response->errcode.':'.$response->errmsg);
        $sysconf_data = array(
            'value' => 0
        );
        $db->autoUpdate('sysconf', $sysconf_data, '`key`=\'expired\'');
        return get_qrcode($openid, $access_token);
        return false;
    } else {
        $data = array(
            'scene_id' => $scene_id,
            'ticket' => $response->ticket,
            'expired' => time()+2592000
        );

        $db->autoUpdate('member', $data, '`openid`=\''.$openid.'\'');
        $qrcode = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$response->ticket;
        return $qrcode;
    }
}

/**
 * 获取access_token
 * @param string $appid 公众号appid
 * @param string $secretKey 公众号密钥appsecret
 * @return string 成功时返回获取的access_token,失败时返回false
 * @author winsen
 * @date 2014-10-24
 */
function get_access_token($appid, $secretkey)
{
    global $errors;
    global $db;
    global $log;

    $check_access_token = 'select `value` from '.$db->table('sysconf').' where `key`=\'expired\'';
    $expired = $db->fetchOne($check_access_token);
    if($expired > time())
    {
        $get_access_token = 'select `value` from '.$db->table('sysconf').' where `key`=\'access_token\'';

        $log->record('access_token is not expired, expired in '.date('Y-m-d H:i:s', $expired));
        return $db->fetchOne($get_access_token);
    }
    $log->record('access_token is expired, refresh.');
    //对于access_token超时，则重新获取access_token
    $request_time = time();
    $url_get_access_token = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s';
    $url = sprintf($url_get_access_token, $appid, $secretkey);

    $data = get($url, null);
    $log->record($data);
    $response = json_decode($data);

    if(!empty($response->errcode))
    {
        $log->record('get access_token '.$response->errmsg.':'.$errors[$response->errcode]);
        return false;
    } else {
        $data = array('value'=>($request_time + ($response->expires_in/2)));

        $db->autoUpdate('sysconf', $data, '`key`=\'expired\'');

        $data = array('value'=>$response->access_token);

        $db->autoUpdate('sysconf', $data, '`key`=\'access_token\'');
        $log->record('access_token expired in '.date('Y-m-d H:i:s', ($request_time + $response->expires_in)));
        return $response->access_token;
    }
}

/**
 * 微信接入开发者模式验证URL以及接收用户信息时使用
 * @param string $signature 微信加密签名
 * @param string $timestamp 时间戳
 * @param string $nonce 随机数
 * @param string $token 公众号设置的Token
 * @return bool
 * @author winsen
 * @date 2014-10-24
 */
function check_signature($signature, $timestamp, $nonce, $token)
{
	$tmpArr = array($token, $timestamp, $nonce);
	sort($tmpArr, SORT_STRING);
	$tmpParam = implode($tmpArr);
	$tmpStr = sha1($tmpParam);

    if( $tmpStr == $signature )
    {
		return true;
	} else {
		return false;
	}
}

/**
 * 生成预支付交易单
 */
function create_prepay($appid, $mch_id, $mch_key, $openid, $total_fee, $body, $detail, $out_trade_no, $params = array())
{
    global $config;

    $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    $now = time();
    $data = array(
        'appid' => $appid,
        'mch_id' => $mch_id,
        'openid' => $openid,
        'total_fee' => $total_fee*100,
        'nonce_str' => get_nonce_str(),
        'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
        'fee_type' => 'CNY',
        'time_start' => date('YmdHis', $now),
        'time_expire' => date('YmdHis', ($now+3600*24*7)),//支付链接7天后无效
        'notify_url' => 'http://'.$_SERVER['HTTP_HOST'].'/notify.php',//完成支付后的回调地址
        'trade_type' => 'JSAPI',//交易类型，可选：JSAPI, NATIVE, APP, WAP
        //  'limit_pay' => 'no_credit', //此项将不允许使用信用卡支付
        'body' => $body,
        'detail' => $detail,
        'out_trade_no' => $out_trade_no//订单编号
    );

    if(!is_weixin() || empty($data['openid'])) {
        $data['trade_type'] = 'MWEB';
        unset($data['openid']);
    }

    $data = array_merge($data, $params);

    ksort($data);
    $param_str = '';
    $xml = new SimpleXMLElement('<xml></xml>');

    foreach($data as $key=>$value)
    {
        if(empty($value))
        {
            unset($data[$key]);
        } else {
            $param_str .= $key.'='.$value.'&';
            $xml->addChild($key, $value);
        }
    }

    $param_str .= 'key='.$mch_key;

    $sign = md5($param_str);
    $sign = strtoupper($sign);
    $xml->addChild('sign', $sign);

    global $log;
    $log->record('request data:'.$xml->asXML());
    $response = post($url, $xml->asXML(), false);
    $log->record('response data:'.$response);
    return $response;
}

function refund($appid, $mch_id, $mch_key, $order_sn, $total_fee, $ssl_cert, $ssl_key) {
    $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

    $data = array(
        'appid' => $appid,
        'mch_id' => $mch_id,
        'nonce_str' => get_nonce_str(),
        'out_trade_no' => $order_sn,
        'out_refund_no' => 'RF'.$order_sn,
        'total_fee' => $total_fee * 100,
        'refund_fee' => $total_fee * 100
    );

    $data['sign'] = tenpay_sign($data, $mch_key);

    $xml = new SimpleXMLElement('<xml></xml>');

    foreach($data as $key=>$value)
    {
        if(empty($value))
        {
            unset($data[$key]);
        } else {
            $xml->addChild($key, $value);
        }
    }

    global $log;
    $log->record('request data:'.$xml->asXML());
    $response = post($url, $xml->asXML(), false, $ssl_cert, $ssl_key);
    $log->record('response data:'.$response);

    $response = simplexml_load_string($response);

    $verify_sign = tenpay_sign($response, $mch_key);

    if($verify_sign != strtolower($response->sign)) {
        return '签名错误';
    }

    if(empty($response) || $response->result_code != 'SUCCESS' || empty($response->return_msg)) {
        return '调用退款接口出错:'.$response->err_code_des;
    } else {
        return true;
    }
}

function tenpay_sign($data, $mch_key)
{
    $data_ = array();
    foreach($data as $key=>$value)
    {
        $data_[$key] = $value;
    }
    $data = $data_;

    ksort($data);
    $param_str = '';
    foreach($data as $key=>$value)
    {
        if($value == '') {
            continue;
        }

        if($key != 'sign')
        {
            $param_str .= $key . '=' . $value . '&';
        }
    }
    $param_str .= 'key='.$mch_key;

    $sign = md5($param_str);
    return strtolower($sign);
}

/**
 * 生成随机字符串
 */
function get_nonce_str()
{
    $seed = '01234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';

    $strlen = 32;

    $nonce_str = '';
    while($strlen--)
    {
        $nonce_str .= $seed[rand(0, strlen($seed)-1)];
    }

    return $nonce_str;
}

/**
 *
 */
function send_template_msg($template_id, $to_openid, $url, $data) {
    global $config, $log;

    $access_token = get_access_token($config['appid'], $config['appsecret']);
    $post_url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;

    $post_data = array(
        'touser' => $to_openid,
        'template_id' => $template_id,
        'url' => $url,
        'data' => $data
    );

    $log->record_array($post_data);

    $response = post($post_url, json_encode($post_data), false);
    $log->record($response);

    $response = json_decode($response, true);

    if($response['errcode'] == 0) {
        return true;
    } else {
        return false;
    }
}

function notify_member($openid, $notice) {
    global $config;

    $url = 'http://'.$config['domain'].'/self_shop.php';

    $data = array(
        'first' => array(
            'value' => $notice,
            'color' => '#333333'
        ),
        'keyword1' => array(
            'value' => '审核通过',
            'color' => '#333333'
        ),
        'keyword2' => array(
            'value' => date('Y年m月d日 H:i:s'),
            'color' => '#333333'
        ),
        'remark' => array(
            'value' => '感谢您对'.$config['site_name'].'的大力支持，点击链接可进入个人店铺',
            'color' => '#333333'
        )
    );

    $template_id = 'VZgTHPJPTBQSbM_smCSsASCGE2cwJv_-aiIho29mpYo';

    return send_template_msg($template_id, $openid, $url, $data);
}

function reward_exchange_notify($openid, $notice, $increment) {
    global $config;

    $url = 'http://'.$config['domain'].'/account.php';

    $data = array(
        'first' => array(
            'value' => $notice,
            'color' => '#333333'
        ),
        'keyword1' => array(
            'value' => sprintf('%.2f', $increment).'元',
            'color' => '#333333'
        ),
        'keyword2' => array(
            'value' => date('Y年m月d日 H:i'),
            'color' => '#333333'
        ),
        'remark' => array(
            'value' => '感谢您对'.$config['site_name'].'的大力支持，点击链接可查看钱包详情'."\n".'佣金在订单完成前都会处于待发佣金中，等用户确认订单完成后佣金才能使用',
            'color' => '#333333'
        )
    );

    $template_id = 'i2ariKGtOPWiVlbCX7UyA_8A5KM4dFVU2fG3Rd05oS8';

    return send_template_msg($template_id, $openid, $url, $data);
}

function order_status_notify($openid, $notice, $order_sn, $status, $remark = '', $link_to_order_detail = true) {
    global $config;

    $url = 'http://'.$config['domain'].'/order.php?act=detail&sn='.$order_sn;

    if(!$link_to_order_detail) {
        $url = '';
    }

    $data = array(
        'first' => array(
            'value' => $notice,
            'color' => '#333333'
        ),
        'OrderSn' => array(
            'value' => $order_sn,
            'color' => '#333333'
        ),
        'OrderStatus' => array(
            'value' => $status,
            'color' => '#333333'
        ),
        'remark' => array(
            'value' => $remark,
            'color' => '#333333'
        )
    );

    $template_id = 'ApYy40v8SYPHLHjQ0WjkJPjbtT4YgowkK0OwmN3Ew_E';

    return send_template_msg($template_id, $openid, $url, $data);
}

function order_status_customer_notify($openid, $notice, $order_sn, $status, $remark = '') {
    global $config;

    $url = 'http://'.$config['domain'].'/order.php?act=detail&sn='.$order_sn;

    $data = array(
        'first' => array(
            'value' => $notice,
            'color' => '#333333'
        ),
        'OrderSn' => array(
            'value' => $order_sn,
            'color' => '#333333'
        ),
        'OrderStatus' => array(
            'value' => $status,
            'color' => '#333333'
        ),
        'remark' => array(
            'value' => $remark,
            'color' => '#333333'
        )
    );

    $template_id = 'cLf9TvadfoIhDw63Wr69GkbYmOMX3P9DbuxvO8VchpY';

    return send_template_msg($template_id, $openid, $url, $data);
}

function send_customer_message($openid, $msg_type, $data) {
    global $config, $log;

    $param = '';

    switch($msg_type) {
        case 'text':
            $param = '{"touser":"'.$openid.'", "msgtype":"text", "text":{"content":"'.$data.'"}}';
            break;

        case 'news':
            $param = '{"touser":"'.$openid.'", "msgtype":"news", "news": {"articles": [';

            foreach($data as $article) {
                $param .= '{"title":"'.$article['title'].'", "description":"'.$article['description'].'", "url":"'.$article['url'].'", "picurl":"'.$article['picUrl'].'"},';
            }

            $param .= ']}}';
            break;
    }

    $access_token = get_access_token($config['appid'], $config['appsecret']);
    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$access_token;
    $response = post($url, $param, false);

    $response = json_decode($response, true);

    if($response['errcode'] == 0) {
        return true;
    } else {
        $log->record_array($response);
        return false;
    }
}

/**
 * 小程序模板消息：订单支付通知
 * @param $openid string 小程序openid
 * @param $order_sn
 * @param $prepay_id
 * @param $amount
 * @param $status_str
 * @param string $notice
 * @return bool
 */
function send_paid_order_message($openid, $order_sn, $prepay_id, $amount, $status_str, $notice = '您的商品很快就飞奔到您手上咯！') {
    global $config, $log;

    if(!defined('WX_PAID_ORDER_TEMPLATE_ID')) {
        return false;
    }

    $param = [
        'touser' => $openid,
        'template_id' => WX_PAID_ORDER_TEMPLATE_ID,
        'page' => '/pages/order/detail?sn='.$order_sn,
        'form_id' => $prepay_id,
        'data' => [
            'keyword1' => $order_sn,
            'keyword2' => '￥'.sprintf('%.2f', $amount).'元',
            'keyword3' => $status_str,
            'keyword4' => $notice
        ],
        'emphasis_keyword' => 'keyword1.DATA'
    ];

    $access_token = get_access_token($config['mini_appid'], $config['mini_appsecret']);
    $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$access_token;

    $response = post($url, json_encode($param), false);

    $response = json_decode($response, true);

    if($response['errcode'] == 0) {
        return true;
    } else {
        $log->record_array($response);
        return false;
    }
}

function get_min_program_qrcode($scene, $page) {
    global $config, $log;

    $param = [
        'scene' => $scene,
        'page' => $page
    ];

    $access_token = get_access_token($config['mini_appid'], $config['mini_appsecret']);
    $url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token;

    $response = post($url, json_encode($param), false);

    $raw_response = $response;
    $response = json_decode($response, true);

    if($response) {
        $log->record_array($response);
        return false;
    } else {
        return $raw_response;
    }
}