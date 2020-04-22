<?php
  /*
   * 微信支付官方支付统一下单
   */
  function postUnifiedorder($subOpenId, $subMchId, $body, $attach, $totalFee)
  {
    $outTradeNo = getOutTradeNo();
    $data = array(
               'appid'  => KEYOU_MP_APP_ID,
               'mch_id'  => MCHID,
               'sub_appid'  => SUISHOUHUI_APP_ID,
               'sub_mch_id' => $subMchId,
               'nonce_str'  => WxPayApi::getNonceStr(),
               'sign_type'  => 'MD5',
               'body'       => $body,
               'attach'     => $attach,
               'out_trade_no' => $outTradeNo,
               'total_fee'    => $totalFee,
               'sub_openid'       => $subOpenId,
               'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
               'notify_url'       => SITE_URL.'/payNotify.php',
               'trade_type'       => 'JSAPI'
               );
    $sign = MakeSign($data);
    $data['sign'] = $sign;
    $xml = ToXml($data);
    //统一下单
    $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
    $response = postXmlCurl($xml, $url, false, 6);
    $responseData = FromXml($response);

    $data = array(
                  'appId' => SUISHOUHUI_APP_ID,
                  'timeStamp' => (string)time(),
                  'nonceStr'  => $responseData['nonce_str'],
                  'package'   => 'prepay_id='.$responseData['prepay_id'],
                  'signType'  => 'MD5',
                 );
    $sign = MakeSign($data);
    $data['paySign'] = $sign;
    $data['prepay_id'] = $responseData['prepay_id'];
    $data['out_trade_no'] = $outTradeNo;
    return $data;
  }

  /**
   * 腾讯云支付统一下单
   */
  function postTenpayUnifiedorder($tenpayConfig, $subOpenId, $subMchId, $body, $attach, $totalFee)
  {
    $outTradeNo = $tenpayConfig['cloud_cashier_id'].getOutTradeNo();
    $payContent = array(
                       'out_trade_no' => $outTradeNo,
                       'total_fee'    => $totalFee,
                       'fee_type'     => 'CNY',
                       'body'         => $body,
                       'is_shelves_order' => false,
                       'wxpay_pay_content_ext' => array(
                                                     'attach' => $attach,
                                                   ),
                      );
    $payMchKey = array(
                     'pay_platform' => 1,
                     'out_mch_id'      => $tenpayConfig['out_mch_id'],
                     'out_sub_mch_id'  => $tenpayConfig['out_sub_mch_id'],
                     'out_shop_id'     => $tenpayConfig['out_shop_id'],
                     'wxpay_pay_mch_key_ext' => array(
                                                   'mini_program_sub_app_id' => SUISHOUHUI_APP_ID,
                                                   'sub_open_id' => $subOpenId,
                                                )
                 );
    $orderClient = array('spbill_create_ip' => $_SERVER['REMOTE_ADDR']);
    $requestContent = array(
                       'pay_content' => $payContent,
                       'pay_mch_key' => $payMchKey,
                       'order_client'=> $orderClient,
                       'nonce_str'   => getNonceStr(),
                      );
    $authenCode = strtoupper(hash_hmac('sha256', json_encode($requestContent), $tenpayConfig['authen_key']));
    $postData = array(
                     'request_content' => json_encode($requestContent),
                     'authen_info' => array('a'=>array('authen_type'=>1, 'authen_code'=>$authenCode)));
    //$url = SAE_URL.'/tenpay.php'; //本地环境curl版本不符合tenpay API接口请求要求，采用新浪云中转
    $url = 'https://pay.qcloud.com/cpay/mini_program_pay';
    $ret = sendHttpRequest($url, $postData);
    $result = json_decode($ret, true);
    $responseData = json_decode($result['response_content'], true);
    $prepayId = $responseData['mini_program_pay']['order_content']['wxpay_order_content_ext']['prepare_id'];

    $jsApiArgs = $responseData['mini_program_pay']['jsapi_args']['wxpay']['mini_program_pay'];
    $data = array(
                  'appId' => SUISHOUHUI_APP_ID,
                  'timeStamp' => (string)time(),
                  'nonceStr'  => $jsApiArgs['nonce_str'],
                  'package'   => $jsApiArgs['package'],
                  'signType'  => 'MD5',
                  'paySign'   => $jsApiArgs['pay_sign'],
                  'prepay_id' => $prepayId,
                  'out_trade_no' => $outTradeNo
                 );
    return $data;
  }
