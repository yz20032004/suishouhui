<?php
  require_once 'common.php';
  require_once dirname(__FILE__).'/lib/WxPay.Api.php';
  require_once dirname(__FILE__).'/unit/WxPay.JsApiPay.php';
  require_once dirname(__FILE__).'/unit/log.php';
  require_once dirname(__FILE__).'/huipay/pay.php';

  $action   = $_GET['action'];
  $now = date('Y-m-d H:i:s');

  switch ($action) {
    case 'get_self_counter':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT counter FROM app_counters WHERE mch_id = $mchId AND counter_type = 'self'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_counter':
      $counter = $_GET['counter'];
      $sql = "SELECT mch_id, shop_id, merchant_name, branch_name, name FROM app_counters WHERE counter = $counter";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'selfpay':
      $subMchId = $_GET['sub_mch_id'];
      $openId   = $_GET['openid'];
      $counter  = $_GET['counter'];
      $counterName = $_GET['counter_name'];
      $shopId      = isset($_GET['shop_id'])?$_GET['shop_id']:0;
      $trade    = $_GET['trade'];
      $qrData = $subMchId.'#'.$trade.'#'.$counter.'#'.$counterName.'#'.$shopId;
      $scene = substr(md5($openId.$now), 8, 16);
      $redis->hset('keyou_pay_qrcodes', $scene, $qrData);
      $redis->expire('keyou_pay_qrcodes', 600);

      $sql  = "SELECT id, mch_id, openid, sub_openid, member_cardid, cardnum, grade, grade_title, name, nickname, point, recharge, coupons FROM members WHERE sub_openid = '$openId' AND mch_id = $subMchId";
      $member = $db->fetch_row($sql);

      $pointRules = unserialize($redis->hget('keyou_mch_point_rules', $subMchId));
      $getPoint = floor($trade / $pointRules['award_need_consume']);

      echo json_encode(array('key'=>$scene, 'get_point'=>$getPoint, 'member'=>$member));
      break;
    case 'get_detail':
      $key = $_GET['key'];
      $subOpenId = $_GET['sub_openid'];
      $isMember  = true;
      $grade     = $_GET['grade'];
      $payAction = $_GET['pay_action']; //scan扫收银员码买单,self顾客自助买单,waimai外卖,mall商城

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
        $pointSpeed = round($row['point_speed'], 1);
      }

      //是否有营销活动,按实付金额进行计算
      $today = date('Y-m-d');
      $campaignType = "'rebate'";
      if ('waimai' == $payAction) {
        $campaignType = "'rebate', 'waimai_reduce'";
      }
      if ($isMember) {
        $sql = "SELECT coupon_id, coupon_total, award_condition, consume, reduce, reduce_max, campaign_type FROM campaigns WHERE mch_id = '$mchId' AND date_start <= '$today' AND date_end >= '$today' AND is_stop = 0 AND consume <= $trade";
        $sql .= " AND campaign_type IN ($campaignType)";
        $ret = $db->fetch_array($sql);
        if ($ret) {
          foreach ($ret as $r) {
            if ('rebate' == $r['campaign_type']) {
              $marketingData['rebate'] = $r;
            } 
            if ('waimai_reduce' == $r['campaign_type']) {
              $marketingData['reduce'] = $r;
            }
          }
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
          $reduce = $consume >= $marketingData['reduce']['consume'] ? $marketingData['reduce']['reduce'] : 0;
          $reduceTitle .= '消费满'.$marketingData['reduce']['consume'].'立减'.$reduce.'元';
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
        $sql = "SELECT coupon_type, amount, discount, coupon_name, coupon_id, COUNT(id) AS total FROM member_coupons WHERE openid = '$subOpenId' AND mch_id = '$mchId' AND date_start <='$now' AND date_end >='$now' AND status = 1 AND consume_limit <= $trade";
        if ('self' == $payAction) { //自助买单调取无需服务员确认的优惠券，即排除礼品券
          $sql .= " AND coupon_type IN ('cash', 'discount')";
        } else if ('waimai' == $payAction) {
          $sql .= " AND coupon_type = 'waimai'";
        } else if ('mall' == $payAction) {
          $sql .= " AND coupon_type = 'mall'";
        }
        $sql .=" GROUP BY coupon_id";
        $ret = $db->fetch_array($sql);
        if (count($ret) > 0) {
          $memberCoupons = $ret;
        }
      }
      $wssUrl = $redis->get('keyou_wssurl_'.$key);
      
      $save = $trade - $consume;
    
      //是否有分享有礼活动，只有会员才可以参与
      $payedShareCouponTotal = 0;
      if ($isMember) {
        $payedShares = $redis->hget('keyou_mch_payed_share', $mchId);
        if ($payedShares) {
          $payedShareData = unserialize($payedShares);
          if (time() - strtotime($payedShareData['date_start']) > 0 && time() - strtotime($payedShareData['date_end']) < 0  && $consume >= $payedShareData['consume']) {
            $payedShareCouponTotal = $payedShareData['coupon_total'];
          }
        }
      }
      
      $ret = array('mch_id'=>$mchId, 'trade'=>$trade, 'consume'=>$consume, 'get_point'=>$getPoint, 'award_coupon_id'=>$awardCouponId, 'award_coupon_name'=>$couponName, 'award_coupon_total'=>$awardTotal, 'member_coupons'=>$memberCoupons, 'reduce'=>$reduce, 'discount'=>$discount, 'member_discount'=>$memberDiscount, 'save'=>$save, 'wss_url'=>$wssUrl, 'point_speed'=>$pointSpeed, 'point_title'=>$pointTitle, 'reduce_title'=>$reduceTitle, 'discount_title'=>$discountTitle, 'is_member'=>$isMember, 'can_cash'=>$canCash, 'exchange_need_points'=>$exchangeNeedPoints, 'award_title'=>$awardTitle, 'payed_share'=>$payedShareCouponTotal);
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
      $payAction = $_GET['pay_action']; //scan扫收银员码买单,self顾客自助买单,waimai外卖,mall商城
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
      $campaignType = "'rebate'";
      if ('waimai' == $payAction) {
        $campaignType = "'rebate', 'waimai_reduce'";
      }
      if ($isMember) {
        $sql = "SELECT coupon_id, coupon_total, award_condition, consume, reduce, reduce_max, campaign_type FROM campaigns WHERE mch_id = '$mchId' AND date_start <= '$today' AND date_end >= '$today' AND is_stop = 0 AND consume <= $trade";
        $sql .= " AND campaign_type IN ($campaignType) ORDER BY campaign_type, consume";
        $ret = $db->fetch_array($sql);
        if ($ret) {
          foreach ($ret as $r) {
            if ('rebate' == $r['campaign_type']) {
              $marketingData['rebate'] = $r;
            } 
            if ('waimai_reduce' == $r['campaign_type']) {
              $marketingData['reduce'] = $r;
            }
          }
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
        $reduce = $consume >= $marketingData['reduce']['consume'] ? $marketingData['reduce']['reduce'] : 0;
        $reduceTitle .= '消费满'.$marketingData['reduce']['consume'].'立减'.$reduce.'元';
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
    case 'getPrepay':
      $appId       = $_GET['appid'];
      $subOpenId   = $_GET['openid'];
      $key         = $_GET['key'];
      $payAction   = $_GET['pay_action'];
      $trade    = $_GET['trade'];
      $totalFee = $_GET['consume'] * 100;

      $tmpData = explode('#', $redis->hget('keyou_pay_qrcodes', $key));
      $mchId   = $tmpData[0];

      $profitSharing = 'N';
      $outTradeNo = getOutTradeNo();
      if ('waimai' == $payAction) {
        $body = '外卖消费';
        $basicFeeRate = $redis->hget('keyou_merchant_basic_fee_rate_list', $mchId);
        $marketingFeeRate = $redis->hget('keyou_merchant_marketing_fee_rate_list', $mchId);
        if ($marketingFeeRate - $basicFeeRate > 0) {
          //服务商团购分佣
          $profitSharing = 'Y';
        }
      } else if ('mall' == $payAction) {
        $body = '商城消费';
      } else {
        $body = '门店消费';
      }
      $_GET['timestamp']  = time();
      $_GET['sub_mch_id'] = $mchId;
      $redis->hset('keyou_pay_result', $key, serialize($_GET));

      $attach = 'pay,'.$key;
      $sql = "SELECT appid,  pay_platform FROM mchs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $payPlatForm = $row['pay_platform'];
      if ('suishouhui' == $payPlatForm) {
        $jsApiArgs = postUnifiedorder($appId, $subOpenId, $mchId, $body, $attach, $totalFee);
      } else if ('tenpay' == $payPlatForm) {
        $sql = "SELECT * FROM mch_tenpay_configs WHERE mch_id = $mchId";
        $tenpayConfig = $db->fetch_row($sql);
        $jsApiArgs = postTenpayUnifiedorder($appId, $tenpayConfig, $subOpenId, $mchId, $body, $attach, $totalFee);
      }
      $redis->hset('keyou_prepay_ids', $key, $jsApiArgs['prepay_id']);
      echo json_encode($jsApiArgs);
      break;
    case 'getRechargeNoPayPrepay':
      //储值N倍免单
      $appId      = $_GET['appid'];
      $subOpenId  = $_GET['openid'];
      $mchId      = $_GET['mch_id'];
      $counter    = $_GET['counter'];
      $trade      = $_GET['trade'];
      $originTrade= $_GET['origin_trade'];
      $rechargeDiscount = $_GET['recharge_discount'];
      $totalFee   = $trade * 100;
      $key = substr(md5($mchId.$subOpenId.$totalFee.time()), 8, 16);
      $attach = 'rechargenopay,'.$key.','.implode(',', array($trade, $originTrade, $rechargeDiscount, $counter));
      $body = '储值';

      $sql = "SELECT appid,  pay_platform FROM mchs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $payPlatForm = $row['pay_platform'];
      if ('suishouhui' == $payPlatForm) {
        $jsApiArgs = postUnifiedorder($appId, $subOpenId, $mchId, $body, $attach, $totalFee);
      } else if ('tenpay' == $payPlatForm) {
        $sql = "SELECT * FROM mch_tenpay_configs WHERE mch_id = $mchId";
        $tenpayConfig = $db->fetch_row($sql);
        $jsApiArgs = postTenpayUnifiedorder($appId, $tenpayConfig, $subOpenId, $mchId, $body, $attach, $totalFee);
      }
      $redis->hset('keyou_prepay_ids', $key, $jsApiArgs['prepay_id']);
      echo json_encode($jsApiArgs);
      break;
    case 'getRechargePrepay':
      $appId      = $_GET['appid'];
      $subOpenId  = $_GET['openid'];
      $rechargeId = $_GET['recharge_id'];
      $mchId      = $_GET['mch_id'];

      $sql = "SELECT touch, award_type, amount, percent FROM app_recharge_rules WHERE id = $rechargeId";
      $row = $db->fetch_row($sql);
      $totalFee = $row['touch'] * 100;

      $key = md5($mchId.$subOpenId.$totalFee.time());
      $attach = 'recharge,'.$key.','.implode(',', $row);
      $body = '储值';

      $sql = "SELECT appid,  pay_platform FROM mchs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $payPlatForm = $row['pay_platform'];
      if ('suishouhui' == $payPlatForm) {
        $jsApiArgs = postUnifiedorder($appId, $subOpenId, $mchId, $body, $attach, $totalFee);
      } else if ('tenpay' == $payPlatForm) {
        $sql = "SELECT * FROM mch_tenpay_configs WHERE mch_id = $mchId";
        $tenpayConfig = $db->fetch_row($sql);
        $jsApiArgs = postTenpayUnifiedorder($appId, $tenpayConfig, $subOpenId, $mchId, $body, $attach, $totalFee);
      }
      $redis->hset('keyou_prepay_ids', $key, $jsApiArgs['prepay_id']);
      echo json_encode($jsApiArgs);
      break;
    case 'consume_no_money':
      $key = $_GET['key'];
      $subOpenId = $_GET['openid'];
      $formId    = $_GET['formId'];
      $payAction = $_GET['pay_action'];

      $str = $redis->hget('keyou_pay_qrcodes', $key);
      $data = explode('#', $str);
      $mchId = $data[0];
      $createdByUserId = $data[2];
      $createdByUserName = $data[3];
      $shopId            = $data[4];

      $sql = "SELECT openid FROM members WHERE sub_openid = '$subOpenId' AND mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $openId = $row['openid'];

      $subOpenId   = $_GET['openid'];
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
      $outTradeNo = getOutTradeNo();
      $appId  =  $_GET['appid'];
      $totalFee = $cashFee = 0;

      if ('waimai' == $payAction) {
        $detail = '外卖消费';
        $payFrom= 'waimai';
      } else if ('mall' == $payAction) {
        $detail = '商城消费';
        $payFrom= 'mall';
      } else if ('ordering' == $payAction) {
        $detail = '在线点单';
        $payFrom= 'general';
      } else {
        $detail = '门店消费';
        $payFrom= 'general';
      }
      $transactionId = $mchId.$outTradeNo;
      $payType = 3;

      $sql = "INSERT INTO wechat_pays (appid, mch_id, shop_id, pay_type, pay_from, openid, sub_openid, transaction_id, prepay_id, out_trade_no, trade, save, total_fee, cash_fee, use_coupon_id, use_coupon_name, use_coupon_total, use_coupon_amount, use_recharge, use_point, point_amount, use_reduce, use_discount, member_discount, get_point, detail, created_by_uid, created_by_uname, created_at) VALUES ('$appId', '$mchId', $shopId, $payType, '$payFrom', '$openId', '$subOpenId', '$transactionId', '$formId', '$outTradeNo', '$trade', '$save', '$totalFee', '$cashFee', '$useCouponId', '$useCouponName', '$useCouponTotal', $useCouponAmount, $consumeRecharge, $consumePoint, $pointAmount, $reduce, $discount, $memberDiscount, $getPoint, '$detail', '$createdByUserId', '$createdByUserName', '$now')";
      $db->query($sql);

      $today = date('Y-m-d');
      $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today' AND shop_id = $shopId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE wechat_pays_today SET trade_total = trade_total + 1, member_trade_total = member_trade_total + 1, consumes = consumes + $cashFee, trade_amount = trade_amount + $trade, use_recharge = use_recharge + $consumeRecharge, use_point = use_point + $consumePoint, point_amount = point_amount + $pointAmount, get_point = $getPoint, use_coupon_total = use_coupon_total + $useCouponTotal, use_coupon_amount = use_coupon_amount + $useCouponAmount, member_discount = member_discount + $memberDiscount, updated_at = '$now' WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO wechat_pays_today (mch_id, shop_id, consumes, trade_total, member_trade_total, trade_amount, use_recharge, use_coupon_total, use_coupon_amount, use_point, point_amount, get_point, member_discount, date_at, updated_at) VALUES ($mchId, $shopId, '$cashFee', 1, 1, $trade, $consumeRecharge, $useCouponTotal, $useCouponAmount, $consumePoint, $pointAmount, $getPoint, $memberDiscount,'$today', '$now')";
      }
      $db->query($sql);

      $_GET['is_member'] = 'true';
      $_GET['out_trade_no'] = $outTradeNo;
      $_GET['time_end']  = date('YmdHis');
      $_GET['timestamp'] = $now;
      $_GET['sub_mch_id']    = $mchId;
      $_GET['pay_info'] = $redis->hget('keyou_pay_qrcodes', $key);
      $redis->hset('keyou_pay_result', $key, serialize($_GET));

      if ('scan' == $payAction) {
        $pushMessage = json_encode(array('pay'=>'completed', 'key'=>$key));
        $url     = 'http://sinaapp.keyouxinxi.com/send_message.php?channel='.$key.'&message='.$pushMessage;
        $ret     = httpPost($url);
      }

      //分离会员操作
      $_GET['openid']    = $openId;
      $_GET['sub_openid']= $subOpenId;
      $_GET['trade_type']= 'recharge';
      $_GET['job'] = 'update_member';
      $_GET['wx_access_token'] = $redis->hget('keyouxinxi', 'wx_access_token');
      $redis->rpush('member_job_list', serialize($_GET));

      //会员外卖操作
      if ('waimai' == $payAction) {
        $_GET['job'] = 'update_member_waimai';
        $redis->rpush('member_job_list', serialize($_GET));
      } else if ('ordering' == $payAction) {
        $_GET['job'] = 'update_member_ordering';
        $redis->rpush('member_job_list', serialize($_GET));
      }

      //云喇叭通知
      $soundData = array(
                        'job'     => 'send_sound',
                        'pay_type'=> 'wechat',
                        'get_point'=> 0,
                        'trade_no' => $outTradeNo,
                        'trade'   => $trade,
                        'counter' => $createdByUserId,
                        'save'    => $save,
                        'consume_recharge' => $consumeRecharge
                      );
      $redis->rpush('keyou_mch_job_list', serialize($soundData));

      echo json_encode(array('out_trade_no'=>$outTradeNo));
      break;
    case 'getPayBefor':
      //支付前活动查询
      $mchId = $_GET['mch_id'];
      $today = date('Y-m-d');

      $sql   = "SELECT campaign_type, title, coupon_id, coupon_total, coupon_amount, consume, discount, total FROM campaigns WHERE mch_id = $mchId AND campaign_type IN ('rechargenopay', 'paybuycoupon') AND is_stop = 0 AND date_start <= '$today' AND date_end >= '$today'";
      $data  = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'getPayDirect':
      //支付后活动查询
      $mchId = $_GET['mch_id'];
      $consume = $_GET['consume'];
      $openId  = $_GET['openid'];
      $isMember = $_GET['is_member'];
      $today    = date('Y-m-d');

      $sql = "SELECT grade, coupon_id, coupon_total, award_condition, consume, campaign_type FROM campaigns WHERE mch_id = $mchId AND date_start <= '$today' AND date_end >= '$today' AND is_stop = 0 AND consume <= $consume AND campaign_type IN ('rebate', 'payed_share') ORDER BY consume DESC";
      $row = $db->fetch_row($sql);
      if ($row['grade'] > 0) {
        if (!$isMember) {
          echo false;
          exit();
        }
        $sql = "SELECT grade FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
        $ret = $db->fetch_row($sql);
        $grade = $ret['grade'];
        if ($row['grade'] != $grade) {
          echo false;
          exit();
        }
      } 
      echo json_encode($row);
      break;
    case 'send_websocket_message':
      $channel = $_GET['channel'];
      $message = $_GET['message'];
      $url     = 'http://sinaapp.keyouxinxi.com/send_message.php?channel='.$channel.'&message='.$message;
      $ret     = httpPost($url);
      echo $ret;
      break;
    default:
      break;
  }
