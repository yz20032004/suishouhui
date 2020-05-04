<?php
  require_once 'common.php';
  require_once 'unit/log.php';
  error_reporting(0);
  //初始化日志
  $logHandler= new CLogFileHandler("/mnt/tmp/alipay_logs/".date('Ymd').'.log');
  $log = Log::Init($logHandler, 15);

  Log::DEBUG("call back:" . json_encode($_POST));
  //Log::DEBUG("call input:" . json_encode(file_get_contents('php://input')));
  $responseData = $_POST;
  if ('TRADE_SUCCESS' != $responseData['trade_status']) {
    echo 'success';
    exit();
  }

  $authAppId = $responseData['auth_app_id'];
  $sql = "SELECT mch_id FROM mchs WHERE alipay_app_id = '$authAppId'";
  $row = $db->fetch_row($sql);
  $mchId = $row['mch_id'];

  $message= array(
        'trade'        => $responseData['total_amount'],
        'get_point'    => 0,
        'consume'      => $responseData['receipt_amount'],
        'use_coupon_id'=> 0,
        'use_coupon_amount' => 0,
        'use_coupon_name'   => '',
        'consume_recharge'  => 0,
        'consume_point'     => 0,
        'point_amount'      => 0,
        'use_point'         => 0,
        'reduce'            => 0,
        'save'              => 0,
        'discount'          => 0,
        'member_discount'   => 0,
        'prepay_id'         => 0,
        'pay_action'        => 'general'
      );

  $key = substr(md5($mchId.$responseData['out_trade_no']), 8, 16);
  $redis->hset('keyou_pay_result', $key, serialize($message));

  $fundBillList = json_decode($responseData['fund_bill_list'], true);
  $alipayData = array(
                  'sub_appid' => 'wxaa02c1c97542b1e4',
                  'sub_mch_id'=> $mchId,
                  'pay_type'  => 2,
                  'total_fee' => $responseData['invoice_amount']*100,
                  'cash_fee'  => $responseData['buyer_pay_amount'] * 100,
                  'attach'    => implode(',', array('pay', $key)),
                  'pay_info'  => $redis->hget('keyou_pay_qrcodes', $key),
                  'is_member' => false,
                  'openid'       => $responseData['buyer_id'],
                  'sub_openid'   => $responseData['buyer_logon_id'],
                  'out_trade_no' => $responseData['out_trade_no'],
                  'transaction_id' => $responseData['trade_no'],
                  'bank_type'      => $fundBillList[0]['fundChannel'],
                  'time_end'       => date('YmdHis', strtotime($responseData['gmt_payment'])),
                );
  $data = array_merge($alipayData, $message);
  //分离入库操作
  $redis->rpush('keyou_trade_list', serialize($data));

  $payInfo   = explode('#', $redis->hget('keyou_pay_qrcodes', $key));
  $soundData = array(
                    'job'     => 'send_sound',
                    'get_point' => 0,
                    'trade_no'=> $responseData['out_trade_no'],
                    'trade'   => $responseData['total_amount'],
                    'counter' => $payInfo[2],
                    'save'    => 0,
                    'consume_recharge' => 0,
                    'pay_type' => 'alipay'
                  );
  $redis->rpush('keyou_mch_job_list', serialize($soundData));

  $channel = 'alipay_'.$mchId.$responseData['out_trade_no'];
  $pushMessage = array('pay'=>'complete');
  sendWebsocketMessage($channel, json_encode($pushMessage));

  echo 'success';

  function sendWebsocketMessage($channel, $message)
  {
    $channel = $channel;
    $url     = 'http://sinaapp.keyouxinxi.com/send_message.php?channel='.$channel.'&message='.$message;
    $ret     = httpPost($url);
  }
