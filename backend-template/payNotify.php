<?php
  require_once 'const.php';
  require_once "lib/WxPay.Api.php";
  require_once 'lib/WxPay.Notify.php';
  require_once 'lib/function.php';
  require_once 'unit/log.php';

  //初始化日志
  $logHandler= new CLogFileHandler("/mnt/tmp/pay_logs/".date('Ymd').'.log');
  $log = Log::Init($logHandler, 15);

  class PayNotifyCallBack extends WxPayNotify
  {
    //重写回调处理函数
    public function NotifyProcess($data, &$msg)
    {
      Log::DEBUG("call back:" . json_encode($data));
      $notfiyOutput = array();
      
      if(!array_key_exists("transaction_id", $data)){
        $msg = "输入参数不正确";
        return false;
      }

      $redis = new Redis();
      $redis->connect(REDIS_HOST, REDIS_PORT);
      $redis->auth(REDIS_PASSWORD);
      $redis->select(REDIS_DB);

      $attach  = explode(',', $data['attach']);
      $payType = $attach[0];
      $key     = $attach[1];
      $prepayId = $redis->hget('keyou_prepay_ids', $key);

      $appId  = $data['sub_appid'];
      $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $data['wx_access_token']= $accessToken;
      $data['mini_access_token'] = $miniAccessToken;
      $data['prepay_id']      = $prepayId;
      $data['pay_type']       = 1;
      $createdAt = getDateAt($data['time_end']);
      $date = date('Ymd', strtotime($createdAt));

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
        //分离会员操作
        if ($data['is_member']) {
          $data['job'] = 'update_member';
          $redis->rpush('member_job_list', serialize($data));
        }
        
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
      } else if ('groupon' == $payType) {
        $redis->rpush('keyou_trade_list', serialize($data));

        $data['job'] = 'update_member_groupon';
        $redis->rpush('member_job_list', serialize($data));
        $redis->hset('keyou_groupon_history_'.$date, $key, serialize($data));

        $redis->zAdd('keyou_groupon_profit_share_list', time()+30, $key);
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
      return true;
    }

    public function sendWebsocketMessage($channel, $message)
    {
      $channel = $channel;
      $url     = 'http://sinaapp.keyouxinxi.com/send_message.php?channel='.$channel.'&message='.$message;
      $ret     = httpPost($url);
    }
  }

  Log::DEBUG("begin notify");
  $notify = new PayNotifyCallBack();
  $notify->Handle(false);
