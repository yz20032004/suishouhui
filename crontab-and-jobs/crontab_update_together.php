<?php
  //检查拼团有无过期未成团情况，过期团将发消息提醒,同时退款
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';
  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);

  $expireTotal = 0;
  $now = date('Y-m-d H:i:s');

  $sql = "SELECT id, appid, openid, prepay_id, transaction_id, mch_id, groupon_id, is_head, buy_total, total_fee, out_trade_no, together_no, together_expired_at FROM wechat_groupon_pays WHERE is_together = 1 AND together_status = 'open' AND together_expired_at < NOW()";
  $data = $db->fetch_array($sql);
  foreach ($data as $row) {
    $openId  = $row['openid'];
    $appId   = $row['appid'];
    $togetherExpiredAt = $row['together_expired_at'];
    $prepayId = $row['prepay_id'];
    $subMchId = $row['mch_id'];
    $togetherId = $row['groupon_id'];
    $refund     = $row['total_fee']/100;
    $outTradeNo = $row['out_trade_no'];
    $togetherNo = $row['together_no'];
    $buyTotal   = $row['buy_total'];
    $transactionId = $row['transaction_id'];

    $sql = "UPDATE wechat_groupon_pays SET together_status = 'expire' WHERE id = $row[id]";
    $db->query($sql);

    $sql = "UPDATE mch_togethers SET sold = sold - $buyTotal";
    if ($row['is_head']) {
      $sql .= ", opens = opens - 1, expires = expires + 1";
    }
    $sql .= " WHERE id = $togetherId";
    $db->query($sql);

    $sql = "SELECT COUNT(id) AS total FROM wechat_groupon_pays WHERE mch_id = $subMchId AND is_together = 1 AND together_no = '$togetherNo'";
    $ret = $db->fetch_row($sql);
    $togetherPeople = $ret['total'];

    if (!isset($merchantData[$subMchId])) {
      $sql = "SELECT business_name FROM shops WHERE mch_id = $subMchId";
      $ret = $db->fetch_row($sql);
      $merchantName = $ret['business_name'];
      $merchantData[$subMchId] = $merchantName;
    } else {
      $merchantName = $merchantData[$subMchId];
    }
    if (!isset($togetherData[$subMchId])) {
      $sql = "SELECT coupon_name, people, amount, price FROM mch_togethers WHERE id = $togetherId";
      $together = $db->fetch_row($sql);
      $togetherData[$subMchId] = $together;
    } else {
      $together = $togetherData[$subMchId];
    }

    $sql = "SELECT template_id FROM app_subscribe_list WHERE mch_id = $subMchId AND tid = 1920";
    $row = $db->fetch_row($sql);
    $templateId = $row['template_id'];
    $templateData= array(
                    'thing4' => array('value'=>$together['coupon_name']),
                    'amount5' => array('value'=>$together['amount']),
                    'amount2' => array('value'=>$together['price']),
                    'thing3'  => array('value'=>'未满'.$together['people'].'人成团'),
                    'amount6' => array('value'=>$refund.'元')
                  );
    $accessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
    $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$accessToken;
    $postData = array(
                  'touser'       => $openId,
                  'template_id'  => $templateId,
                  'page'         => 'pages/vip/groupon_detail?mch_id='.$subMchId.'&out_trade_no='.$outTradeNo,
                  'data'         => $templateData
                );
    print_r($postData);
    $r = sendHttpRequest($url, $postData);
    echo 'send together expire mini message '.$r.PHP_EOL;

    $refundFee = $refund*100;
    if ($appId == SUISHOUHUI_APP_ID) {
      $data = array('mch_id'=>$subMchId, 'out_trade_no'=>$outTradeNo, 'total_fee'=>$refundFee, 'refund_fee'=>$refundFee, 'openid'=>$openId);
      together_refund($data);
    } else {
      //商户独立小程序退款
      $data = array('mch_id'=>$subMchId, 'out_trade_no'=>$outTradeNo, 'total_fee'=>$refundFee, 'refund_fee'=>$refundFee, 'openid'=>$openId, 'appid'=>$appId, 'transaction_id'=>$transactionId);
      together_refund_mch_ext($data);
    }
  }

  function together_refund($data)
  {
    require_once dirname(__FILE__).'/lib/WxPay.Config.php';
    require_once dirname(__FILE__).'/lib/WxPay.Exception.php';
    global $db;
    $mchId      = $data['mch_id'];
    $totalFee   = $data['total_fee'];
    $refundFee  = $data['refund_fee'];
    $outTradeNo = $data['out_trade_no'];
    $openId     = $data['openid'];
    $outRefundNo= getOutTradeNo();
    $refundDesc = '拼团未成团退款';
    $postData = array(
                  'appid' => SUISHOUHUI_APP_ID,
                  'mch_id'=> KEYOU_MCHID,
                  'nonce_str' => getNonceStr(),
                  'out_trade_no' => $outTradeNo,
                  'out_refund_no'=> $outRefundNo,
                  'total_fee'    => $totalFee,
                  'refund_fee'   => $refundFee,
                  'refund_desc'  => $refundDesc
    );
    $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    $sign = MakeKeyouSign($postData);
    $postData['sign'] = $sign;
    $xml = ToXml($postData);
    $response = postKeyouXmlCurl($xml, $url, true, 6);
    $retData = FromXml($response);
    if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
      $now = date('Y-m-d H:i:s');
      $sql = "INSERT INTO wechat_refunds (appid, mch_id, openid, out_trade_no, out_refund_no, refund_id, total_fee, refund_fee, cash_refund_fee, coupon_refund_fee, refund_desc, created_by_uid, created_at) VALUES ('$retData[appid]', $mchId, '$openId', '$outTradeNo', '$outRefundNo', '$retData[refund_id]', $totalFee, $refundFee, $retData[cash_refund_fee], $retData[coupon_refund_fee], '$refundDesc', 0, '$now')";
      $db->query($sql);

      $sql = "UPDATE wechat_groupon_pays SET refund_fee = $refundFee WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $db->query($sql);
    }
  }

  function together_refund_mch_ext($data)
  {
    //独立小程序拼团退款，先完结分账，再退款
    require_once dirname(__FILE__).'/lib/WxPay.Config.php';
    require_once dirname(__FILE__).'/lib/WxPay.Exception.php';
    global $db;
    $mchId      = $data['mch_id'];
    $totalFee   = $data['total_fee'];
    $refundFee  = $data['refund_fee'];
    $openId     = $data['openid'];
    $appId      = $data['appid'];
    $outTradeNo = $data['out_trade_no'];
    $transactionId = $data['transaction_id'];

    $outRefundNo= getOutTradeNo();
    $refundDesc = '拼团未成团退款';
    $postData = array(
                  'appid'   => KEYOU_MP_APP_ID,
                  'mch_id'=> MCH_ID,
                  'sub_appid'  => $appId,
                  'sub_mch_id' => $mchId,
                  'nonce_str' => getNonceStr(),
                  'transaction_id' => $transactionId,
                  'out_refund_no'=> $outRefundNo,
                  'total_fee'    => $totalFee,
                  'refund_fee'   => $refundFee,
                  'refund_desc'  => $refundDesc
    );
    $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    $sign = MakeSign($postData);
    $postData['sign'] = $sign;
    $xml = ToXml($postData);
    $response = postXmlCurl($xml, $url, true, 6);
    $retData = FromXml($response);
    if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
      $now = date('Y-m-d H:i:s');
      $sql = "INSERT INTO wechat_refunds (appid, mch_id, openid, out_trade_no, out_refund_no, refund_id, total_fee, refund_fee, cash_refund_fee, coupon_refund_fee, refund_desc, created_by_uid, created_at) VALUES ('$retData[appid]', $mchId, '$openId', '$outTradeNo', '$outRefundNo', '$retData[refund_id]', $totalFee, $refundFee, $retData[cash_refund_fee], $retData[coupon_refund_fee], '$refundDesc', 0, '$now')";
      $db->query($sql);

      $sql = "UPDATE wechat_groupon_pays SET refund_fee = $refundFee WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $db->query($sql);
    }
  }
