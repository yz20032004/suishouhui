<?php
  /*
   * 会员数据操作
   */
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';
  ini_set('default_socket_timeout', -1);

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  use OSS\OssClient;
  use OSS\Core\OssException;
  require_once dirname(__FILE__).'/lib/aliyun-oss-php-sdk-2.2.3.phar';

  while (true) {
    $result = $redis->blpop('member_job_list', 1800);
    if ($result) {
      $message = unserialize($result[1]);
      $job  = $message['job'];
      switch ($job) {
        case 'update_member':
          update_member($message);
          break;
        case 'update_member_opengift':
          update_member_opengift($message);
          break;
        case 'update_member_recharge':
          update_member_recharge($message);
          break;
        case 'update_member_groupon':
          update_member_groupon($message);
          break;
        case 'update_member_waimai':
          update_member_waimai($message);
          break;
        case 'update_member_mall':
          update_member_mall($message);
          break;
        case 'update_member_ordering':
          update_member_ordering($message);
          break;
        case 'update_member_together':
          update_member_together($message);
          break;
        case 'send_point_exchange_notice':
          send_point_exchange_notice($message);
          break;
        case 'update_member_point':
          update_member_point($message);
          break;
        case 'update_member_card':
          update_member_card($message);
          break;
        case 'update_member_vipcard':
          update_member_vipcard($message);
          break;
        case 'send_member_coupon':
          send_member_coupon($message);
          break;
        case 'consume_coupon':
          consume_coupon($message);
          break;
        case 'groupon_refund':
          groupon_refund($message);
          break;
        case 'trade_refund':
          trade_refund($message);
          break;
        case 'update_member_rechargenopay':
          update_member_rechargenopay($message);
          break;
        case 'print_ordering_receipt':
          print_ordering_receipt($message);
          break;
        case 'print_ordering_append_receipt':
          print_ordering_append_receipt($message);
          break;
        default:
          break;
      }
    }
  }

  //购买权益卡升级
  function update_member_vipcard($data)
  {
    global $db;
    $mchId     = $data['sub_mch_id'];
    $subOpenId = $data['sub_openid'];
    $accessToken = $data['wx_access_token'];
    $now = date('Y-m-d H:i:s');

    $attach      = explode(',', $data['attach']);
    $grade       = $attach[2];
    //获取初始会员卡等级
    $sql = "SELECT name, valid_days, catch_value FROM app_grades WHERE mch_id = $mchId AND grade = $grade";
    $row = $db->fetch_row($sql);
    $gradeTitle = $row['name'];
    $validDays  = $row['valid_days'];
    $catchValue = $row['catch_value'];
    if ($validDays > 0) {
      $expiredAt = date('Y-m-d 23:59:59', strtotime('+ '.$validDays.'days'));
    } else {
      $expiredAt = '0000-00-00 00:00:00';
    }

    $sql = "UPDATE members SET grade = $grade, grade_title = '$gradeTitle', upgrade_at = '$now', expired_at = '$expiredAt' WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
    $db->query($sql);

    //执行开卡礼
    $sql = "SELECT member_cardid, cardnum, coupons, mobile, name FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
    $row = $db->fetch_row($sql);
    $cardId    = $row['member_cardid'];
    $cardNum   = $row['cardnum'];
    $couponTotal = $row['coupons'];
    $memberName  = $row['name'];
    $mobile      = $row['mobile'];

    $sql = "SELECT coupon_id, coupon_total FROM app_opengifts WHERE mch_id = $mchId AND grade = $grade";
    $data = $db->fetch_array($sql);
    foreach ($data as $row) {
      if ($row['coupon_id']) {
        $couponId = $row['coupon_id'];
        $total    = $row['coupon_total'];
        $couponTotal += $total;

        $sql = "SELECT * FROM coupons WHERE id = $couponId";
        $ret = $db->fetch_row($sql);
        $couponName = $ret['name'];
        $amount     = $ret['amount'];
        $discount   = $ret['discount'];
        $detail     = $ret['description'];
        $consumeLimit = $ret['consume_limit'];
        $couponType   = $ret['coupon_type'];

        if ('relative' == $ret['validity_type']) {
          $dateStart = $ret['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
          $totalDays = $ret['total_days'] - 1;
          $dateEnd   = date('Y-m-d', strtotime($dateStart.'+'.$totalDays.' days'));  
        } else {
          $dateStart = $ret['date_start'];
          $dateEnd    = $ret['date_end'];
        }

        for($i=0;$i<$total;$i++) {
          $code = mt_rand(1000000000, 9999999999);
          $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, code, amount, discount, consume_limit, detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$subOpenId', '$couponId', '$couponType', '$couponName', '$code', '$amount', '$discount', '$consumeLimit', '$detail', '$dateStart', '$dateEnd', 'opencard', '$now')";
          $db->query($sql);
        }
        $sql = "UPDATE coupons SET balance = balance - $total WHERE id = $couponId";
        $db->query($sql);

        $sql = "UPDATE members SET coupons = coupons + $total WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
        $db->query($sql);
      }
    }

    //通知提醒
    $paramData  = array(
                        'first' => '姓名：'.$memberName,
                        'keyword1' => $mobile,
                        'keyword2' => $gradeTitle,
                        'keyword3' => $now,
                        'remark'   => '售卡收入'.$catchValue.'元'
                      );
    $remindData = array(
                    'mch_id' => $mchId,
                    'remind_type'     => 'is_vipcard',
                    'wx_access_token' => $accessToken,
                    'template_id'     => '-JwqWuMVnxMfHziW34Z37sBS5DNkghVTetfFyZSBqME',
                    'page_path'       => 'pages/member/detail?openid='.$subOpenId,
                    'param_data'      => $paramData
                  );
    send_user_remind($remindData);

    if (!$cardNum || !$cardId) {
      return false;
    }
    $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
    $data = array(
              'code'              => $cardNum,
              'card_id'           => $cardId,
              'custom_field_value1' => $couponTotal.urlencode('张'),
              'custom_field_value2' => urlencode($gradeTitle),
              'notify_optional' => array(
                                          'is_notify_custom_field1' => true,
                                          'is_notify_custom_field2' => true
                                        )
            );
    $ret = httpPost($url, urldecode(json_encode($data)));
    echo 'update vipcard result '.$ret.PHP_EOL;
  }

  //执行开卡礼
  function update_member_opengift($data)
  {
    global $db;
    $mchId     = $data['mch_id'];
    $accessToken = $data['access_token'];
    $code        = $data['code'];
    $point       = $data['point'];
    $now = date('Y-m-d H:i:s');

    //获取初始会员卡等级
    $sql = "SELECT name FROM app_grades WHERE mch_id = $mchId AND grade = 1";
    $row = $db->fetch_row($sql);
    $gradeTitle = $row['name'];

    $sql = "UPDATE members SET grade = 1, grade_title = '$gradeTitle', upgrade_at = '$now' WHERE mch_id = $mchId AND cardnum = '$code'";
    $db->query($sql);

    //执行开卡礼 grade=>1普通卡
    $sql = "SELECT sub_openid, member_cardid, cardnum, name, mobile FROM members WHERE mch_id = $mchId AND cardnum = '$code'";
    $row = $db->fetch_row($sql);
    $cardId    = $row['member_cardid'];
    $subOpenId = $row['sub_openid'];
    $memberName= $row['name'];
    $mobile    = $row['mobile'];

    $sql = "SELECT COUNT(id) AS total FROM member_coupons WHERE openid = '$subOpenId' AND mch_id = $mchId AND status = 1";
    $row = $db->fetch_row($sql);

    $couponTotal = $row['total'];
    $today = date('Y-m-d');
    $sql = "SELECT coupon_id, coupon_total FROM app_opengifts WHERE mch_id = $mchId AND grade = 1";
    $data = $db->fetch_array($sql);
    foreach ($data as $row) {
      if ($row['coupon_id']) {
        $couponId = $row['coupon_id'];
        $total    = $row['coupon_total'];
        $couponTotal += $total;

        $sql = "SELECT * FROM coupons WHERE id = $couponId";
        $ret = $db->fetch_row($sql);
        $couponName = $ret['name'];
        $amount     = $ret['amount'];
        $discount   = $ret['discount'];
        $detail     = $ret['description'];
        $consumeLimit = $ret['consume_limit'];
        $couponType   = $ret['coupon_type'];

        if ('relative' == $ret['validity_type']) {
          $dateStart = $ret['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
          $totalDays = $ret['total_days'] - 1;
          $dateEnd   = date('Y-m-d', strtotime($dateStart.'+'.$totalDays.' days'));  
        } else {
          $dateStart = $ret['date_start'];
          $dateEnd    = $ret['date_end'];
        }

        for($i=0;$i<$total;$i++) {
          $couponCode = mt_rand(1000000000, 9999999999);
          $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, code, amount, discount, consume_limit, detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$subOpenId', '$couponId', '$couponType', '$couponName', '$couponCode', '$amount', '$discount', '$consumeLimit', '$detail', '$dateStart', '$dateEnd', 'opencard', '$now')";
          $db->query($sql);
        }
        $sql = "UPDATE coupons SET balance = balance - $total WHERE id = $couponId";
        $db->query($sql);

        $sql = "UPDATE members SET coupons = coupons + $total WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
        $db->query($sql);
      }
    }

    if ($cardId) {
      $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
      $detail = '消费奖励'; 
      $data = array(
                'code'              => $code,
                'card_id'           => $cardId,
                'add_bonus'         => $point,
                'record_bonus'      => urlencode($detail),
                'custom_field_value1' => $couponTotal.urlencode('张'),
                'custom_field_value2' => urlencode($gradeTitle),
                'notify_optional' => array(
                                            'is_notify_bonus' => false,
                                            'is_notify_custom_field1' => true
                                          )
              );
      $ret = httpPost($url, urldecode(json_encode($data)));
      echo 'init member opengift result '.$ret.PHP_EOL;
    }

    //通知提醒
    $paramData  = array(
                        'first' => '',
                        'keyword1' => $mobile,
                        'keyword2' => $gradeTitle,
                        'keyword3' => $now,
                        'remark'   => '姓名：'.$memberName
                      );
    $remindData = array(
                    'mch_id' => $mchId,
                    'remind_type'     => 'is_member',
                    'wx_access_token' => $accessToken,
                    'template_id'     => '-JwqWuMVnxMfHziW34Z37sBS5DNkghVTetfFyZSBqME',
                    'page_path'       => 'pages/member/detail?openid='.$subOpenId,
                    'param_data'      => $paramData
                  );
    send_user_remind($remindData);
  }

  function update_member($data)
  {
    global $db;
    $mchId  = $data['sub_mch_id'];
    $openId = $data['openid'];
    $subOpenId = $data['sub_openid'];
    $isMember  = $data['is_member'];
    $accessToken = $data['wx_access_token'];
    $detail = '';

    $outTradeNo = $data['out_trade_no'];
    $trade    = $data['trade'];
    $getPoint = $data['get_point'];
    $isNotifyBonus = false;
    $consume  = $data['consume'];
    $useCouponId = isset($data['use_coupon_id'])?$data['use_coupon_id']:0;
    $useCouponName   = isset($data['use_coupon_name'])?$data['use_coupon_name']:'';
    $consumeRecharge = isset($data['consume_recharge'])?$data['consume_recharge']:0;
    $consumePoint    = isset($data['consume_point'])?$data['consume_point']:0;
    $pointAmount     = isset($data['point_amount'])?$data['point_amount']:0;
    $usePoint        = isset($data['use_point'])?$data['use_point']:0;
    $useCouponAmount = isset($data['use_coupon_amount'])?$data['use_coupon_amount']:0;
    $awardCouponId   = isset($data['award_coupon_id'])?$data['award_coupon_id']:0;
    $awardCouponName = isset($data['award_coupon_name'])?$data['award_coupon_name']:'';
    $awardCouponTotal= isset($data['award_coupon_total'])?$data['award_coupon_total']:0;
    $wechatCashCouponId = isset($data['coupon_id_0']) ? $data['coupon_id_0'] : 0; //微信预充值/免充值券ID
    $isPayBuyCoupon  = isset($data['is_paybuycoupon']) ? $data['is_paybuycoupon'] : 0;

    //支付时加价购券
    if ($isPayBuyCoupon) {
      update_member_paybuycoupon($data);
    }

    $createdAt = getDateAt($data['time_end']);
    if ('MICROPAY' == $data['trade_type'] || 'CASHPAY' == $data['trade_type']) {
      $isNotifyBonus = true;
      /*$sql = "SELECT grade FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      if ($row['grade'] > 0) {
        $sql = "SELECT award_need_consume FROM app_point_rules WHERE mch_id = $mchId";
        $pointRules = $db->fetch_row($sql);
        $sql = "SELECT name, discount, point_speed FROM app_grades WHERE mch_id = $mchId AND grade = $row[grade]";
        $ret = $db->fetch_row($sql);
        if ($ret['point_speed']) {
          $pointSpeed = round($ret['point_speed'], 1);
          $getPoint = floor($pointSpeed * $consume / $pointRules['award_need_consume']);
        }
      }*/
    }
    $modifyPoint = $getPoint - $consumePoint;

    if ($useCouponId) {
      $sql = "SELECT id, code FROM member_coupons WHERE mch_id = $mchId AND coupon_id = $useCouponId AND openid = '$subOpenId' AND status = 1 LIMIT 1";
      $row = $db->fetch_row($sql);
      $id  = $row['id'];
      $code = $row['code'];
      $sql = "UPDATE member_coupons SET status = 0, amount = $useCouponAmount, updated_at = '$createdAt' WHERE id = $id";
      $db->query($sql);

      if (strlen($code) == '12') {
        //核销微信卡券
        $url = 'https://api.weixin.qq.com/card/code/consume?access_token='.$accessToken;
        $ret = httpPost($url, json_encode(array('code'=>$code)));
        echo 'consume code '.$ret.PHP_EOL;
      }

      $payInfo = explode('#', $data['pay_info']);
      $createdByUserId = $payInfo[2];
      $createdByUserName = $payInfo[3];
      $shopId            = $payInfo[4];

      $sql = "INSERT INTO coupons_used (openid, coupon_id, coupon_name, mch_id, shop_id, code, amount, trade_no, created_by_uname, created_at) VALUES ('$subOpenId', '$useCouponId', '$useCouponName', '$mchId', '$shopId', '$code', $useCouponAmount, '$outTradeNo', '$createdByUserName', '$createdAt')";
      $db->query($sql);
    }
    if ($wechatCashCouponId) {
      $sql = "UPDATE member_coupons SET status = 0, updated_at = '$createdAt' WHERE mch_id = $mchId AND code = '$wechatCashCouponId' LIMIT 1";
      $db->query($sql);
      $affected =  $db->affected_rows();
      if (!$affected) {
        $wechatCashCouponId = false;
      }
    }

    if ($awardCouponId) {
      $sql = "SELECT * FROM coupons WHERE id = $awardCouponId";
      $ret = $db->fetch_row($sql);
      $couponName = $ret['name'];
      $amount     = $ret['amount'];
      $discount   = $ret['discount'];
      $detail     = $ret['description'];
      $consumeLimit = $ret['consume_limit'];
      $couponType   = $ret['coupon_type'];

      if ('relative' == $ret['validity_type']) {
        $dateStart = $ret['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
        $totalDays = $ret['total_days'] - 1;
        $dateEnd   = date('Y-m-d', strtotime('+'.$totalDays.' days'));
      } else {
        $dateStart = $ret['date_start'];
        $dateEnd   = $ret['date_end'];
      }

      for($i=0;$i<$awardCouponTotal;$i++) {
        $code = mt_rand(1000000000, 9999999999);
        $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code,  detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$subOpenId', '$awardCouponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'rebate', '$createdAt')";
        $db->query($sql);
      }
      $sql = "UPDATE coupons SET balance = balance - $awardCouponTotal WHERE id = $awardCouponId";
      $db->query($sql);
    }

    //更新会员信息
    $sql = "UPDATE members SET consumes = consumes + 1, point = point + $modifyPoint, recharge = recharge - $consumeRecharge";
    if ($useCouponId) {
      $sql .= ", coupons = coupons - 1";
    }
    if ($wechatCashCouponId) {
      $sql .=", coupons = coupons -1";
    }
    if ($awardCouponId) {
      $sql .= ", coupons = coupons + $awardCouponTotal";
    }
    $sql .= ", amount_total = amount_total + $consume, last_consume_at = '$createdAt' WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
    $db->query($sql);

    $affectedRows = $db->affected_rows();
    if (!$affectedRows) {
      //新顾客，支付后写入一条会员数据
      //$cardNum = mt_rand(1000000000, 9999999999);
      if (isset($data['pay_action']) && 'waimai' == $data['pay_action']) {
        $cardNum = '';
        $sql = "INSERT INTO members (mch_id, openid, sub_openid, cardnum, grade, grade_title, consumes, point, coupons, amount_total, last_consume_at, created_at) VALUES ($mchId, '$openId', '$subOpenId', '$cardNum', 0, '粉丝', 1, $modifyPoint, $awardCouponTotal, $consume, '$createdAt', '$createdAt')";
        $db->query($sql);
      }
    }

    if ($modifyPoint) {
      $detail = $modifyPoint > 0 ? '消费奖励' : '消费抵扣'; 
      $sql = "INSERT INTO member_point_history (mch_id, openid, modify_point, detail, created_at) VALUES ($mchId, '$subOpenId', $modifyPoint, '$detail', '$createdAt')";
      $db->query($sql);
    }

    if ($isMember || $modifyPoint) {
      $sql = "SELECT member_cardid, cardnum, coupons FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      $cardNum = $row['cardnum'];
      $cardId  = $row['member_cardid'];
      if ($cardNum && $cardId) {
        $detail = $modifyPoint > 0 ? '消费奖励' : '消费抵扣'; 
        $data = array(
                  'code' => $cardNum,
                  'card_id' => $cardId,
                  'add_bonus' => $modifyPoint,
                  'record_bonus' => urlencode($detail),
                  'custom_field_value1' => $row['coupons'].urlencode('张'),
                  'notify_optional' => array(
                                              'is_notify_bonus' => $isNotifyBonus,
                                              'is_notify_custom_field1' => $isNotifyBonus
                                            )
        );
        $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
        $a = httpPost($url, urldecode(json_encode($data)));
        echo 'update card result '.$a.PHP_EOL;
      }
      update_member_grade($openId, $mchId, $accessToken);
    }
  }

  function update_member_ordering($data)
  {
    global $db;
    $mchId  = $data['sub_mch_id'];
    $openId = $data['openid'];
    $subOpenId = $data['sub_openid'];
    $accessToken = $data['wx_access_token'];
    $payInfo = explode('#', $data['pay_info']);
    $tableId = $payInfo[2];

    $outTradeNo = $data['out_trade_no'];
    $trade      = $data['trade'];
    $cashAmount = $data['cash_fee']/100;

    $sql = "SELECT name, grade_title FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
    $row = $db->fetch_row($sql);
    if ($row['name']) {
      $contactName = $row['name'];
      $gradeTitle  = $row['grade_title'];
    } else {
      $contactName = $gradeTitle = '';
    }

    //有可能是主人点餐，客人扫码买单
    $sql = "SELECT id, form_id, out_trade_no, table_id FROM member_ordering_orders WHERE mch_id = $mchId";
    if ($tableId) {
      $sql .= " AND table_id = $tableId";
    } else {
      $sql .= " AND openid = '$subOpenId'";
    }
    $sql .= " ORDER BY id DESC LIMIT 1";
    $row = $db->fetch_row($sql);
    $orderId= $row['id'];
    $tableId= $row['table_id'];
    
    $getNo = '';
    if ($tableId) {
      //有桌台，需要清台
      $sql = "UPDATE mch_ordering_tables SET is_seat = 0 WHERE mch_id = $mchId AND table_id = $tableId";
      $db->query($sql);
    }
    if (!$row['out_trade_no']) {
      //先支付后享用，无订单号，有取餐号
      $today = date('Y-m-d');
      $sql = "SELECT COUNT(id) AS total FROM member_ordering_orders WHERE mch_id = $mchId AND created_at > '$today'";
      $row = $db->fetch_row($sql);
      $getNo = $row['total']%50 + 1;
    }

    $sql = "UPDATE member_ordering_orders SET is_pay = 1, total_amount = $trade, cash_amount = $cashAmount, out_trade_no = '$outTradeNo', get_no = '$getNo', contact_name = '$contactName', grade_title = '$gradeTitle' WHERE id = $orderId";
    $db->query($sql);
    

    //小票打印机打印
    print_ordering_receipt($data);
  }


  function update_member_waimai($data)
  {
    global $db;
    $mchId  = $data['sub_mch_id'];
    $openId = $data['openid'];
    $subOpenId = $data['sub_openid'];
    $accessToken = $data['wx_access_token'];

    $outTradeNo = $data['out_trade_no'];
    $trade    = $data['trade'];
    $deliveryCost = $data['delivery_cost'];
    $packageCost  = $data['package_cost'];
    $deliveryTime = $data['delivery_time'];
    $isSelf       = 'self' == $data['order_type'] ? 1 : 0;
    
    $sql = "SELECT name, mobile, address, address_no FROM member_address WHERE mch_id = $mchId AND openid = '$subOpenId'";
    $row = $db->fetch_row($sql);
    if ($row) {
      $name = $row['name'];
      $mobile = $row['mobile'];
      $address= $row['address'].$row['address_no'];
    } else {
      $name = $mobile = $address = '';
    }
    
    $sql = "UPDATE member_waimai_orders SET is_pay = 1, is_self = $isSelf, total_amount = $trade, delivery_cost = $deliveryCost, package_cost = $packageCost, out_trade_no = '$outTradeNo', contact_name = '$name', contact_mobile = '$mobile', contact_address = '$address', delivery_time = '$deliveryTime' WHERE mch_id = $mchId AND openid = '$subOpenId' ORDER BY id DESC LIMIT 1";
    $db->query($sql);

    //小票打印机打印
    print_waimai_receipt($mchId, $outTradeNo);
    
    //商户提醒
    $detail = '';
    $sql = "SELECT delivery_time, detail FROM member_waimai_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    $deliveryTime = $row['delivery_time'];
    $detailData = unserialize($row['detail']);
    foreach ($detailData as $row) {
      $detail .= $row['dish'].$row['total'].'份,';
    }
    $detail = substr($detail, 0, -1);
    $detail = mb_strlen($detail) > 10 ? mb_substr($detail, 0, 8) .'...' : $detail;
    $address = mb_strlen($address) > 10 ? mb_substr($address, 0, 8) .'...' : $address;

    $sql = "SELECT created_at, id FROM wechat_pays WHERE mch_id = $mchId AND pay_from = 'waimai' AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    $createdAt  = $row['created_at'];

    $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
    $ret = $db->fetch_row($sql);
    $merchantName = $ret['merchant_name'];

    $paramData  = array(
                        'first' => '订单金额：'.$trade.'元',
                        'keyword1' => $merchantName,
                        'keyword2' => $createdAt,
                        'keyword3' => $name.$mobile,
                        'keyword4' => $detail,
                        'keyword5' => $address,
                        'remark'   => '配送时间'.$deliveryTime
                      );
    $remindData = array(
                    'mch_id' => $mchId,
                    'remind_type'     => 'is_waimai',
                    'wx_access_token' => $accessToken,
                    'template_id'     => 'GD8r1sl2k-8JfBRYt161g_D5ysof0oJrqpTWp5oFiTs',
                    'page_path'       => 'pages/trade/detail?out_trade_no='.$outTradeNo,
                    'param_data'      => $paramData
                  );
    send_user_remind($remindData);
  }

  function update_member_mall($data)
  {
    global $db;
    $mchId  = $data['sub_mch_id'];
    $openId = $data['openid'];
    $subOpenId = $data['sub_openid'];
    $accessToken = $data['wx_access_token'];

    $outTradeNo = $data['out_trade_no'];
    $trade    = $data['trade'];
    $deliveryCost = $data['delivery_cost'];
    $distributeId = $data['distribute_id'];
    $cart = $data['cart'];
    $buyTotals = $data['buy_totals'];
    $amount= $data['amount'];
    $trade = $data['trade'];
    $deliveryCost = $data['delivery_cost'];
    
    $sql = "SELECT name, mobile, address, address_no FROM member_address WHERE mch_id = $mchId AND openid = '$subOpenId'";
    $row = $db->fetch_row($sql);
    $name = $row['name'];
    $mobile = $row['mobile'];
    $address= $row['address'].$row['address_no'];

    $buyDetail = '';
    $cartData = explode(',', $cart);
    $buyTotalData = explode(',', $buyTotals);
    foreach ($cartData as $k=>$productId) {
      $sql = "SELECT title, price FROM mch_mall_products WHERE id = $productId";
      $row = $db->fetch_row($sql);
      $total = $buyTotalData[$k];
      $buyDetail[] = array('title'=>$row['title'], 'price'=>$row['price'], 'total'=>$total);

      $revenue = $row['price'] * $total;
      $sql = "UPDATE mch_mall_products SET sold = sold + $total, revenue = revenue + $revenue WHERE id = $productId";
      $db->query($sql);
    }
    $detail = serialize($buyDetail);

    $now = date('Y-m-d H:i:s');
    $sql = "INSERT INTO member_mall_orders (mch_id, openid, amount, delivery_cost, total_amount, detail, out_trade_no, contact_name, contact_mobile, contact_address, created_at) VALUES ($mchId, '$subOpenId', $amount, $deliveryCost, $trade, '$detail', '$outTradeNo', '$name', '$mobile', '$address', '$now')";
    $db->query($sql);

    //小票打印机打印
    print_mall_receipt($mchId, $outTradeNo);
    
    //商户提醒
    $detail = '';
    $sql = "SELECT detail, created_at FROM member_mall_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    $createdAt  = $row['created_at'];
    $detailData = unserialize($row['detail']);
    foreach ($detailData as $row) {
      $detail .= $row['title'].$row['total'].'份,';
    }
    $detail = substr($detail, 0, -1);
    $detail = mb_strlen($detail) > 10 ? mb_substr($detail, 0, 8) .'...' : $detail;
    $address = mb_strlen($address) > 10 ? mb_substr($address, 0, 8) .'...' : $address;

    $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
    $ret = $db->fetch_row($sql);
    $merchantName = $ret['merchant_name'];

    $paramData  = array(
                        'first' => '订单金额：'.$trade.'元',
                        'keyword1' => $merchantName,
                        'keyword2' => $createdAt,
                        'keyword3' => $name.$mobile,
                        'keyword4' => $detail,
                        'keyword5' => $address,
                        'remark'   => ''
                      );
    $remindData = array(
                    'mch_id' => $mchId,
                    'remind_type'     => 'is_mall',
                    'wx_access_token' => $accessToken,
                    'template_id'     => 'GD8r1sl2k-8JfBRYt161g_D5ysof0oJrqpTWp5oFiTs',
                    'page_path'       => 'pages/trade/detail?out_trade_no='.$outTradeNo,
                    'param_data'      => $paramData
                  );
    send_user_remind($remindData);
  }

  function update_member_rechargenopay($data)
  {
    global $db;
    $now = date('Y-m-d H:i:s');
    $mchId       = $data['sub_mch_id'];
    $openId      = $data['openid'];
    $subOpenId   = $data['sub_openid'];

    $attach      = explode(',', $data['attach']);
    $key      = $attach[1];
    $recharge = $attach[2];
    $originTrade = $attach[3];
    $rechargeDiscount = $attach[4];
    if ('0' == $rechargeDiscount) {
      $consumeRecharge = 0;
    } else {
      $consumeRecharge = $originTrade * $rechargeDiscount / 10;
    }

    $transactionId   = $data['transaction_id'];
    $prepayId        = $data['prepay_id'];
    $accessToken = $data['wx_access_token'];
    $shopId      = isset($data['shop_id'])?$data['shop_id']:0;

    $sql = "UPDATE members SET recharge = recharge + $recharge - $consumeRecharge, recharge_total = recharge_total + $recharge WHERE openid = '$openId' AND mch_id = $mchId";
    $db->query($sql);
    $affectedRows = $db->affected_rows();
    if (!$affectedRows) {
      $rechargeBalance = $recharge - $consumeRecharge;
      $sql = "INSERT INTO members (mch_id, shop_id, openid, sub_openid, grade_title, recharge, recharge_total, last_consume_at, created_at) VALUES ($mchId, $shopId, '$openId', '$subOpenId', '粉丝', $rechargeBalance, $recharge, '$now', '$now')";
      $db->query($sql);
    }

    $sql = "INSERT INTO member_recharges (mch_id, shop_id, openid, sub_openid, recharge, award_money, award_coupon, transaction_id, created_at) VALUES ($mchId, '$shopId', '$openId', '$subOpenId', $recharge, 0, '', '$transactionId', '$now')";
    echo $sql.PHP_EOL;
    $db->query($sql);

    update_member_grade($openId, $mchId, $accessToken);
  }

  //加价买券
  function update_member_paybuycoupon($data)
  {
    global $db;
    $mchId  = $data['sub_mch_id'];
    $openId = $data['openid'];
    $subOpenId = $data['sub_openid'];
    $isMember  = $data['is_member'];
    $accessToken = $data['wx_access_token'];
    $consume  = $data['consume'];
    $getPoint = $data['get_point'];
    $createdAt = getDateAt($data['time_end']);

    $couponId    = $data['buy_coupon_id'];
    $couponTotal = $data['buy_coupon_total'];

    $sql = "SELECT * FROM coupons WHERE id = $couponId";
    $ret = $db->fetch_row($sql);
    $couponName = $ret['name'];
    $amount     = $ret['amount'];
    $discount   = $ret['discount'];
    $detail     = $ret['description'];
    $consumeLimit = $ret['consume_limit'];
    $couponType   = $ret['coupon_type'];

    if ('relative' == $ret['validity_type']) {
      $dateStart = $ret['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
      $totalDays = $ret['total_days'] - 1;
      $dateEnd   = date('Y-m-d', strtotime('+'.$totalDays.' days'));
    } else {
      $dateStart = $ret['date_start'];
      $dateEnd   = $ret['date_end'];
    }

    for($i=0;$i<$couponTotal;$i++) {
      $code = mt_rand(1000000000, 9999999999);
      $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code,  detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$subOpenId', '$couponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'buy', '$createdAt')";
      $db->query($sql);
    }
    $sql = "UPDATE coupons SET balance = balance - $couponTotal WHERE id = $couponId";
    $db->query($sql);

    //更新会员信息
    $sql = "UPDATE members SET coupons = coupons + $couponTotal  WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
    $db->query($sql);
    $affectedRows = $db->affected_rows();
    if (!$affectedRows) {
      //新顾客，支付后写入一条会员数据
      //$cardNum = mt_rand(1000000000, 9999999999);
      $cardNum = '';
      $sql = "INSERT INTO members (mch_id, openid, sub_openid, cardnum, grade, grade_title, coupons, last_consume_at, created_at) VALUES ($mchId, '$openId', '$subOpenId', '$cardNum', 0, '粉丝', $couponTotal, '$createdAt', '$createdAt')";
      $db->query($sql);
    } else {
      $sql = "SELECT member_cardid, cardnum, coupons FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      $cardNum = $row['cardnum'];
      $cardId  = $row['member_cardid'];
      if ($cardNum && $cardId) {
        $data = array(
                  'code' => $cardNum,
                  'card_id' => $cardId,
                  'custom_field_value1' => $row['coupons'].urlencode('张'),
                  'notify_optional' => array(
                                              'is_notify_custom_field1' => true 
                                            )
        );
        $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
        $a = httpPost($url, urldecode(json_encode($data)));
        echo 'update card result '.$a.PHP_EOL;
      }
    }
  }

  function update_member_recharge($data)
  {
    global $db;
    $now = date('Y-m-d H:i:s');
    $mchId       = $data['sub_mch_id'];
    $openId      = $data['openid'];
    $subOpenId   = $data['sub_openid'];

    $attach      = explode(',', $data['attach']);
    $key      = $attach[1];
    $recharge = $attach[2];
    $trade    = $recharge * 100;
    $awardType = $attach[3];
    $amount    = $attach[4];
    $percent   = $attach[5];
    $transactionId   = $data['transaction_id'];
    $prepayId        = $data['prepay_id'];
    $accessToken = $data['wx_access_token'];
    $shopId      = isset($data['shop_id'])?$data['shop_id']:0;

    $awardCouponInfo = '';
    switch ($awardType) {
      case 'money_percent':
        $awardMoney     = $recharge * $percent / 100;
        $rechargeModify = $recharge + $awardMoney;
        break;
      case 'coupon':
        $awardMoney = 0;

        $rechargeModify = $recharge;

        $awardCouponTotal = 0;
        $sql = "SELECT coupon_id, coupon_name, total FROM app_recharge_coupon_rules WHERE mch_id = $mchId AND touch = $recharge";
        $data = $db->fetch_array($sql);
        foreach ($data as $row) {
          $couponId = $row['coupon_id'];

          $sql = "SELECT * FROM coupons WHERE id = $couponId";
          $ret = $db->fetch_row($sql);
          $couponName = $ret['name'];
          $amount     = $ret['amount'];
          $discount   = $ret['discount'];
          $detail     = $ret['description'];
          $consumeLimit = $ret['consume_limit'];
          $couponType   = $ret['coupon_type'];

          if ('relative' == $ret['validity_type']) {
            $dateStart = $ret['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
            $totalDays = $ret['total_days'] - 1;
            $dateEnd   = date('Y-m-d', strtotime('+'.$totalDays.' days'));
          } else {
            $dateStart = $ret['date_start'];
            $dateEnd   = $ret['date_end'];
          }

          for($i=0;$i<$row['total'];$i++) {
            $code = mt_rand(1000000000, 9999999999);
            $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code,  detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$subOpenId', '$couponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'recharge', '$now')";
            $db->query($sql);
          }
          $sql = "UPDATE coupons SET balance = balance - $row[total] WHERE id = $couponId";
          $db->query($sql);

          $awardCouponInfo .= '返'.$row['coupon_name'].$row['total']."张\n";
          $awardCouponTotal += $row['total'];
        }
        break;
      case 'money_constant':
        $awardMoney = $amount;
        $rechargeModify = $recharge + $awardMoney;
        break;
      default:
        break;
    }
    $sql = "UPDATE members SET recharge = recharge + $rechargeModify, recharge_total = recharge_total + $rechargeModify";
    if ('coupon' == $awardType) {
      $sql .= " ,coupons = coupons + $awardCouponTotal";
    }
    $sql .= " WHERE sub_openid = '$subOpenId' AND mch_id = $mchId";
    $db->query($sql);
    $sql = "INSERT INTO member_recharges (mch_id, shop_id, openid, sub_openid, recharge, award_money, award_coupon, transaction_id, created_at) VALUES ($mchId, '$shopId', '$openId', '$subOpenId', $recharge, $awardMoney, '$awardCouponInfo', '$transactionId', '$now')";
    $db->query($sql);

    if ('coupon' == $awardType && $awardCouponTotal) {
      $sql = "SELECT member_cardid, cardnum, coupons FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      $cardNum = $row['cardnum'];
      $cardId  = $row['member_cardid'];
      if ($cardNum) {
        $data = array(
                  'code' => $cardNum,
                  'card_id' => $cardId,
                  'custom_field_value1' => $row['coupons'].urlencode('张'),
                  'notify_optional' => array('is_notify_custom_field1' => true)
        );
        $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
        $a = httpPost($url, urldecode(json_encode($data)));
        echo 'update card coupons result '.$a.PHP_EOL;
      }
    }
    update_member_grade($openId, $mchId, $accessToken);

    //管理员通知
    $sql = "SELECT mobile, recharge, recharge_total FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
    $row = $db->fetch_row($sql);
    $mobile        = $row['mobile'];
    $memberRecharge= $row['recharge'];
    $rechargeTotal = $row['recharge_total'];
    $cardNum       = $row['mobile'];

    $paramData  = array(
                        'first' => '充值到账',
                        'keyword1' => $mobile,
                        'keyword2' => $recharge,
                        'keyword3' => $awardMoney,
                        'keyword4' => $memberRecharge,
                        'remark'   => '累积储值'.$rechargeTotal.'元'
                      );
    $remindData = array(
                    'mch_id' => $mchId,
                    'remind_type'     => 'is_recharge',
                    'wx_access_token' => $accessToken,
                    'template_id'     => 'bR5N_XisnD7AXvM-E7wPSztXF28SlG0KybZvwu2pueo',
                    'page_path'       => 'pages/member/detail?openid='.$subOpenId,
                    'param_data'      => $paramData
                  );
    send_user_remind($remindData);
  }

  function update_member_groupon($data)
  {
    global $db;
    $now = date('Y-m-d H:i:s');
    $appId       = $data['sub_appid'];
    $mchId       = $data['mch_id'];
    $openId      = $data['openid'];
    $subOpenId = isset($data['sub_openid'])?$data['sub_openid']:$openId;
    $accessToken = $data['wx_access_token'];
    $miniAccessToken = $data['mini_access_token'];
    $prepayId        = $data['prepay_id'];
    $consume         = $data['total_fee']/100;

    $attach      = explode(',', $data['attach']);
    $key         = $attach[1];
    $grouponId   = $attach[2];
    $buyTotal    = $attach[3];
    $subMchId    = $attach[4];
    $couponId    = $attach[5];
    $couponTotal = $attach[6];

    $sql = "SELECT * FROM coupons WHERE id = $couponId";
    $ret = $db->fetch_row($sql);
    $couponName = $ret['name'];
    $amount     = $ret['amount'];
    $discount   = $ret['discount'];
    $detail     = $ret['description'];
    $consumeLimit = $ret['consume_limit'];
    $couponType   = $ret['coupon_type'];
    $couponRevenue = round($consume / $buyTotal / $couponTotal, 2);

    if ('relative' == $ret['validity_type']) {
      $dateStart = $ret['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
      $totalDays = $ret['total_days'] - 1;
      $dateEnd   = date('Y-m-d', strtotime('+'.$totalDays.' days'));
    } else {
      $dateStart = $ret['date_start'];
      $dateEnd   = $ret['date_end'];
    }

    if ($appId != SUISHOUHUI_APP_ID) {
      //判断拼团来自平台小程序还是商户独立小程序
      //$openId = $subOpenId;
    }
    $getCouponTotal = $buyTotal * $couponTotal;
    for($i=0;$i<$getCouponTotal;$i++) {
      $code = mt_rand(1000000000, 9999999999);
      $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code,  detail, date_start, date_end, get_type, coupon_revenue, in_wechat, created_at) VALUES ('$subMchId', '$subOpenId', '$couponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'buy', $couponRevenue, 1, '$now')";
      $db->query($sql);
    }
    $sql = "UPDATE coupons SET balance = balance - $getCouponTotal WHERE id = $couponId";
    $db->query($sql);

    $sql = "UPDATE mch_groupons SET sold = sold + $buyTotal WHERE id = $grouponId";
    $db->query($sql);

    $sql = "UPDATE members SET coupons = coupons + $getCouponTotal WHERE mch_id = $subMchId AND sub_openid = '$subOpenId'";
    $db->query($sql);
    $affectedRows = $db->affected_rows();
    if (!$affectedRows) {
      $sql = "SELECT openid, unionid, sub_openid, mch_id, nickname, headimgurl, province, city, gender FROM members WHERE sub_openid = '$subOpenId'";
      $member = $db->fetch_row($sql);
      //顾客多会员卡，在其它商户有会员，购券商户无会员卡，自动获取该顾客会员卡信息插入购券商户会员表
      $sql = "INSERT INTO members (mch_id, openid, sub_openid, unionid, grade, grade_title, nickname, headimgurl, province, city, gender, coupons, created_at) VALUES ($subMchId, '$member[openid]', '$subOpenId', '$member[unionid]', 0, '粉丝', '$member[nickname]', '$member[headimgurl]', '$member[province]', '$member[city]', $member[gender], $getCouponTotal, '$now')";
      $db->query($sql);
    }

    $postData = array(
              'mch_id' => $subMchId,
              'openid' => $subOpenId,
              'access_token' => $accessToken,
              'point'  => 0,
              'detail' => '',
            );
    update_member_card($postData);

    //通知提醒
    $sql = "SELECT business_name FROM shops WHERE mch_id = $subMchId";
    $row = $db->fetch_row($sql);
    $merchantName = $row['business_name'];

    $goodDetail = $couponTotal > 1 ? $couponName.'X'.$couponTotal : $couponName;
    $paramData  = array(
                        'first' => '您的商户有一笔新团购订单',
                        'keyword1' => $merchantName,
                        'keyword2' => $goodDetail,
                        'keyword3' => $buyTotal.'份',
                        'keyword4' => $now,
                        'remark'   => '订单金额：'.$consume.'元'
                      );
    $remindData = array(
                    'mch_id' => $subMchId,
                    'remind_type'     => 'is_groupon',
                    'wx_access_token' => $accessToken,
                    'template_id'     => 'SoR8w7UqZzoJm4nWdZOrUm3RIvFztD-n2tXYJV5Qeew',
                    'page_path'       => 'pages/buy/sold_list?groupon_id='.$grouponId,
                    'param_data'      => $paramData
                  );
    send_user_remind($remindData);
  }

  function update_member_together($data)
  {
    global $db;
    $now = date('Y-m-d H:i:s');
    $mchId       = $data['mch_id'];
    $subAppId    = $data['sub_appid'];
    $openId      = $data['openid'];
    $accessToken = $data['wx_access_token'];
    $miniAccessToken = $data['mini_access_token'];
    $prepayId        = $data['prepay_id'];
    $consume         = $data['total_fee']/100;

    $attach      = explode(',', $data['attach']);
    $key         = $attach[1];
    $togetherId = $attach[2];
    $buyTotal  = $attach[3];
    $subMchId  = $attach[4];
    $couponId  = $attach[5];
    $isHead    = $attach[6];
    $togetherNo = $attach[7];

    $sql = "SELECT coupon_name, price, people FROM mch_togethers WHERE id = $togetherId";
    $row = $db->fetch_row($sql);
    $couponName = $row['coupon_name'];
    $price  = $row['price'];
    $people = $row['people'];

    $sql = "UPDATE mch_togethers SET sold = sold + $buyTotal";
    if ('true' == $isHead) {
      $sql .= ", opens = opens + 1";
    }
    $sql .= " WHERE id = $togetherId";
    $db->query($sql);

    $sql = "SELECT openid, buy_total, prepay_id, transaction_id FROM wechat_groupon_pays WHERE groupon_id = $togetherId AND is_together = 1 AND together_no = '$togetherNo'";
    $togetherData = $db->fetch_array($sql);
    if (count($togetherData) < $people) {
      //未成团
      return false;
    }
    //已成团

    $sql = "SELECT * FROM coupons WHERE id = $couponId";
    $ret = $db->fetch_row($sql);
    $couponName = $ret['name'];
    $amount     = $ret['amount'];
    $discount   = $ret['discount'];
    $detail     = $ret['description'];
    $consumeLimit = $ret['consume_limit'];
    $couponType   = $ret['coupon_type'];
    $couponRevenue = round($consume / $buyTotal, 2);

    if ('relative' == $ret['validity_type']) {
      $dateStart = $ret['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
      $totalDays = $ret['total_days'] - 1;
      $dateEnd   = date('Y-m-d', strtotime('+'.$totalDays.' days'));
    } else {
      $dateStart = $ret['date_start'];
      $dateEnd   = $ret['date_end'];
    }
    $sql = "SELECT business_name FROM shops WHERE mch_id = $subMchId";
    $row = $db->fetch_row($sql);
    $merchantName = $row['business_name'];

    $getCouponTotal = 0;
    foreach ($togetherData as $row) {
      $openId = $row['openid'];
      $buyTotal = $row['buy_total'];
      $prepayId = $row['prepay_id'];
      $transactionId = $row['transaction_id'];
      for($i=0;$i<$buyTotal;$i++) {
        $code = mt_rand(1000000000, 9999999999);
        $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code,  detail, date_start, date_end, get_type, coupon_revenue, created_at) VALUES ('$subMchId', '$openId', '$couponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'together_buy', $couponRevenue, '$now')";
        $db->query($sql);
        $getCouponTotal++;
      }
      $sql = "UPDATE members SET coupons = coupons + $buyTotal WHERE mch_id = $subMchId AND sub_openid = '$openId'";
      $db->query($sql);
      $affectedRows = $db->affected_rows();
      if (!$affectedRows) {
        $sql = "SELECT openid, unionid, sub_openid, mch_id, nickname, headimgurl, province, city, gender FROM members WHERE sub_openid = '$openId'";
        $member = $db->fetch_row($sql);
        //顾客多会员卡，在其它商户有会员，购券商户无会员卡，自动获取该顾客会员卡信息插入购券商户会员表
        $sql = "INSERT INTO members (mch_id, openid, sub_openid, unionid, grade, grade_title, nickname, headimgurl, province, city, gender, coupons, created_at) VALUES ($subMchId, '$member[openid]', '$openId', '$member[unionid]', 0, '粉丝', '$member[nickname]', '$member[headimgurl]', '$member[province]', '$member[city]', $member[gender], $buyTotal, '$now')";
        $db->query($sql);
      }

      $sql = "SELECT template_id FROM app_subscribe_list WHERE mch_id = $subMchId AND tid = 1923";
      $row = $db->fetch_row($sql);
      $templateId = $row['template_id'];
      $templateData= array(
                      'thing2'  => array('value'=>$couponName),
                      'amount3' => array('value'=>$amount.'元'),
                      'amount4' => array('value'=>$price.'元'),
                      'thing7'  => array('value'=>'已满'.$people.'人'),
                      'thing6'  => array('value'=>'恭喜你拼团成功，团购券已入账')
                    );
      $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$miniAccessToken;
      $postData = array(
                    'touser'       => $openId,
                    'template_id'  => $templateId,
                    'page'         => 'pages/vip/index',
                    'data'         => $templateData
                  );
      $r = sendHttpRequest($url, $postData);
      echo 'send together success mini message '.$r.PHP_EOL;

      $postData = array(
                'mch_id' => $subMchId,
                'openid' => $openId,
                'access_token' => $accessToken,
                'point'  => 0,
                'detail' => '',
              );
      update_member_card($postData);
      //拼团成功解冻商户资金
      profitsharingfinish($subMchId, $transactionId);
    }
    $sql = "UPDATE coupons SET balance = balance - $getCouponTotal WHERE id = $couponId";
    $db->query($sql);

    $sql = "UPDATE mch_togethers SET success = success + 1 WHERE id = $togetherId";
    $db->query($sql);

    $sql = "UPDATE wechat_groupon_pays SET together_status = 'success' WHERE mch_id = $subMchId AND together_no = '$togetherNo'";
    $db->query($sql);

    $today = date('Y-m-d');
    $sql = "SELECT id FROM wechat_groupon_pays_today WHERE mch_id = $subMchId AND date_at = '$today'";
    $row = $db->fetch_row($sql);
    if ($row['id']) {
      $sql = "UPDATE wechat_groupon_pays_today SET together_success = together_success + 1, coupon_total = coupon_total + $getCouponTotal WHERE id = $row[id]";
    } else {
      $sql = "INSERT INTO wechat_groupon_pays_today (mch_id, coupon_total, together_success, date_at) VALUES ($subMchId, $getCouponTotal, 1, '$today')";
    }
    $db->query($sql);
  }

  function send_point_exchange_notice($data)
  {
    global $db;
    $miniAccessToken = $data['mini_access_token'];
    $mchId = $data['mch_id'];
    $code  = $data['code'];
    $point = $data['point'];
    $formId = $data['form_id'];

    $sql = "SELECT merchant_name FROM users WHERE mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $merchantName = $row['merchant_name'];

    $sql = "SELECT * FROM member_coupons WHERE mch_id = $mchId AND code = '$code'";
    $row = $db->fetch_row($sql);
    $openId = $row['openid'];
    $id     = $row['id'];
    $exchangeName = $row['coupon_name'].'1张';
    $remark = '券有效期自'.$row['date_start'].'至'.$row['date_end'];

    $sql = "SELECT point FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
    $row = $db->fetch_row($sql);
    $pointBalance = $row['point'];

    $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$miniAccessToken;
    $data = array(
              'touser' => $openId,
              'template_id' => 'lqjSnIjEuuu9RRV-DxphHXSJAvlb2brOQZN8pDkgOys',
              'page'        => '/pages/coupon/list',
              'form_id'     => $formId,
              'data'        => array(
                                  'keyword1' => array('value'=>$merchantName),
                                  'keyword2' => array('value'=>$exchangeName),
                                  'keyword3' => array('value'=>$point.'积分'),
                                  'keyword4' => array('value'=>$pointBalance.'积分'),
                                  'keyword5' => array('value'=>$remark),
                               )
            );
    print_r($data);
    $r = sendHttpRequest($url, $data);
    echo 'send point exchange '.$r.PHP_EOL;
  }

  function update_member_grade($openId, $mchId, $accessToken)
  {
      global $db;
      //判断是否符合升级规则
      $sql = "SELECT * FROM app_grades WHERE mch_id = $mchId AND catch_type != 'scan'";
      $data = $db->fetch_array($sql);
      if ($data) {
        $gradeRules = array();
        foreach ($data as $row) {
          $gradeRules[$row['catch_type']][$row['grade']] = array('catch'=>$row['catch_value'], 'grade_title'=>$row['name']);
        }
        $sql = "SELECT member_cardid, cardnum, consumes, amount_total, recharge_total, grade FROM members WHERE mch_id = $mchId AND openid = '$openId'";
        $row = $db->fetch_row($sql);
        $cardId   = $row['member_cardid'];
        $cardNum  = $row['cardnum'];
        $consumes = $row['consumes'];
        $amountTotal = $row['amount_total'];
        $rechargeTotal = $row['recharge_total'];
        $memberGrade = $row['grade'];

        $upgrade = false;
        if (isset($gradeRules['frequency'])) {
          foreach ($gradeRules['frequency'] as $grade=>$row) {
            if ($consumes >= $row['catch']) {
              $upgrade = $grade;
              $gradeTitle = $row['grade_title'];
            }
          }
        }
        if (isset($gradeRules['amount'])) {
          foreach ($gradeRules['amount'] as $grade=>$row) {
            if ($amountTotal >= $row['catch']) {
              $upgrade = $grade;
              $gradeTitle = $row['grade_title'];
            }
          }
        }
        if (isset($gradeRules['recharge'])) {
          foreach ($gradeRules['recharge'] as $grade=>$row) {
            if ($rechargeTotal >= $row['catch']) {
              $upgrade = $grade;
              $gradeTitle = $row['grade_title'];
            }
          }
        }
        if ($upgrade && $upgrade > $memberGrade) {
          $now = date('Y-m-d H:i:s');
          $sql = "UPDATE members SET grade = $upgrade, grade_title = '$gradeTitle', upgrade_at = '$now' WHERE mch_id = $mchId AND openid = '$openId'";
          $db->query($sql);

          if ($cardNum && $cardId) {
            $postData = array(
                      'code' => $cardNum,
                      'card_id' => $cardId,
                      'custom_field_value2' => urlencode($gradeTitle),
                      'notify_optional' => array('is_notify_custom_field2'=>true)
            );
            $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
            $a = httpPost($url, urldecode(json_encode($postData)));
            echo 'update grade result '.$a.PHP_EOL;
          }
        }
      }
  }

  function update_member_point($data)
  {
    global $db;
    $mchId  = $data['mch_id'];
    $openId = $data['sub_openid'];
    $accessToken = $data['wx_access_token'];
    $modifyPoint = $data['point'];

    $sql = "SELECT member_cardid, cardnum FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
    $row = $db->fetch_row($sql);
    if ($row['cardnum'] && $row['member_cardid']) {
      $cardNum = $row['cardnum'];
      $cardId  = $row['member_cardid'];
      $data = array(
                'code' => $cardNum,
                'card_id' => $cardId,
                'add_bonus' => $modifyPoint,
                'record_bonus' => urlencode('门店手工调整'),
                'notify_optional' => array(
                                            'is_notify_bonus' => true,
                                          )
      );
      $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
      $a = httpPost($url, urldecode(json_encode($data)));
      echo 'update member card '.$a.PHP_EOL;
    }
  }

  function update_member_card($data)
  {
    global $db;
    $mchId  = $data['mch_id'];
    $openId = $data['openid'];
    $accessToken = $data['access_token'];
    $point  = $data['point'];
    $detail = $data['detail'];

    $sql = "SELECT member_cardid, cardnum, coupons FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
    $row = $db->fetch_row($sql);
    if ($row['cardnum'] && $row['member_cardid']) {
      $cardNum = $row['cardnum'];
      $cardId  = $row['member_cardid'];
      $data = array(
                'code' => $cardNum,
                'card_id' => $cardId,
                'add_bonus' => $point,
                'record_bonus' => urlencode($detail),
                'custom_field_value1' => $row['coupons'].urlencode('张'),
                'notify_optional' => array('is_notify_bonus' => true, 'is_notify_custom_field1'=>true)
      );
      $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
      $a = httpPost($url, urldecode(json_encode($data)));
      echo 'update member card '.$a.PHP_EOL;
    }
  }

  function send_member_coupon($data)
  {
    global $db;
    $accessToken = $data['wx_access_token'];
    $mchId       = $data['mch_id'];
    $subOpenId   = $data['sub_openid'];
    $couponId    = $data['coupon_id'];
    $couponTotal = $data['coupon_total'];
    $couponName  = $data['coupon_name'];
    $amount      = $data['amount'];
    $discount    = $data['discount'];
    $detail      = $data['detail'];
    $consumeLimit = $data['consume_limit'];
    $couponType   = $data['coupon_type'];
    $dateStart    = $data['date_start'];
    $dateEnd      = $data['date_end'];

    $cardNum = $data['cardnum'];
    $cardId  = $data['member_cardid'];
    $coupons = $data['coupons'];
    $now     = date('Y-m-d H:i:s');

    for($i=0;$i<$couponTotal;$i++) {
      $code = mt_rand(1000000000, 9999999999);
      $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code, detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$subOpenId', '$couponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'send', '$now')";
      $db->query($sql);
    }
    $sql = "UPDATE members SET coupons = coupons + $couponTotal WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
    $db->query($sql);

    if ($cardId) {
      $nowCoupons = $coupons + $couponTotal;
      $data = array(
                'code' => $cardNum,
                'card_id' => $cardId,
                'custom_field_value1' => $nowCoupons.urlencode('张'),
                'notify_optional' => array('is_notify_custom_field1' => true)
      );
      $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
      $a = httpPost($url, urldecode(json_encode($data)));
      echo 'send member coupon modify '.$a.PHP_EOL;
    }

    $sql = "UPDATE coupons SET balance = balance - $couponTotal WHERE id = $couponId";
    $db->query($sql);
  }

  function consume_coupon($data)
  {
    global $db;
    $accessToken = $data['access_token'];
    $mchId       = $data['mch_id'];
    $code        = $data['code'];
    $couponAmount= isset($data['coupon_amount'])?$data['coupon_amount']:0;
    $shopId      = $data['shop_id'];
    $createdBy   = $data['created_by'];
    $consumeTotal= $data['consume_total'];
    $now         = date('Y-m-d H:i:s');

    $sql = "SELECT openid, coupon_id, coupon_name, get_type, coupon_revenue FROM member_coupons WHERE mch_id = $mchId AND code = '$code'";
    $row = $db->fetch_row($sql);
    $subOpenId = $row['openid'];
    $couponId  = $row['coupon_id'];
    $couponName= $row['coupon_name'];
    $getType   = $row['get_type'];
    $revenue = $row['coupon_revenue'];

    $date = date('Y-m-d');
    if ($consumeTotal > 1) {
      $sql = "SELECT code FROM member_coupons WHERE mch_id = $mchId AND coupon_id = $couponId AND status = 1 AND date_start <= '$date' AND date_end >= '$date' AND openid = '$subOpenId' AND code != '' LIMIT $consumeTotal";
      $ret = $db->fetch_array($sql);
      foreach ($ret as $v) {
        $couponData[] = $v['code'];
      }
    } else {
      $couponData = array($code);
    }

    foreach ($couponData as $code) {
      $sql = "UPDATE member_coupons SET status = 0, updated_at = '$now' WHERE code = '$code'";
      $db->query($sql);
      $affectedRows = $db->affected_rows();
      if (!$affectedRows) {
        return false;
      }

      $sql = "UPDATE members SET coupons = coupons - 1 WHERE sub_openid = '$subOpenId' AND mch_id = '$mchId'";
      $db->query($sql);

      $tradeNo = getTradeNo();
      $sql = "INSERT INTO coupons_used (openid, coupon_id, coupon_name, mch_id, shop_id, code, amount, trade_no, created_by_uname, created_at) VALUES ('$subOpenId', '$couponId', '$couponName', '$mchId', '$shopId', '$code', $couponAmount, '$tradeNo', '$createdBy', '$now')";
      $db->query($sql);

      $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$date' AND shop_id = $shopId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE wechat_pays_today SET use_coupon_total = use_coupon_total +1, use_coupon_amount = use_coupon_amount + $couponAmount, updated_at = '$now' WHERE mch_id = $mchId AND date_at = '$date' AND shop_id = $shopId";
      } else {
        $sql = "INSERT INTO wechat_pays_today (mch_id, shop_id, use_coupon_total, use_coupon_amount, date_at, updated_at) VALUES ($mchId, $shopId, 1, $couponAmount, '$date', '$now')";
      }
      $db->query($sql);

      //如果是团购券则计算最新revenue
      if ('buy' == $getType || 'together_buy' == $getType) {
        if ('buy' == $getType) {
          $sql = "UPDATE mch_groupons SET consumed = consumed + 1 WHERE mch_id = $mchId AND coupon_id = $couponId ORDER BY id DESC LIMIT 1";
        } else {
          $sql = "UPDATE mch_togethers SET consumed = consumed + 1 WHERE mch_id = $mchId AND coupon_id = $couponId ORDER BY id DESC LIMIT 1";
        }
        $db->query($sql);


        $sql = "SELECT id FROM wechat_groupon_pays_today WHERE mch_id = $mchId AND date_at = '$date'";
        $row = $db->fetch_row($sql);
        if ($row['id']) {
          $sql = "UPDATE wechat_groupon_pays_today SET coupon_used = coupon_used + 1 WHERE id = $row[id]";
        } else {
          $sql = "INSERT INTO wechat_groupon_pays_today (mch_id, coupon_used, date_at) VALUES ($mchId, 1, '$date')";
        }
        $db->query($sql);
      }
      $message = $createdBy.'核销'.$couponName.'1张,优惠'.$couponAmount.'元';
      $sql = "INSERT INTO logs (mch_id, openid, message, created_at) VALUES ('$mchId', '$subOpenId', '$message', '$now')";
      $db->query($sql);

      //核销微信卡券
      if (strlen($code) == '12') {
        $data = array(
                      'code' => $code,
                     );
        $url = 'https://api.weixin.qq.com/card/code/consume?access_token='.$accessToken;
        $ret = httpPost($url, json_encode($data));
        echo 'consume code '.$code.' '.$ret.PHP_EOL;
      } else {
        $channel = 'coupon_'.$code;
        $message = json_encode(array('consume'=>'completed'));
        $url     = 'http://sinaapp.keyouxinxi.com/send_message.php?channel='.$channel.'&message='.$message;
        $ret     = httpPost($url);
      }
    }

    $data = array('openid'=>$subOpenId, 'point'=>0, 'detail'=>'', 'mch_id'=>$mchId, 'access_token'=>$accessToken);
    update_member_card($data);
  }

  function groupon_refund($data)
  {
    //请求频率限制：150qps，即每秒钟正常的申请退款请求次数不超过150次
    sleep(1);
    require_once dirname(__FILE__).'/lib/WxPay.Config.php';
    require_once dirname(__FILE__).'/lib/WxPay.Exception.php';
    global $db;
    $mchId      = $data['mch_id'];
    $totalFee   = $data['total_fee'];
    $refundFee  = $data['refund_fee'];
    $outTradeNo = $data['out_trade_no'];
    $openId     = $data['openid'];
    $outRefundNo= getOutTradeNo();
    $refundDesc = '商品过期未使用退款';
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
    echo $response.PHP_EOL;
    $retData = FromXml($response);
    if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
      $now = date('Y-m-d H:i:s');
      $sql = "INSERT INTO wechat_refunds (appid, mch_id, openid, out_trade_no, out_refund_no, refund_id, total_fee, refund_fee, cash_refund_fee, coupon_refund_fee, refund_desc, created_by_uid, created_at) VALUES ('$retData[appid]', $mchId, '$openId', '$outTradeNo', '$outRefundNo', '$retData[refund_id]', $totalFee, $refundFee, $retData[cash_refund_fee], $retData[coupon_refund_fee], '$refundDesc', 0, '$now')";
      $db->query($sql);

      $sql = "UPDATE wechat_groupon_pays SET refund_fee = refund_fee + $refundFee WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $db->query($sql);
    }
  }

  function trade_refund($data)
  {
    global $db;
    $now = date('Y-m-d H:i:s');
    $modifyCoupons = $modifyPoint = 0;
    $amountTotal   = $data['trade'] - $data['save'];
    $useRecharge   = $data['use_recharge'];

    $mchId     = $data['mch_id'];
    $subOpenId = $data['sub_openid'];
    if ($data['use_coupon_id']) {
      $sql = "UPDATE member_coupons SET status = 1, in_wechat = 0, updated_at = '0000-00-00 00:00:00' WHERE mch_id = $mchId AND coupon_id = $data[use_coupon_id] AND status = 0 ORDER BY id DESC LIMIT 1";
      $db->query($sql);

      $modifyCoupons = 1;
    }
    if ($data['use_point'] || $data['get_point']) {
      $modifyPoint = $data['use_point'] - $data['get_point'];

      $sql = "INSERT INTO member_point_history (mch_id, openid, modify_point, detail, created_at) VALUES ($mchId, '$subOpenId', $modifyPoint, '退款', '$now')";
      $db->query($sql);
    }

    if ($modifyCoupons || $modifyPoint) {
      $sql = "UPDATE members SET coupons = coupons + $modifyCoupons, point = point + $modifyPoint, amount_total = amount_total - $amountTotal, consumes = consumes - 1, recharge = recharge - $useRecharge WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $db->query($sql);
      $data['openid'] = $subOpenId;
      $data['point']  = $modifyPoint;
      $data['detail'] = '退款';
      update_member_card($data);
    }
  }

  function send_user_remind($data)
  {
    global $db;
    $mchId = $data['mch_id'];
    $remindType  = $data['remind_type'];
    $accessToken = $data['wx_access_token'];
    $templateId  = $data['template_id'];
    $pagePath    = $data['page_path'];
    $paramData   = $data['param_data'];

    //通知商户
    $sql = "SELECT * FROM user_reminds WHERE mch_id = $mchId AND $remindType = 1";
    $userList = $db->fetch_array($sql);
    if (!$userList) {
      return;
    }

    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$accessToken;
    foreach ($userList as $row) {
      $openId = $row['mp_openid'];
      $postData = array(
                    'touser'       => $openId,
                    'template_id'  => $templateId
                  );
      if ($pagePath) {
        $postData['miniprogram']  = array(
                                        'appid' => APP_ID,
                                        'pagepath'  => $pagePath 
                                      );
      }
      foreach ($paramData as $key=>$value) {
        $postData['data'][$key] = array('value'=>urlencode($value));
      }
      $r = httpPost($url, urldecode(json_encode($postData)));
      echo 'send template message '.$r.PHP_EOL;
    }
  }

  //佳博云打印外卖订单
  function print_waimai_receipt($mchId, $outTradeNo)
  {
    global $db;

    $sql = "SELECT jiabo_device_no FROM mch_waimai_configs WHERE mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $deviceNo = $row['jiabo_device_no'];
    if (!$deviceNo) {
      return;
    }

    $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
    $ret = $db->fetch_row($sql);
    $merchantName = $ret['merchant_name'];

    $sql = "SELECT * FROM member_waimai_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    
    $msgDetail = "<gpWord Align=1 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>**随手惠外卖**</gpWord>";
    $msgDetail .= "<gpBr/>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>$merchantName</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>订 单 号：$outTradeNo</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>下单时间：$row[created_at]</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>配送时间：$row[delivery_time]</gpWord>";
    $msgDetail .= "<gpBr/>";
    if ($row["remark"]) {
      $msgDetail .= "<gpWord Align=0 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>备注:$row[remark]</gpWord>";
    }
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>----------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>点单明细</gpWord>";
    $msgDetail .= "<gpBr/>";
    
    $orderData = unserialize($row['detail']);
    foreach ($orderData as $v) {
      $msgDetail .= "<gpTR2 Type=0><td>$v[dish]</td><td>X$v[total]</td></gpTR2>";
    }
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>小  计: $row[amount]</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>包装费: $row[package_cost]</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>配送费: $row[delivery_cost]</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>应  收: $row[total_amount]</gpWord>";
    $msgDetail .= "<gpBr/>";
    if ($row['contact_name']) {
      $msgDetail .= "<gpWord Align=0 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>$row[contact_name] $row[contact_mobile]</gpWord>";
    }
    if ($row['contact_address']) {
      $msgDetail .= "<gpWord Align=0 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>地址：$row[contact_address]</gpWord>";
    }
    $msgDetail .= "<gpBr/>";
    $url  = 'https://coupons.keyouxinxi.com/scan_trade.php?no='.$outTradeNo;
    $msgDetail .= "<gpQRCode Align=1 Size=7 Error=M>$url</gpQRCode>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>店员微信扫码查看订单</gpWord>";
    $msgDetail .= "<gpBr/><gpBr/>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>技术支持：随手惠管家</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>电话：18621769763</gpWord>";
    $msgDetail .= "<gpBr/>";
    $msgDetail .= "<gpCut/>";

    jiabo_pos_print($deviceNo, $msgDetail, $outTradeNo);
  }

  //佳博云商城打印订单
  function print_mall_receipt($mchId, $outTradeNo)
  {
    global $db;

    $sql = "SELECT jiabo_device_no FROM mch_mall_configs WHERE mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $deviceNo = $row['jiabo_device_no'];
    if (!$deviceNo) {
      return;
    }

    $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
    $ret = $db->fetch_row($sql);
    $merchantName = $ret['merchant_name'];

    $sql = "SELECT * FROM member_mall_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    
    $msgDetail = "<gpWord Align=1 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>**随手惠商城**</gpWord>";
    $msgDetail .= "<gpBr/>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>$merchantName</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>订 单 号：$outTradeNo</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>下单时间：$row[created_at]</gpWord>";
    $msgDetail .= "<gpBr/>";
    if ($row["remark"]) {
      $msgDetail .= "<gpWord Align=0 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>备注:$row[remark]</gpWord>";
    }
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>----------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>订单明细</gpWord>";
    $msgDetail .= "<gpBr/>";
    
    $orderData = unserialize($row['detail']);
    foreach ($orderData as $v) {
      $msgDetail .= "<gpTR3 Type=0><td>$v[title]</td><td>$v[price]</td><td>X$v[total]</td></gpTR3>";
    }
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>小  计: $row[amount]</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>快递费: $row[delivery_cost]</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>应  收: $row[total_amount]</gpWord>";
    $msgDetail .= "<gpBr/>";
    if ($row['contact_name']) {
      $msgDetail .= "<gpWord Align=0 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>$row[contact_name] $row[contact_mobile]</gpWord>";
    }
    if ($row['contact_address']) {
      $msgDetail .= "<gpWord Align=0 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>地址：$row[contact_address]</gpWord>";
    }
    $msgDetail .= "<gpBr/>";
    $url  = 'https://coupons.keyouxinxi.com/scan_trade.php?no='.$outTradeNo;
    $msgDetail .= "<gpQRCode Align=1 Size=7 Error=M>$url</gpQRCode>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>店员微信扫码查看订单</gpWord>";
    $msgDetail .= "<gpBr/><gpBr/>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>技术支持：随手惠管家</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>电话：18621769763</gpWord>";
    $msgDetail .= "<gpBr/>";
    $msgDetail .= "<gpCut/>";

    jiabo_pos_print($deviceNo, $msgDetail, $outTradeNo);
  }

  //佳博云打印在线点单
  function print_ordering_receipt($data)
  {
    global $db;
    $mchId = $data['sub_mch_id'];
    $outTradeNo = $data['out_trade_no'];

    $sql = "SELECT jiabo_device_no FROM mch_ordering_configs WHERE mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $deviceNo = $row['jiabo_device_no'];
    if (!$deviceNo) {
      return;
    }

    $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
    $ret = $db->fetch_row($sql);
    $merchantName = $ret['merchant_name'];

    $sql = "SELECT * FROM member_ordering_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    
    $msgDetail = "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>$merchantName</gpWord>";
    $msgDetail .= "<gpBr/>";
    $payTitle  = $row['is_pay'] ? '已支付' : '未支付';
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>$payTitle</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>订 单 号：$outTradeNo</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>下单时间：$row[created_at]</gpWord>";
    if ($row['contact_name']) {
      $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>下单会员：$row[contact_name]($row[grade_title])</gpWord>";
    }
    $msgDetail .= "<gpBr/>";
    if ($row['get_no']) {
      $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>取餐号：#$row[get_no]</gpWord>";
    }
    $msgDetail .= "<gpBr/>";
    if ($row['table_name']) {
      $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>桌号：$row[table_name]</gpWord>";
    }
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>点单明细</gpWord>";
    
    $orderData = unserialize($row['detail']);
    foreach ($orderData as $v) {
      $msgDetail .= "<gpTR2 Type=0><td>$v[dish]</td><td>X$v[total]</td></gpTR2>";
    }
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>应    收:$row[amount]元</gpWord>";
    if (isset($data['save']) && $data['save'] > 0) {
      $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>会员优惠:$data[save]元</gpWord>";
    }
    if (isset($data['consume_recharge']) && $data['consume_recharge'] > 0) {
      $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>储值支付:$data[consume_recharge]元</gpWord>";
    }
    if ($row['is_pay'] && $row['cash_amount']) {
      $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>实    收:$row[cash_amount]元</gpWord>";
    }
    $msgDetail .= "<gpBr/>";
    if ($row["remark"]) {
      $msgDetail .= "<gpWord Align=0 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>备注:$row[remark]</gpWord>";
    }
    $msgDetail .= "<gpBr/><gpBr/>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>技术支持：随手惠管家</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>电话：18621769763</gpWord>";
    $msgDetail .= "<gpBr/>";
    $msgDetail .= "<gpCut/>";

    jiabo_pos_print($deviceNo, $msgDetail, $outTradeNo);
  }

  //佳博云打印，加菜
  function print_ordering_append_receipt($data)
  {
    global $db;
    $mchId = $data['sub_mch_id'];
    $outTradeNo = $data['out_trade_no'];
    $appendDishes = $data['append_dishes'];

    $sql = "SELECT jiabo_device_no FROM mch_ordering_configs WHERE mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $deviceNo = $row['jiabo_device_no'];
    if (!$deviceNo) {
      return;
    }

    $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
    $ret = $db->fetch_row($sql);
    $merchantName = $ret['merchant_name'];

    $sql = "SELECT * FROM member_ordering_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    
    $msgDetail = "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>$merchantName</gpWord>";
    $msgDetail .= "<gpBr/>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>加菜</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>订 单 号：$outTradeNo</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>下单时间：$row[created_at]</gpWord>";
    if ($row['contact_name']) {
      $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>下单会员：$row[contact_name]($row[grade_title])</gpWord>";
    }
    $msgDetail .= "<gpBr/>";
    if ($row['table_name']) {
      $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=1 Hsize=1 Reverse=0 Underline=0>桌号：$row[table_name]</gpWord>";
    }
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>点单明细</gpWord>";
    
    foreach ($appendDishes as $v) {
      $msgDetail .= "<gpTR2 Type=0><td>$v[dish]</td><td>X$v[total]</td></gpTR2>";
    }
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>--------------------------------</gpWord>";
    $msgDetail .= "<gpWord Align=0 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>应    收:$row[amount]元</gpWord>";
    $msgDetail .= "<gpBr/>";
    if ($row["remark"]) {
      $msgDetail .= "<gpWord Align=0 Bold=1 Wsize=1 Hsize=1 Reverse=0 Underline=0>备注:$row[remark]</gpWord>";
    }
    $msgDetail .= "<gpBr/><gpBr/>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>技术支持：随手惠管家</gpWord>";
    $msgDetail .= "<gpWord Align=1 Bold=0 Wsize=0 Hsize=0 Reverse=0 Underline=0>电话：18621769763</gpWord>";
    $msgDetail .= "<gpBr/>";
    $msgDetail .= "<gpCut/>";

    jiabo_pos_print($deviceNo, $msgDetail, $outTradeNo, $reprint=1);
  }

  function jiabo_pos_print($deviceNo, $msgDetail, $outTradeNo, $reprint=0)
  {
    $memberCode = JIABO_MEMBER_CODE;
    $apiKey     = JIABO_API_KEY;
    $reqTime = getMillisecond();
    $securityCode = md5($memberCode.$deviceNo.$outTradeNo.$reqTime.$apiKey);
    $url = 'http://api.poscom.cn/apisc/sendMsg';
    $content['charset'] = 1;
    $content['reqTime'] = $reqTime;
    $content['memberCode'] = $memberCode;
    $content['deviceNo'] = $deviceNo;
    $content['securityCode'] = $securityCode;
    // 打印内容
    $content['msgDetail'] = $msgDetail;
    $content['msgNo'] = $outTradeNo;
    // reprint 是否验证订单编号，1：不验证订单编号，可重新打印订单
    $content['reprint'] = $reprint;
    //$content['reprint'] = $reprint;  
    // multi 是否多订单模式，默认0：为单订单模式，1：多订单模式，
    // 多订单模式下 $msgDetail 为json格式，格式为{"ordernum001":"msgDetail001","ordernum002":"msgDetail002"}
    // 多订单模式下订单编号不能重复，必须填写。建议最大订单数为50
    $content['multi'] = 0;
    // 打印类型          
    $content['mode'] = 2;
    // 打印联数   
    $content['times'] = 1;  
    $res = httpPost($url, $content);
    print_r($res);
  }

  function profitsharingfinish($mchId, $transactionId)
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
                'description'    => '拼团成功资金解冻给商户'
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
      echo 'together profitsharingfinish failed '.$transactionId.PHP_EOL;
    }
  }

  function getEncrypt($str){
     $url = 'https://suishouh.applinzi.com/encrypt.php';
     $data = array('string'=>$str);
     $sign = httpSSLPost($url, $data);
     return $sign;
  } 
