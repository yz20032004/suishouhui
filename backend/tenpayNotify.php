<?php
  /*
   * 腾讯云支付的支付结果事件通知
   */
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
  $tradeType   = $orderContent['trade_type']; //8=>扫码支付
  
  $date = date('Ymd', $orderContent['create_time']);
  $key  = substr(md5($orderContent['out_trade_no']), 8,16);

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
            'attach'=> 'pay,'.$key,
            'bank_type' => $bankType,
            'cash_fee'  => $orderContent['cash_fee'],
            'fee_type'  => '8' == $payPlatForm ? $orderContent['cash_fee_type'] : '',
            'mch_id'    => MCHID,
            'openid'    => $openId,
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
            'key'      => $key,
            'get_point'=> $getPoint,
            'reduce'   => 0,
            'save'     => 0,
            'discount' => 0,
            'member_discount' => 0,
            'is_member' => $isMember,
            'pay_action' => 'self',
          );
  $redis->rpush('keyou_trade_list', serialize($data));
  $redis->hset('keyou_trade_history_'.$date, $key, serialize($data));
  //分离会员操作
  $data['job'] = 'update_member';
  $redis->rpush('member_job_list', serialize($data));
