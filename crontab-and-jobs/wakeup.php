<?php
  //沉睡会员唤醒
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';
  //$db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db = new DB(DBHOST, DBUSER, DBPASS, 'suishouhui_test');
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);

  $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');

  $sql = "SELECT * FROM campaigns WHERE campaign_type = 'wakeup' AND is_stop = 0 ORDER BY id DESC";
  $data = $db->fetch_array($sql);
  foreach ($data as $row) {
    $mchId     = $row['mch_id'];
    $wakeupDay = $row['day'];
    $couponId  = $row['coupon_id'];
    $couponTotal = $row['coupon_total'];
    $smsTemplateId = $row['sms_template_id'];
    $smsParam  = $row['sms_params'];
    $wakeupTitle = $row['title'];

    //查询商户短信余额
    $sql = "SELECT merchant_name, mobile, sms_total FROM users WHERE mch_id = $mchId AND is_admin = 1";
    $ret = $db->fetch_row($sql);
    $merchantName = $ret['merchant_name'];
    $adminMobile = $ret['mobile'];
    $smsBalance  = $ret['sms_total'];

    $sql = "SELECT * FROM coupons WHERE id = $couponId";
    $ret = $db->fetch_row($sql);
    $cardId     = $ret['wechat_cardid'];
    $couponName = $ret['name'];
    $amount     = $ret['amount'];
    $discount   = $ret['discount'];
    $detail     = $ret['description'];
    $consumeLimit = $ret['consume_limit'];
    $couponType   = $ret['coupon_type'];

    if ('relative' == $ret['validity_type']) {
      $dateStart = $ret['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
      $dateEnd   = date('Y-m-d', strtotime($dateStart.'+'.$ret['total_days'].' days'));  
    } else {
      $dateStart = $ret['date_start'];
      $dateEnd    = $ret['date_end'];
    }

    $sendCouponTotal = $memberTotal = $smsTotal = 0;
    $needSendDate = date('Y-m-d', strtotime("-$wakeupDay days"));
    $sql = "SELECT sub_openid, member_cardid, cardnum, mobile, coupons FROM members WHERE mch_id = $mchId AND date_format(last_consume_at, '%Y-%m-%d') = '$needSendDate'";
    $memberData = $db->fetch_array($sql);
    foreach ($memberData as $v) {
      $subOpenId    = $v['sub_openid'];
      $memberCardId = $v['member_cardid'];
      $cardNum      = $v['cardnum'];
      $mobile       = $v['mobile'];
      $coupons      = $v['coupons'];

      $data = array(
                    'job'          => 'send_member_coupon', 
                    'wx_access_token' => $accessToken,
                    'mch_id'       => $mchId,
                    'sub_openid'   => $subOpenId,
                    'coupon_id'    => $couponId,
                    'coupon_total' => $couponTotal,
                    'coupon_name'  => $couponName,
                    'amount'       => $amount,
                    'discount'     => $discount,
                    'detail'       => $detail,
                    'consume_limit'=> $consumeLimit,
                    'coupon_type'  => $couponType,
                    'date_start'   => $dateStart,
                    'date_end'     => $dateEnd,
                    'cardnum'      => $cardNum,
                    'member_cardid'=> $memberCardId,
                    'coupons'      => $coupons
                    );
      $redis->rpush('member_job_list', serialize($data));
      $sendCouponTotal += $couponTotal;

      $memberTotal++;
      //群发短信通知,如果smsTemplateId有值，代表需要发送短信，否则商户不要求发送短信
      if ($smsTemplateId && $smsBalance > 0) {
        $smsData = array('template_code'=>$smsTemplateId, 'sms_params'=>$smsParam, 'mobile'=>$mobile);
        $redis->rpush('keyou_sms_job_list', serialize($smsData));
        $smsTotal++;
        $smsBalance--;
      }
    }
    //短信发送商户活动执行结果
    $smsParamData = array('shop'=>$merchantName, 'wakeup'=>$wakeupTitle, 'total'=>$memberTotal);
    $smsParams    = json_encode($smsParamData, JSON_UNESCAPED_UNICODE);
    $smsData = array('template_code'=>'SMS_171670012', 'sms_params'=>$smsParams, 'mobile'=>$adminMobile);
    $redis->rpush('keyou_sms_job_list', serialize($smsData));

    //扣除商户短信余额
    $sql = "UPDATE users SET sms_total = sms_total - $smsTotal WHERE mch_id = $mchId AND is_admin = 1";
    $db->query($sql);

    $sql = "UPDATE coupons SET balance = balance - $sendCouponTotal WHERE id = $couponId";
    $db->query($sql);

    echo $merchantName . $wakeupTitle . ' end, send '.$memberTotal.'人，sendsms '.$smsTotal.'条；'.date('Y-m-d H:i:s').PHP_EOL;
  }
