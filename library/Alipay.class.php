<?php
/**
 *
 * @author Winsen
 * @date 2017-11-28
 * @version 1.0.0
 */

class Alipay {
    private $sign_method = 'sha256WithRSAEncryption';
    private $sign_type = 'RSA2';
    private $private_key_pem = '';
    private $public_key_pem = '';

    public function __construct($private_key_pem = '', $public_key_pem = '') {
        $this->private_key_pem = $private_key_pem;
        $this->public_key_pem = $public_key_pem;

        $openssl_methods = openssl_get_md_methods(true);

        if(!in_array($this->sign_method, $openssl_methods)) {
            $this->sign_method = OPENSSL_ALGO_SHA1;
            $this->sign_type = 'RSA';
        }
    }

    public function combineParams($params) {
        if(is_array($params)) {
            ksort($params);
            $param_str = '';
            foreach($params as $key => $value) {
                if(empty($value) || $key == 'sign') {
                    continue;
                }

                if($param_str != '') {
                    $param_str .= '&';
                }

                $param_str .= $key.'='.$value;
            }
            $params = $param_str;
        }

        return $params;
    }

    public function sign($params, $cert) {
        $params = $this->combineParams($params);

        $private_key = file_get_contents($cert);
        $res = openssl_get_privatekey($private_key);
        openssl_sign($params, $sign, $private_key, $this->sign_method);
        openssl_free_key($res);

        return base64_encode($sign);
    }

    public function verifySign($params, $sign, $cert) {
        $params = $this->combineParams($params);

        $public_key = file_get_contents($cert);
        $public_key = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($public_key, 64, "\n", true) .
            "-----END PUBLIC KEY-----";
        $res = openssl_get_publickey($public_key);
        $sign = base64_decode($sign);
        $verify = openssl_verify($params, $sign, $res, $this->sign_method);
        openssl_free_key($res);

        return $verify > 0;
    }

    public function notify() {

    }

    public function refund($app_id, $order_sn, $refund_amount) {
        $refund_url = 'https://openapi.alipay.com/gateway.do';

        $biz_content = array(
            'out_trade_no' => $order_sn,
            'refund_amount' => $refund_amount
        );

        $params = array(
            'app_id' => $app_id,
            'method' => 'alipay.trade.refund',
            'charset' => 'utf-8',
            'sign_type' => $this->sign_type,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode($biz_content)
        );

        $params['sign'] = $this->sign($params, $this->private_key_pem);

        $response = post($refund_url, $params, true);

        $response = json_decode($response, true);

        if(empty($response)) {
            return '请求接口错误，请稍后再试';
        }

        $api_response = $response['alipay_trade_refund_response'];

        if($this->verifySign(json_encode($api_response), $response['sign'], $this->public_key_pem)) {
            if($api_response['code'] == '10000') {
                return true;
            } else {
                return $api_response['sub_code'].':'.$api_response['sub_msg'];
            }
        } else {
            return '签名错误';
        }
    }
}
