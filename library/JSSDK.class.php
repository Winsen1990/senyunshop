<?php
class JSSDK {
  private $appId;
  private $appSecret;
  private $db;

  public function __construct($appId, $appSecret, $db) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;
    $this->db = $db;
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();
    $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    $timestamp = time();
    $nonceStr = $this->createNonceStr();

    // 这里参数的顺序要按照 key 值 ASCII 码升序排序
    $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

    $signature = sha1($string);

    $signPackage = array(
      "appId"     => $this->appId,
      "nonceStr"  => $nonceStr,
      "timestamp" => $timestamp,
      "url"       => $url,
      "signature" => $signature,
      "rawString" => $string
    );
    return $signPackage; 
  }

  private function createNonceStr($length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $str = "";
    for ($i = 0; $i < $length; $i++) {
      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
  }

  private function getJsApiTicket() {
    $expire_time = $this->db->getColumn('sysconf', 'value', array('key' => 'jsapi_ticket_expire'));
    if ($expire_time < time()) {
      $accessToken = $this->getAccessToken();
      $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token='.$accessToken;
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        $this->db->autoUpdate('sysconf', array('value' => $ticket), '`key`=\'jsapi_ticket\'');
        $this->db->autoUpdate('sysconf', array('value' => (time() + 7000)), '`key`=\'jsapi_ticket_expire\'');
      }
    } else {
      $ticket = $this->db->getColumn('sysconf', 'value', array('key' => 'jsapi_ticket'));
    }

    return $ticket;
  }

  private function getAccessToken() {
    $access_token = get_access_token($this->appId, $this->appSecret);
    return $access_token;
  }

  private function httpGet($url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }
}

