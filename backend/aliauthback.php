<?php
  //支付宝授权回调
  //文档：https://docs.open.alipay.com/20160728150111277227/intro/
  require_once 'common.php';
  require_once 'lib/alipay/AopSdk.php';
  require_once 'lib/alipay/f2fpay/config/config.php';

  $mchId = $_GET['mch_id'];
  $appAuthCode = $_GET['app_auth_code'];

  $aop = new AopClient ();
  $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
  $aop->appId = $config['app_id'];
  $aop->rsaPrivateKey = $config['merchant_private_key'];
  $aop->alipayrsaPublicKey= $config['alipay_public_key'];
  $aop->apiVersion = '1.0';
  $aop->signType = 'RSA2';
  $aop->postCharset='UTF-8';
  $aop->format='json';
  $request = new AlipayOpenAuthTokenAppRequest ();

  $bizContent = array(
                  'grant_type' => 'authorization_code',
                  'code'       => $appAuthCode
                );
  $request->setBizContent(json_encode($bizContent));
  $result = $aop->execute ($request); 
  
  $responseString = json_encode($result);
  $responseData   = json_decode($responseString, true);
  if ('10000' == $responseData['alipay_open_auth_token_app_response']['code']) {
    $authAppId = $responseData['alipay_open_auth_token_app_response']['auth_app_id'];
    $sql = "UPDATE mchs SET alipay_app_id = '$authAppId' WHERE mch_id = $mchId";
    $db->query($sql);

    $redis->hset('alipay_code', $mchId, json_encode($responseData['alipay_open_auth_token_app_response']));
    
    echo '<div style="font-size:30px;margin-top:20px;text-align:center">签约成功</div>';
    //$url = 'https://openhome.alipay.com/isv/settling/inviteSign.htm?appId=2018052360189175&sign=fQrTAgVhoxYcpI3JcU7VhWvFEs8vpGFVCjZhFdBG4o4=';
    //header('location: '.$url);
  }
