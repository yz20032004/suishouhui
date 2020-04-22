<?php
  //营销活动执行脚本,循环读取活动，推送到redis里去执行
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';
  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);

  $now = date('Y-m-d H:i:00');
  $sql  = "SELECT id, campaign_type, mch_id, grade, title, coupon_id, coupon_total, detail, sms_params, sms_template_id, created_at FROM campaigns WHERE campaign_type IN ('send_sms', 'send_coupon') AND status = 1 AND is_stop = 0 AND date_format(send_at, '%Y-%m-%d %H:%i:00') = '$now' ORDER BY send_at";
  $data = $db->fetch_array($sql);
  foreach ($data as $row) {
    if ($row['id']) {
      $campaignId = $row['id'];
      $sql = "UPDATE campaigns SET is_stop = 1 WHERE id = $campaignId";
      $db->query($sql);

      $campaignType = $row['campaign_type'];
      $mchId      = $row['mch_id'];
      $grade      = $row['grade'];
      $createdAt  = $row['created_at'];
      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      if ('send_sms' == $row['campaign_type']) { 
        $smsTempId = $row['sms_template_id'];
        $smsParam  = $row['sms_params'];

        $smsTotal = 0;
        $sql = "SELECT mobile FROM members WHERE mch_id = $mchId AND mobile != ''";
        if ($grade) {
          $sql .=" AND grade = $grade";
        }
        $memberData = $db->fetch_array($sql);
        foreach ($memberData as $v) {
          $smsData = array('template_code'=>$smsTempId, 'sms_params'=>$smsParam, 'mobile'=>$v['mobile']);
          $redis->rpush('keyou_sms_job_list', serialize($smsData));
          $smsTotal++;
        }
        //短信也发送给商户管理员
        $sql = "SELECT mobile FROM users WHERE mch_id = $mchId AND is_admin = 1";
        $ret = $db->fetch_row($sql);
        $mobile = $ret['mobile'];
        $smsData = array('template_code'=>$smsTempId, 'sms_params'=>$smsParam, 'mobile'=>$mobile);
        $redis->rpush('keyou_sms_job_list', serialize($smsData));
        $smsTotal++;

        //扣除商户短信余额
        $sql = "UPDATE users SET sms_total = sms_total - $smsTotal WHERE mch_id = $mchId AND is_admin = 1";
        $db->query($sql);
      } else if ('send_coupon' == $row['campaign_type']) {
        $couponId    = $row['coupon_id'];
        $couponTotal = $row['coupon_total'];

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

        $sendCouponTotal = 0;

        $sql = "SELECT sub_openid, member_cardid, cardnum, coupons FROM members WHERE mch_id = $mchId ";
        if ($grade) {
          $sql .= " AND grade = $grade";
        }
        $sql .= " AND created_at <= '$createdAt'";
        $memberData = $db->fetch_array($sql);
        foreach ($memberData as $v) {
          $subOpenId = $v['sub_openid'];
          $memberCardId = $v['member_cardid'];
          $cardNum      = $v['cardnum'];
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
        }

        $sql = "UPDATE coupons SET balance = balance - $sendCouponTotal WHERE id = $couponId";
        $db->query($sql);
      }
      echo 'Mch#'.$mchId.' '.$campaignType.' on '.date('Y-m-d H:i:s').PHP_EOL;
    }
  }
