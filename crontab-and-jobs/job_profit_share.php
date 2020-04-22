<?php
  /*
   * 营销收益分账操作
   */
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';
  require_once dirname(__FILE__).'/lib/WxPay.Config.php';
  require_once dirname(__FILE__).'/lib/WxPay.Exception.php';
  ini_set('default_socket_timeout', -1);

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  while (true) {
    $date = date('Ymd');
    $grouponList = $redis->zRangeByScore('keyou_groupon_profit_share_list', 0, time());
    foreach ($grouponList as $key) {
      $grouponData = unserialize($redis->hget('keyou_groupon_history_'.$date, $key));
      $outTradeNo  = $grouponData['out_trade_no'];
      $mchId       = $grouponData['sub_mch_id'];
      $appId       = $grouponData['sub_appid'];
      $cashFee     = $grouponData['cash_fee'];
      $transactionId = $grouponData['transaction_id'];
      $subOpenId     = $grouponData['sub_openid'];
      $attach        = explode(',', $grouponData['attach']);
      $buyTotal  = $attach[3];
      //会员团购分销
      $distributeId= isset($attach[7])?$attach[7]:0;
      if ($distributeId) {
        distributeProfitsharing($distributeId, $buyTotal, $appId, $cashFee, $transactionId, $subOpenId);
      }

      $basicFeeRate     = $redis->hget('keyou_merchant_basic_fee_rate_list', $mchId);
      $marketingFeeRate = $redis->hget('keyou_merchant_marketing_fee_rate_list', $mchId);
      $profitFeeRate    = $marketingFeeRate - $basicFeeRate;
      if ($profitFeeRate  > 0) {
        if (!$redis->hget('keyou_profit_sharing_receiver', $mchId)) {
          profitsharingaddreceiver($appId, $mchId);
        }
        $cashFee = $grouponData['cash_fee'];
        $profitType = 'groupon';
        serviceMerchantProfitsharing($appId, $mchId, $cashFee, $outTradeNo, $transactionId, $profitFeeRate, $profitType); 
      } else {
        profitsharingfinish($transactionId, $mchId);
      }
      $redis->zRem('keyou_groupon_profit_share_list', $key);
    }

    $waimaiList = $redis->zRangeByScore('keyou_waimai_profit_share_list', 0, time());
    foreach ($waimaiList as $key) {
      $waimaiData = unserialize($redis->hget('keyou_trade_history_'.$date, $key));
      $outTradeNo  = $waimaiData['out_trade_no'];
      $mchId       = $waimaiData['sub_mch_id'];
      $appId       = $waimaiData['sub_appid'];
      $cashFee     = $waimaiData['cash_fee'];
      $transactionId = $waimaiData['transaction_id'];
      $subOpenId     = $waimaiData['sub_openid'];

      $basicFeeRate     = $redis->hget('keyou_merchant_basic_fee_rate_list', $mchId);
      $marketingFeeRate = $redis->hget('keyou_merchant_marketing_fee_rate_list', $mchId);
      $profitFeeRate    = $marketingFeeRate - $basicFeeRate;
      if ($profitFeeRate  > 0) {
        if (!$redis->hget('keyou_profit_sharing_receiver', $mchId)) {
          profitsharingaddreceiver($appId, $mchId);
        }
        $cashFee = $waimaiData['cash_fee'];
        $profitType = 'waimai';
        serviceMerchantProfitsharing($appId, $mchId, $cashFee, $outTradeNo, $transactionId, $profitFeeRate, $profitType); 
      }
      $redis->zRem('keyou_waimai_profit_share_list', $key);
    }

    sleep(120);
  }

  //添加分销分账方
  function profitsharingaddreceiver($appId, $mchId)
  {
    global $redis, $db;
    $receiver =  array(
                        'type' => 'MERCHANT_ID',
                        'account' => KEYOU_MCHID,
                        'name'    => '上海客友信息技术有限公司',
                        'relation_type' => 'SERVICE_PROVIDER'
                      );
    $data = array(
                'appid'  => KEYOU_MP_APP_ID,
                'mch_id'  => MCH_ID,
                'sub_appid'  => $appId,
                'sub_mch_id' => $mchId,
                'nonce_str'  => getNonceStr(),
                'sign_type'  => 'HMAC-SHA256',
                'receiver'   => json_encode($receiver)
            );
     $sign = MakeSign($data, 'HMAC-SHA256');
     $data['sign'] = $sign;
     $xml = ToXml($data);
     //统一下单
     $url = 'https://api.mch.weixin.qq.com/pay/profitsharingaddreceiver';
     $response = postXmlCurl($xml, $url, false, 6);
     echo $response.PHP_EOL;
     $retData = FromXml($response);
     if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
       $redis->hset('keyou_profit_sharing_receiver', $mchId, KEYOU_MCHID);
     }
  }

  //团购后服务商获得佣金
  function serviceMerchantProfitsharing($subAppId, $subMchId, $cashFee, $outTradeNo, $transactionId, $profitFeeRate, $profitType)
  {
    global $db, $redis;
    require_once dirname(__FILE__).'/lib/WxPay.Config.php';
    require_once dirname(__FILE__).'/lib/WxPay.Exception.php';

    $profitFee  = round($cashFee * $profitFeeRate, 0);
    $outOrderNo = getOutTradeNo();
    $receivers =  array(
                        'type' => 'MERCHANT_ID',
                        'account' => KEYOU_MCHID,
                        'amount'  => $profitFee,
                        'description' => '服务商分佣'
                      );
    $data = array(
                'appid'  => KEYOU_MP_APP_ID,
                'mch_id'  => MCH_ID,
                'sub_appid'  => $subAppId,
                'sub_mch_id' => $subMchId,
                'nonce_str'  => getNonceStr(),
                'sign_type'  => 'HMAC-SHA256',
                'transaction_id' => $transactionId,
                'out_order_no'   => $outOrderNo,
                'receivers'     => json_encode(array($receivers))
            );
    print_r($data);
    $sign = MakeSign($data, 'HMAC-SHA256');
    $data['sign'] = $sign;
    $xml = ToXml($data);
    //单次分账，同时解冻资金给商户
    $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharing';
    $response = postXmlCurl($xml, $url, true, 6);
    echo $response.PHP_EOL;
    $retData = FromXml($response);
    if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
      $now = date('Y-m-d H:i:s');
      $sql = "INSERT INTO mch_marketing_share_profits (mch_id, profit_type, out_trade_no, transaction_id, out_order_no, cash_fee, profit_fee, created_at) VALUES ($subMchId, '$profitType', '$outTradeNo', '$transactionId', '$outOrderNo', $cashFee, $profitFee, '$now')";
      echo $sql.PHP_EOL;
      $db->query($sql);
    }
  }

  //团购分销后分账
  function distributeProfitsharing($distributeId, $buyTotal, $appId, $cashFee, $transactionId, $transactionOpenId)
  {
    require_once dirname(__FILE__).'/lib/WxPay.Config.php';
    require_once dirname(__FILE__).'/lib/WxPay.Exception.php';
    global $db;
    $sql = "SELECT mch_id, groupon_id, openid, distribute_bonus FROM mch_groupon_distributes WHERE id = $distributeId";
    $row = $db->fetch_row($sql);
    $mchId = $row['mch_id'];
    $grouponId = $row['groupon_id'];
    $openId= $row['openid'];
    $bonusFee = $row['distribute_bonus'] * $buyTotal * 100;
    if (!$bonusFee) {
      return false;
    }

    $sql = "SELECT nickname FROM apps WHERE mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $merchantName = $row['nickname'];

    $outTradeNo = getOutTradeNo();
    $receivers  = array(
                    'type' => 'PERSONAL_SUB_OPENID',
                    'account' => $openId,
                    'amount'  => $bonusFee,
                    'description' => '团购分销佣金'
                  );
    $data = array(
                'appid'  => KEYOU_MP_APP_ID,
                'mch_id'  => MCH_ID,
                'sub_appid'  => $appId,
                'sub_mch_id' => $mchId,
                'nonce_str'  => getNonceStr(),
                'sign_type'  => 'HMAC-SHA256',
                'transaction_id' => $transactionId,
                'out_order_no'   => $outTradeNo,
                'receivers'     => json_encode(array($receivers))
            );
    print_r($data);
    $sign = MakeSign($data, 'HMAC-SHA256');
    $data['sign'] = $sign;
    $xml = ToXml($data);
    //统一下单
    $url = 'https://api.mch.weixin.qq.com/secapi/pay/multiprofitsharing';
    $response = postXmlCurl($xml, $url, true, 6);
    $retData = FromXml($response);
    if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
      $sql = "UPDATE wechat_groupon_pays SET distribute_fee = $bonusFee WHERE mch_id = $mchId AND transaction_id = '$transactionId'";
      $db->query($sql);
      
      $bonus = $bonusFee/100;
      $distributeRevenue = ($cashFee - $bonusFee)/100;
      $sql = "UPDATE mch_groupons SET distribute_sold = distribute_sold + $buyTotal, distribute_bonus_total = distribute_bonus_total + $bonus, distribute_revenue = distribute_revenue + $distributeRevenue WHERE id = $grouponId";
      $db->query($sql);

      $trade = $cashFee/100;
      $sql = "UPDATE mch_groupon_distributes SET pays = pays + 1, bonus = bonus + $bonus, total_trade = total_trade + $trade WHERE id = $distributeId";
      $db->query($sql);

      $today = date('Y-m-d');
      $sql = "UPDATE wechat_groupon_pays_today SET revenue = revenue - $bonusFee, distribute_bonus = distribute_bonus + $bonusFee WHERE mch_id = $mchId AND date_at = '$today' LIMIT 1";
      $db->query($sql);

      $sql = "SELECT nickname, headimgurl FROM members WHERE mch_id = $mchId AND sub_openid = '$transactionOpenId'";
      $row = $db->fetch_row($sql);
      $nickname = $row['nickname'];
      $headimgurl = $row['headimgurl'];

      $sql = "SELECT title FROM mch_groupons WHERE id = $grouponId";
      $row = $db->fetch_row($sql);
      $grouponTitle = $row['title'];

      $now = date('Y-m-d H:i:s');
      $sql = "INSERT INTO member_distribute_bonus_list (mch_id, groupon_id, groupon_title, distribute_id, openid, bonus, transaction_id, transaction_openid, nickname, headimgurl, created_at) VALUES ($mchId, $grouponId, '$grouponTitle', $distributeId, '$openId', $bonus, $transactionId, '$transactionOpenId', '$nickname', '$headimgurl', '$now')";
      $db->query($sql);
    }
  }

  //团购完结分账，解冻资金
  function profitsharingfinish($transactionId, $mchId)
  {
    require_once dirname(__FILE__).'/lib/WxPay.Config.php';
    require_once dirname(__FILE__).'/lib/WxPay.Exception.php';
    $outTradeNo = getOutTradeNo();
    $data = array(
                'appid'  => KEYOU_MP_APP_ID,
                'mch_id'  => MCH_ID,
                'sub_mch_id' => $mchId,
                'nonce_str'  => getNonceStr(),
                'sign_type'  => 'HMAC-SHA256',
                'transaction_id' => $transactionId,
                'out_order_no'   => $outTradeNo,
                'description'    => '分账已完成'
            );
    $sign = MakeSign($data, 'HMAC-SHA256');
    $data['sign'] = $sign;
    $xml = ToXml($data);
    //统一下单
    $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish';
    $response = postXmlCurl($xml, $url, true, 6);
    $retData = FromXml($response);
    if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
    } else {
      echo 'profitsharingfinish failed '.$transactionId.PHP_EOL;
    }
  }
