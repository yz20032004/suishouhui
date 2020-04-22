<?php
class JSSDK {
  private $appId;
  private $appSecret;
  private $redis;

  public function __construct($redis, $appId, $appSecret) {
    $this->appId = $appId;
    $this->appSecret = $appSecret;

    $this->redis = $redis;
  }

  public function getSignPackage() {
    $jsapiTicket = $this->getJsApiTicket();
    $url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
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
    $expireTime = $this->redis->hget('keyouxinxi', 'jsapi_expire_time');
    $accessToken = $this->getAccessToken();
    $jsApiAccessToken = $this->redis->hget('keyouxinxi', 'jsapi_wx_token');
    if ($expireTime < time() || $accessToken != $jsApiAccessToken) {
      $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
      //$url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$accessToken.'&type=wx_card';
      $res = json_decode($this->httpGet($url));
      $ticket = $res->ticket;
      if ($ticket) {
        $expireTime = time() + 7000;
        $jsapiTicket = $ticket;
        $this->redis->hset('keyouxinxi', 'jsapi_ticket', $jsapiTicket);
        $this->redis->hset('keyouxinxi', 'jsapi_expire_time', $expireTime);
        $this->redis->hset('keyouxinxi', 'jsapi_wx_token', $accessToken);
      }
    } else {
      $ticket = $this->redis->hget('keyouxinxi', 'jsapi_ticket');
    }

    return $ticket;
  }

  private function getAccessToken() {
    $access_token = $this->redis->hget('keyouxinxi', 'wx_access_token');
    
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

