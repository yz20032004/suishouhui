<?php
  require_once 'common.php';
  require_once dirname(__FILE__).'/lib/WxPay.Api.php';
  require_once dirname(__FILE__).'/unit/WxPay.JsApiPay.php';
  require_once dirname(__FILE__).'/unit/log.php';

  $action   = $_GET['action'];
  $now = date('Y-m-d H:i:s');

  switch ($action) {
    case 'cashpay':
      $subMchId = $_GET['sub_mch_id'];
      $openId   = $_GET['openid'];
      $uid      = $_GET['uid'];
      $username = $_GET['username'];
      $shopId      = $_GET['shop_id'];
      $shopName    = $_GET['shop_name'];
      $trade       = $_GET['trade'];
      $qrData = $subMchId.'#'.$trade.'#'.$uid.'#'.$shopName.$username.'#'.$shopId;
      $scene = substr(md5($openId.$now), 8, 16);
      $redis->hset('keyou_pay_qrcodes', $scene, $qrData);
      $redis->expire('keyou_pay_qrcodes', 600);

      $pointRules = unserialize($redis->hget('keyou_mch_point_rules', $subMchId));
      $getPoint = floor($trade / $pointRules['award_need_consume']);

      echo json_encode(array('key'=>$scene, 'get_point'=>$getPoint));
      break;
    case 'get_detail':
      $key = $_GET['key'];
      $subOpenId = $_GET['sub_openid'];
      $isMember  = true;
      $grade     = $_GET['grade'];
      $payAction = $_GET['pay_action']; //scan扫收银员码买单,self顾客自助买单,cashpay收银员现金收款

      $str = $redis->hget('keyou_pay_qrcodes', $key);
      $data = explode('#', $str);
      $mchId = $data[0];
      $trade = $data[1];

      $getPoint = intval($trade);
      $pointTitle = $reduceTitle = $discountTitle = $awardTitle = $memberDayTitle = $gradeTitle = '';
      $couponName = '';
      $awardCouponId = $awardTotal = $save = $reduce = $discount = $memberDiscount = $memberDayPointSpeed = 0;
      $pointSpeed = 1;
      $consume    = $trade;
      $marketingData = array();

      //是否有等级折扣信息
      if ($grade && $grade != 'undefined') {
        $sql = "SELECT name, discount, point_speed FROM app_grades WHERE mch_id = $mchId AND grade = $grade";
        $row = $db->fetch_row($sql);
        $gradeTitle = $row['name'];
        if ($row['discount'] > 0) {
          $memberDiscount = round(($trade) * (10 - $row['discount']) / 10, 2);
        }
        if ($row['point_speed']) {
          $pointSpeed = round($row['point_speed'], 1);
        }
      }

      //是否有营销活动,按实付金额进行计算
      $today = date('Y-m-d');
      if ($isMember) {
        $sql = "SELECT coupon_id, coupon_total, award_condition, consume, campaign_type FROM campaigns WHERE mch_id = '$mchId' AND date_start <= '$today' AND date_end >= '$today' AND is_stop = 0 AND consume <= $trade";
        $sql .= " AND campaign_type = 'rebate'";
        $ret = $db->fetch_row($sql);
        if ($ret) {
          $marketingData['rebate'] = $ret;
        }

        //查询会员日活动
        //会员日活动与返券活动互斥，优先使用会员日活动
        $day = date('w');
        $sql = "SELECT title, day, point_speed, coupon_id, coupon_total, award_condition, consume, campaign_type, reduce, discount, reduce_max FROM campaigns WHERE mch_id = '$mchId' AND campaign_type = 'member_day' AND is_stop = 0 AND day = '$day'";

        $row = $db->fetch_row($sql);
        if ($row) {
          $memberDayTitle = $row['title'];
          if ($row['point_speed'] > 1) {
            $memberDayPointSpeed = round($row['point_speed'], 1);
          } else if ($row['discount'] > 0) {
            $row['campaign_type'] = 'discount';
            $discountTitle .= $row['title'];
            $marketingData['discount'] = $row;
          } else if ($row['reduce']) {
            $reduceTitle .= $row['title'];
            $row['campaign_type'] = 'reduce';
            $marketingData['reduce'] = $row;
          } else if ($row['coupon_id']) {
            $awardTitle .= $row['title'];
            $row['campaign_type'] = 'rebate';
            $marketingData['rebate'] = $row;
          }
        }

        if (isset($marketingData['discount'])) {
          $discount = round($trade * (10 - $row['discount']) / 10, 2);
          $discountTitle .= '买单立享'.$row['discount'].'折';
          if ($row['reduce_max']) {
            $discount = $row['reduce_max'] >= $discount ? $discount : $row['reduce_max'];
            if ($row['reduce_max'] < $discount) {
              $discountTitle .= '，最高优惠'.$row['reduce_max'];
            }
          }
        }

        if ($memberDiscount > $discount) {
          $discount = 0;
        } else {
          $memberDiscount = 0;
        }
        //memberdiscount与discount取最大值，折扣不同享
        $consume = $trade - $memberDiscount - $discount; 
        if (isset($marketingData['reduce'])) {
          $reduce = intval($consume / $row['consume']) * $row['reduce'];
          $reduceTitle .= '每消费满'.$row['consume'].'立减'.$row['reduce'].'元';
          if ($row['reduce_max']) {
            $reduce = $row['reduce_max'] >= $reduce ? $reduce : $row['reduce_max'];
            if ($row['reduce_max'] < $reduce) {
              $reduceTitle .= '，最高立减'.$row['reduce_max'];
            }
          }
        }
        $consume = $consume - $reduce;

        if (isset($marketingData['rebate'])) {
          $awardCouponId = $marketingData['rebate']['coupon_id'];
          $sql = "SELECT name FROM coupons WHERE id = $awardCouponId AND balance > 0";
          $r   = $db->fetch_row($sql);
          if ($r['name']) {
            $couponName = $r['name'];
            $awardTitle .= "消费返券";
            if ('ge' == $row['award_condition']) {
              $awardTotal = intval($consume/$marketingData['rebate']['consume']);
              if (0 == $awardTotal) {
                $awardCouponId = 0;
                $couponName    = '';
              }
            } else {
              if ($consume >= $marketingData['rebate']['consume']) {
                $awardTotal = 1;
              } else {
                $awardTotal = $awardCouponId = 0;
                $couponName = '';
              }
            }
          }
        }
      }
      $pointRules = unserialize($redis->hget('keyou_mch_point_rules', $mchId));
      $pointTitle = '实付'.$pointRules['award_need_consume'].'元返1积分';
      if ($memberDayPointSpeed) {
        if ($memberDayPointSpeed >= $pointSpeed) {
          $pointSpeed = $memberDayPointSpeed;
          $pointTitle .= "\n".$memberDayTitle.$pointSpeed.'倍加速';
        } else {
          $pointTitle .= ",".$gradeTitle.$pointSpeed.'倍加速';
        }
      } else if ($pointSpeed > 1) {
        $pointTitle .= ",".$gradeTitle.$pointSpeed.'倍加速'; 
      }
      $getPoint = floor($pointSpeed * $consume / $pointRules['award_need_consume']);
      $canCash = $pointRules['can_cash'];
      $exchangeNeedPoints = $pointRules['exchange_need_points'];

      $memberCoupons = array();
      //是否有可用优惠券
      if ($isMember) {
        $sql = "SELECT coupon_type, amount, discount, coupon_name, coupon_id, COUNT(id) AS total FROM member_coupons WHERE openid = '$subOpenId' AND mch_id = '$mchId' AND date_start <='$now' AND date_end >='$now' AND status = 1 AND consume_limit <= $trade AND coupon_type != 'gift' GROUP BY coupon_id";
        $ret = $db->fetch_array($sql);
        if (count($ret) > 0) {
          $memberCoupons = $ret;
        }
      }
      $save = $trade - $consume;
    
      $ret = array('mch_id'=>$mchId, 'trade'=>$trade, 'consume'=>$consume, 'get_point'=>$getPoint, 'award_coupon_id'=>$awardCouponId, 'award_coupon_name'=>$couponName, 'award_coupon_total'=>$awardTotal, 'member_coupons'=>$memberCoupons, 'reduce'=>$reduce, 'discount'=>$discount, 'member_discount'=>$memberDiscount, 'save'=>$save, 'point_speed'=>$pointSpeed, 'point_title'=>$pointTitle, 'reduce_title'=>$reduceTitle, 'discount_title'=>$discountTitle, 'is_member'=>$isMember, 'can_cash'=>$canCash, 'exchange_need_points'=>$exchangeNeedPoints, 'award_title'=>$awardTitle);
      echo json_encode($ret);
      break;
    case 'refresh_detail':
      $key = $_GET['key'];
      $subOpenId = $_GET['sub_openid'];
      $isMember  = true;
      $grade     = $_GET['grade'];
      $useCouponAmount  = $_GET['use_coupon_amount'];
      $useRecharge      = $_GET['use_recharge'];
      $usePoint         = $_GET['use_point'];
      $pointSpeed = 1;
      $consumeRecharge = $consumePoint = $discount = 0;
      $marketingData = array();

      $str = $redis->hget('keyou_pay_qrcodes', $key);
      $data = explode('#', $str);
      $mchId = $data[0];
      $trade = $data[1];
      $consume = $trade;

      $couponName = '';
      $pointTitle = $reduceTitle = $discountTitle = $awardTitle = '';
      $awardCouponId = $awardTotal = $reduce = $pointAmount = $memberDiscount = 0;

      //先用会员等级折扣，再用优惠券
      if ($grade) {
        $sql = "SELECT name, discount, point_speed FROM app_grades WHERE mch_id = $mchId AND grade = $grade";
        $row = $db->fetch_row($sql);
        $gradeTitle = $row['name'];
        if ($row['discount'] > 0) {
          $memberDiscount = round($trade * (10 - $row['discount']) / 10, 2);
        }
        if ($row['point_speed']) {
          $pointSpeed = round($row['point_speed'], 1);
        }
      }

      //是否有营销活动,按实付金额进行计算
      $today = date('Y-m-d');
      if ($isMember) {
        $sql = "SELECT coupon_id, coupon_total, award_condition, consume, campaign_type FROM campaigns WHERE mch_id = '$mchId' AND date_start <= '$today' AND date_end >= '$today' AND is_stop = 0 AND consume <= $trade";
        $sql .= " AND campaign_type = 'rebate'";
        $ret = $db->fetch_row($sql);
        if ($ret) {
          $marketingData['rebate'] = $ret;
        }

        //查询会员日活动
        //会员日活动与折扣、立减、返券活动互斥，优先使用会员日活动
        $day = date('w');
        $sql = "SELECT title, day, point_speed, coupon_id, coupon_total, award_condition, consume, campaign_type, reduce, discount, reduce_max FROM campaigns WHERE mch_id = '$mchId' AND campaign_type = 'member_day' AND is_stop = 0 AND day = '$day'";
        $row = $db->fetch_row($sql);
        if ($row) {
          if ($row['point_speed'] > 1) {
            if ($pointSpeed < $row['point_speed']) {
              $pointSpeed = round($row['point_speed'], 1);
            }
          } else if ($row['discount'] > 0) {
            $row['campaign_type'] = 'discount';
            $discountTitle .= $row['title'];
            $marketingData['discount'] = $row;
          } else if ($row['reduce']) {
            $reduceTitle .= $row['title'];
            $row['campaign_type'] = 'reduce';
            $marketingData['reduce'] = $row;
          } else if ($row['coupon_id']) {
            $awardTitle .= $row['title'];
            $row['campaign_type'] = 'rebate';
            $marketingData['rebate'] = $row;
          }
        }

        if (isset($marketingData['discount'])) {
          $discount = round($consume * (10 - $row['discount']) / 10, 2);
          $discountTitle .= '买单立享'.$row['discount'].'折';
          if ($row['reduce_max']) {
            $discount = $row['reduce_max'] >= $discount ? $discount : $row['reduce_max'];
            if ($row['reduce_max'] < $discount) {
              $discountTitle .= '，最高优惠'.$row['reduce_max'];
            }
          }
        }

        if ($memberDiscount > $discount) {
          $discount = 0;
        } else {
          $memberDiscount = 0;
        }
        //memberdiscount与discount取最大值，折扣不同享
        $consume = $consume - $memberDiscount - $discount; 
      }

      //使用券金额
      $consume = $consume > $useCouponAmount ? $consume - $useCouponAmount : 0;
      //积分优惠
      $pointRules = unserialize($redis->hget('keyou_mch_point_rules', $mchId));
      if ($usePoint) {
        $pointAmount = floor($usePoint / $pointRules['exchange_need_points']);
        $pointAmount = $consume >= $pointAmount ? $pointAmount : floor($consume);
        $consumePoint = $pointAmount * $pointRules['exchange_need_points'];
        $consume = $consume - $pointAmount; 
      }
      //使用储值余额
      if ($useRecharge) {
        $consumeRecharge = $consume >= $useRecharge ? $useRecharge : $consume;
        $consume = $consume - $consumeRecharge;
      }

      if (isset($marketingData['reduce'])) {
        $reduce = intval($consume / $row['consume']) * $row['reduce'];
        $reduceTitle .= '每消费满'.$row['consume'].'立减'.$row['reduce'].'元';
        if ($row['reduce_max']) {
          $reduce = $row['reduce_max'] >= $reduce ? $reduce : $row['reduce_max'];
          if ($row['reduce_max'] < $reduce) {
            $reduceTitle .= '，最高立减'.$row['reduce_max'];
          }
        }
        $consume = $consume - $reduce;
      }

      if (isset($marketingData['rebate'])) {
        $awardCouponId = $marketingData['rebate']['coupon_id'];
        $sql = "SELECT name FROM coupons WHERE id = $awardCouponId AND balance > 0";
        $r   = $db->fetch_row($sql);
        if ($r['name']) {
          $couponName = $r['name'];
          $awardTitle .= "消费返券";
          if ('ge' == $row['award_condition']) {
            $awardTotal = intval($consume/$marketingData['rebate']['consume']);
            if (0 == $awardTotal) {
              $awardCouponId = 0;
              $couponName    = '';
            }
          } else {
            if ($consume >= $marketingData['rebate']['consume']) {
              $awardTotal = 1;
            } else {
              $awardTotal = $awardCouponId = 0;
              $couponName = '';
            }
          }
        }
      }

      $getPoint = intval($pointSpeed * $consume / $pointRules['award_need_consume']);
      $rechargePointSpeed = $pointRules['recharge_point_speed'];
      $rechargePoint      = floor($consumeRecharge * $rechargePointSpeed / $pointRules['award_need_consume']);
      $rechargePointTitle = '储值消费返'.$rechargePointSpeed.'倍积分';
      $getPoint += $rechargePoint;

      $save = $trade - $consume - $consumeRecharge;
      $ret = array('trade'=>$trade, 'consume'=>$consume, 'get_point'=>$getPoint, 'award_coupon_id'=>$awardCouponId, 'award_coupon_name'=>$couponName, 'award_coupon_total'=>$awardTotal, 'consume_recharge'=>$consumeRecharge, 'consume_point'=>$consumePoint, 'point_amount'=>$pointAmount, 'discount'=>$discount, 'member_discount'=>$memberDiscount, 'reduce'=>$reduce, 'save'=>$save, 'point_speed'=>$pointSpeed, 'recharge_point_speed'=>$rechargePointSpeed, 'recharge_point'=>$rechargePoint, 'recharge_point_title'=>$rechargePointTitle);
      echo json_encode($ret);
      break;
    case 'consume':
      $key = $_GET['key'];
      $openId    = $_GET['openid'];
      $subOpenId = $_GET['sub_openid'];
      $payAction = $_GET['pay_action'];

      $str = $redis->hget('keyou_pay_qrcodes', $key);
      $data = explode('#', $str);
      $mchId = $data[0];
      $createdByUserId = $data[2];
      $createdByUserName = $data[3];
      $shopId            = $data[4];

      $trade    = $_GET['trade'];
      $useCouponId = $_GET['use_coupon_id'];
      $useCouponAmount = $_GET['use_coupon_amount'];
      $useCouponName   = $_GET['use_coupon_name'];
      $useCouponTotal  = $useCouponAmount?1:0;
      $consumeRecharge = $_GET['consume_recharge'];
      $consumePoint    = $_GET['consume_point'];
      $pointAmount     = $_GET['point_amount'];
      $reduce          = $_GET['reduce'];
      $discount        = $_GET['discount'];
      $memberDiscount  = $_GET['member_discount'];
      $getPoint        = $_GET['get_point'];
      $save            = $_GET['save'];
      $consume         = $_GET['consume'];
      $outTradeNo = getOutTradeNo();
      $appId  =  SUISHOUHUI_APP_ID;
      $totalFee = $cashFee = $consume * 100;
      $detail = '门店消费';
      $transactionId = $mchId.$outTradeNo;
      $payType = 4;

      $sql = "INSERT INTO wechat_pays (appid, mch_id, shop_id, pay_type, openid, sub_openid, transaction_id, out_trade_no, trade, save, total_fee, cash_fee, use_coupon_id, use_coupon_name, use_coupon_total, use_coupon_amount, use_recharge, use_point, point_amount, use_reduce, use_discount, member_discount, get_point, detail, created_by_uid, created_by_uname, created_at) VALUES ('$appId', '$mchId', '$shopId', $payType, '$openId', '$subOpenId', '$transactionId', '$outTradeNo', '$trade', '$save', '$totalFee', '$cashFee', '$useCouponId', '$useCouponName', '$useCouponTotal', $useCouponAmount, $consumeRecharge, $consumePoint, $pointAmount, $reduce, $discount, $memberDiscount, $getPoint, '$detail', '$createdByUserId', '$createdByUserName', '$now')";
      file_put_contents('/tmp/sql', $sql.PHP_EOL, FILE_APPEND);
      $db->query($sql);

      $today = date('Y-m-d');
      $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today' AND shop_id = '$shopId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE wechat_pays_today SET trade_total = trade_total + 1, member_trade_total = member_trade_total + 1, consumes = consumes + $cashFee, member_consumes = member_consumes + $cashFee, trade_amount = trade_amount + $trade, use_recharge = use_recharge + $consumeRecharge, use_point = use_point + $consumePoint, point_amount = point_amount + $pointAmount, get_point = $getPoint, use_coupon_total = use_coupon_total + $useCouponTotal, use_coupon_amount = use_coupon_amount + $useCouponAmount, discount = discount + $discount, member_discount = member_discount + $memberDiscount, consumes_other = consumes_other + $cashFee, save = save + $save, updated_at = '$now' WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO wechat_pays_today (mch_id, shop_id, consumes, member_consumes, save, trade_total, member_trade_total, trade_amount, use_recharge, use_coupon_total, use_coupon_amount, use_point, point_amount, get_point, discount, member_discount, consumes_other, date_at, updated_at) VALUES ($mchId, '$shopId', '$cashFee', $cashFee, $save, 1, 1, $trade, $consumeRecharge, $useCouponTotal, $useCouponAmount, $consumePoint, $pointAmount, $getPoint, $discount, $memberDiscount, $cashFee, '$today', '$now')";
      }
      file_put_contents('/tmp/sql', $sql.PHP_EOL, FILE_APPEND);
      $db->query($sql);

      $_GET['is_member'] = 'true';
      $_GET['out_trade_no'] = $outTradeNo;
      $_GET['time_end']  = date('YmdHis');
      $_GET['timestamp'] = $now;
      $_GET['sub_mch_id']    = $mchId;
      $_GET['pay_info'] = $redis->hget('keyou_pay_qrcodes', $key);
      $redis->hset('keyou_pay_result', $key, serialize($_GET));

      //分离会员操作
      $_GET['trade_type']= 'CASHPAY';
      $_GET['job'] = 'update_member';
      $_GET['wx_access_token'] = $redis->hget('keyouxinxi', 'wx_access_token');
      $redis->rpush('member_job_list', serialize($_GET));

      echo $outTradeNo;
      break;
    case 'recharge':
      $openId     = $_GET['openid'];
      $subOpenId  = $_GET['sub_openid'];
      $rechargeId = $_GET['recharge_id'];
      $mchId      = $_GET['mch_id'];
      $createdByUserId = $_GET['created_uid'];
      $createdByUserName = $_GET['created_username'];
      $shopId     = $_GET['shop_id'];
      $shopName   = $_GET['shop_name'];

      $sql = "SELECT touch, award_type, amount, percent FROM app_recharge_rules WHERE id = $rechargeId";
      $row = $db->fetch_row($sql);
      $totalFee = $row['touch'] * 100;
  
      $key = md5($mchId.$subOpenId.$totalFee.time());
      $attach = 'recharge,'.$key.','.implode(',', $row);

      $outTradeNo = getOutTradeNo();
      $transactionId = $mchId.$outTradeNo;
      $data = array(
                   'appid' => SUISHOUHUI_APP_ID,
                   'sub_mch_id' => $mchId,
                   'attach' => $attach,
                   'openid' => $openId,
                   'sub_openid' => $subOpenId,
                   'total_fee'  => $totalFee,
                   'cash_fee'   => $totalFee,
                   'out_trade_no' => $outTradeNo,
                   'transaction_id' => $transactionId,
                   'bank_type'      => '',
                   'time_end'       => date('YmdHis'),
                   'prepay_id'      => '',
                   'created_by_uid' => $createdByUserId,
                   'created_by_uname'  => $createdByUserName,
                   'shop_id'        => $shopId,
                   'shop_name'      => $shopName,
                   'pay_type'       => 4
                    );
      $redis->rpush('keyou_trade_list', serialize($data));

      $data['job'] = 'update_member_recharge';
      $data['wx_access_token'] = $redis->hget('keyouxinxi', 'wx_access_token');
      $redis->rpush('member_job_list', serialize($data));
      $redis->hset('keyou_recharge_result', $key, serialize($data));
      echo 'success';
      break;
    case 'getSmsPrepay':
      $subMchId    = $_GET['mch_id'];
      $subOpenId   = $_GET['openid'];
      $smsTotal    = $_GET['sms_total'];
      $totalFee = $_GET['trade'] * 100;

      $key = md5($subMchId.$subOpenId.$totalFee.time());
      $outTradeNo = getOutTradeNo();
      $body = '购买短信包';
      $attach = implode(',', array('sms', $key, $subMchId, $smsTotal));
      $data = array(
                  'appid'   => SSHGJ_APP_ID,
                  'mch_id'  => KEYOU_MCHID,
                  'nonce_str'  => WxPayApi::getNonceStr(),
                  'sign_type'  => 'MD5',
                  'body'       => $body,
                  'attach'     => $attach,
                  'out_trade_no' => $outTradeNo,
                  'total_fee'    => $totalFee,
                  'openid'       => $subOpenId,
                  'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
                  'notify_url'       => SITE_URL.'/payNotify.php',
                  'trade_type'       => 'JSAPI'
                  );
       $sign = MakePaySign($data);
       $data['sign'] = $sign;
       $xml = ToXml($data);
       //统一下单
       $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
       $response = postXmlCurl($xml, $url, false, 6);
       $responseData = FromXml($response);

       $redis->hset('keyou_prepay_ids', $key, $responseData['prepay_id']);

       $data = array(
                     'appId' =>  SSHGJ_APP_ID,
                     'timeStamp' => (string)time(),
                     'nonceStr'  => $responseData['nonce_str'],
                     'package'   => 'prepay_id='.$responseData['prepay_id'],
                     'signType'  => 'MD5'
                    );
       $sign = MakePaySign($data);
       $data['paySign'] = $sign;
       echo json_encode($data);
       break;
    default:
      break;
  }

	function MakePaySign($values, $type='MD5')
	{
		//签名步骤一：按字典序排序参数
		ksort($values);
		$string = ToUrlParams($values);
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".KEYOU_KEY;
		//签名步骤三：MD5加密
    if ('MD5' == $type) {
  		$string = md5($string);
    } else {
      $string = hash_hmac('sha256', $string, KEYOU_KEY);
    }
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}

