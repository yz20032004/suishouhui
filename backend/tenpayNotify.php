<?php
  require_once 'common.php';
  require_once 'unit/log.php';

  //初始化日志
  $logHandler= new CLogFileHandler("/mnt/tmp/pay_logs/".date('Ymd').'.log');
  $log = Log::Init($logHandler, 15);

  $message = file_get_contents('php://input');
  Log::DEBUG("Tenpay call back:" .$message);

  $data = json_decode($message, true);
  $requestData = json_decode($data['request_content'], true);
  $orderClient = $requestData['order_client'];
  $orderContent= $requestData['order_content'];
  $payMchKey   = $requestData['pay_mch_key'];
  $outShopId   = $payMchKey['out_shop_id'];
  $trade   = $orderContent['total_fee']/100;
  $tradeType   = $orderContent['trade_type']; //8=>扫码支付 1=>刷卡支付, 9=>小程序支付
  $attach  = $orderContent['wxpay_order_content_ext']['attach'];
  
  $date = date('Ymd', $orderContent['create_time']);
  $payPlatForm = $payMchKey['pay_platform'];
  if ('1' == $payPlatForm) {
    //微信支付
    $payType = 1;
    $openId  = $payMchKey['wxpay_pay_mch_key_ext']['open_id'];
    $subMchId= $payMchKey['wxpay_pay_mch_key_ext']['sub_mch_id'];
    $bankType=  $orderContent['wxpay_order_content_ext']['bank_type'];
    $prepayId = '8'==$tradeType ? $orderContent['wxpay_order_content_ext']['prepare_id'] : ''; 

    $sql  = "SELECT id, sub_openid, member_cardid, cardnum, grade FROM members WHERE openid = '$openId' AND mch_id = $subMchId";
    $ret = $db->fetch_row($sql);
    if ($ret) {
      $isMember = true;
      $subOpenId = $ret['sub_openid'];
      $sql = "SELECT point_speed FROM app_grades WHERE mch_id = $subMchId";
      $row = $db->fetch_row($sql);
      $pointSpeed = $row['point_speed'];
      $pointRules = unserialize($redis->hget('keyou_mch_point_rules', $subMchId));
      $getPoint = floor($pointSpeed * $trade / $pointRules['award_need_consume']);
    } else {
      $subOpenId = '';
      $isMember = false;
      $getPoint = 0;
    }
  } else {
    //支付宝支付
    $payType = 2;
    $openId = $payMchKey['alipay_pay_mch_key_ext']['user_id'];
    $subOpenId   = '8' == $tradeType ? $orderContent['alipay_order_content_ext']['buyer_logon_id'] : '';
    $sql = "SELECT mch_id, counter FROM app_counters WHERE tenpay_shopid = '$outShopId'";
    $row = $db->fetch_row($sql);
    $counter  = $row['counter'];
    $subMchId = $row['mch_id'];
    $bankType = $orderContent['alipay_order_content_ext']['fund_bill_list'][0]['fund_channel'];
    $prepayId = '';
    $isMember = false;
    $getPoint = 0;

    if ('8' == $tradeType) {
      $soundData = array(
                        'job'     => 'send_sound',
                        'trade_no'=> $orderContent['out_trade_no'],
                        'trade'   => $trade,
                        'get_point' => 0,
                        'counter' => $counter,
                        'save'    => 0,
                        'consume_recharge' => 0,
                        'pay_type' => 'alipay'
                      );
      $redis->rpush('keyou_mch_job_list', serialize($soundData));
    }
  }
  $payInfo = $subMchId.'#'.$trade.'#'.$orderClient['device_id'].'#'.$orderContent['body'].'#0';

  $sql = "SELECT appid FROM mchs WHERE mch_id = $subMchId";
  $row = $db->fetch_row($sql);
  $subAppId = $row['appid'];

  $data = array(
            'appid' => KEYOU_MP_APP_ID,
            'bank_type' => $bankType,
            'cash_fee'  => $orderContent['cash_fee'],
            'fee_type'  => '8' == $payPlatForm ? $orderContent['cash_fee_type'] : '',
            'mch_id'    => MCHID,
            'openid'    => $openId,
            'attach'    => $attach,
            'out_trade_no' => $orderContent['out_trade_no'],
            'sub_appid' => $subAppId,
            'sub_mch_id'=> $subMchId,
            'sub_openid'=> $subOpenId,
            'total_fee' => $orderContent['total_fee'],
            'transaction_id' => $orderContent['transaction_id'],
            'prepay_id'      => $prepayId,
            'wx_access_token'=> $redis->hget('keyouxinxi', 'wx_access_token'),
            'mini_access_token'=> $redis->hget('keyou_mini', 'access_token'),
            'time_end' => date('YmdHis', $orderContent['time_end']),
            'trade_type' => 'MICROPAY',
            'pay_type' => $payType,
            'pay_info' => $payInfo,
            'trade'    => $trade,
            'consume'  => $trade,
            'get_point'=> $getPoint,
            'reduce'   => 0,
            'save'     => 0,
            'discount' => 0,
            'member_discount' => 0,
            'is_member' => $isMember,
            'pay_action' => 'self',
          );
  if ('9' == $tradeType) {
    //小程序支付
    $attachData = explode(',', $attach);
    $payType = $attachData[0];
    $key     = $attachData[1];
    if ('pay' == $payType) {
      $data['pay_info'] = $redis->hget('keyou_pay_qrcodes', $key);
      $payResult = unserialize($redis->hget('keyou_pay_result', $key));
      $payResult['sub_openid'] = $payResult['openid'];
      $payResult['openid']     = $data['openid'];
      $payResult['coupon_fee'] = isset($data['coupon_fee'])?$data['coupon_fee']:0;
      $redis->hset('keyou_pay_result', $key, serialize($payResult));

      $data = array_merge($data, $payResult);

      //分离入库操作
      $redis->rpush('keyou_trade_list', serialize($data));

      $redis->hset('keyou_trade_history_'.$date, $key, serialize($data));
      file_put_contents('/tmp/mchsql', $key.PHP_EOL, FILE_APPEND);
      //分离会员操作
      $data['job'] = 'update_member';
      $redis->rpush('member_job_list', serialize($data));
      
      $payInfo   = explode('#', $data['pay_info']);
      $soundData = array(
                        'job'     => 'send_sound',
                        'trade_no'=> $data['out_trade_no'],
                        'trade'   => $payResult['trade'],
                        'get_point' => $data['get_point'],
                        'counter' => $payInfo[2],
                        'save'    => $payResult['save'],
                        'consume_recharge' => isset($payResult['consume_recharge'])?$payResult['consume_recharge']:0,
                        'pay_type'=> 'wechat'
                      );
      $redis->rpush('keyou_mch_job_list', serialize($soundData));
      $redis->hdel('keyou_pay_qrcodes', $key);

      if (isset($data['pay_action'])) {
        if ('waimai' == $data['pay_action']) {
          $data['job'] = 'update_member_waimai';
          $data['guanjia_access_token'] = $redis->hget('keyou_mini', 'guanjia_access_token');
          $redis->rpush('member_job_list', serialize($data));

          $redis->zAdd('keyou_waimai_profit_share_list', time()+30, $key);
        } else if ('mall' == $data['pay_action']) {
          $data['job'] = 'update_member_mall';
          $data['guanjia_access_token'] = $redis->hget('keyou_mini', 'guanjia_access_token');
          $redis->rpush('member_job_list', serialize($data));
        } else if ('ordering' == $data['pay_action']) {
          $data['job'] = 'update_member_ordering';
          $data['guanjia_access_token'] = $redis->hget('keyou_mini', 'guanjia_access_token');
          $redis->rpush('member_job_list', serialize($data));
        }
      }
    } else if ('rechargenopay' == $payType) {
      //储值N倍免单，先储值，再消费
      $redis->rpush('keyou_trade_list', serialize($data));
      $redis->hset('keyou_trade_history_'.$date, $key, serialize($data));

      $data['job'] = 'update_member_rechargenopay';
      $redis->rpush('member_job_list', serialize($data));
      $redis->hset('keyou_recharge_result', $key, serialize($data));

      $data['job'] = 'send_sound_rechargenopay';
      $redis->rpush('keyou_mch_job_list', serialize($data));
    } else if ('recharge' == $payType) { 
      $redis->rpush('keyou_trade_list', serialize($data));

      $data['job'] = 'update_member_recharge';
      $redis->rpush('member_job_list', serialize($data));
      $redis->hset('keyou_recharge_result', $key, serialize($data));
    } else if ('sms' == $payType) {
      $redis->rpush('keyou_trade_list', serialize($data));
    } else if ('function' == $payType) {
      $data['job'] = 'update_mch_function';
      $redis->rpush('keyou_mch_job_list', serialize($data));
    } else if ('groupon' == $payType) {
      $redis->rpush('keyou_trade_list', serialize($data));

      $data['job'] = 'update_member_groupon';
      $redis->rpush('member_job_list', serialize($data));
      $redis->hset('keyou_groupon_history_'.$date, $key, serialize($data));
    } else if ('together' == $payType) {
      $redis->rpush('keyou_trade_list', serialize($data));

      sleep(2);
      //让数据先执行插入操作，防止后面提前计算拼团人数而造成数据偏差
      $data['job'] = 'update_member_together';
      $redis->rpush('member_job_list', serialize($data));
      $redis->hset('keyou_together_history_'.$date, $key, serialize($data));

    } else if ('vipcard' == $payType) {
      $redis->rpush('keyou_trade_list', serialize($data));

      $data['job'] = 'update_member_vipcard';
      $redis->rpush('member_job_list', serialize($data));
      $redis->hset('keyou_vipcard_history_'.$date, $key, serialize($data));
    }
  } else {
    //腾讯云支付的扫码支付、刷卡支付
    $key  = substr(md5($orderContent['out_trade_no']), 8,16);
    $data['attach'] = 'pay,'.$key;
    $redis->rpush('keyou_trade_list', serialize($data));
    $redis->hset('keyou_trade_history_'.$date, $key, serialize($data));
    //分离会员操作
    $data['job'] = 'update_member';
    $redis->rpush('member_job_list', serialize($data));
  }
