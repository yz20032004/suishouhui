<?php
  /*
   * 执行支付数据入库
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

  while (true) {
    $result = $redis->blpop('keyou_trade_list', 1800);
    if ($result) {
      $data = unserialize($result[1]);

      $appId     = isset($data['sub_appid'])?$data['sub_appid']:$data['appid'];
      $mchId     = isset($data['sub_mch_id'])?$data['sub_mch_id']:$data['mch_id'];
      $openId    = $data['openid'];
      $subOpenId = isset($data['sub_openid'])?$data['sub_openid']:$openId;
      $totalFee  = $data['total_fee'];
      $cashFee   = $data['cash_fee'];
      $couponFee = isset($data['coupon_fee']) ? $data['coupon_fee'] : 0;
      $settlementTotalFee = isset($data['settlement_total_fee']) ? $data['settlement_total_fee'] : 0;
      $outTradeNo  = $data['out_trade_no'];
      $transactionId  = $data['transaction_id'];
      $bankType       = $data['bank_type'];
      $createdAt      = getDateAt($data['time_end']);
      $detail = '门店消费';
      $prepayId = $data['prepay_id'];
      $accessToken = $data['wx_access_token'];

      $sql = "SELECT wechat_fee_rate, ali_fee_rate, marketing_fee_rate FROM mchs WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $wechatFeeRate = $ret['wechat_fee_rate'];
      $aliFeeRate    = $ret['ali_fee_rate'];
      $marketingFeeRate = $ret['marketing_fee_rate'];
      $serviceFee = $serviceFeeWechat = $serviceFeeAlipay = 0;
      if (isset($data['pay_type'])) {
        if ('2' == $data['pay_type']) {
        //支付宝支付
        $serviceFee  = $cashFee * $aliFeeRate >= 1 ? $cashFee * $aliFeeRate : 0;
        $serviceFeeAlipay = $serviceFee;
        $serviceFeeWechat = 0;
        } else if ('1' == $data['pay_type']) {
          //微信支付
          $serviceFee  = $cashFee * $wechatFeeRate >= 1 ? $cashFee * $wechatFeeRate : 0;
          $serviceFeeWechat = $serviceFee;
          $serviceFeeAlipay = 0;
        }
      }
      $attach = explode(',', $data['attach']);
      $payType = $attach[0];
      $key     = $attach[1];
      if ('pay' == $payType) {
        $payInfo = explode('#', $data['pay_info']);
        $createdByUserId = $payInfo[2];
        $createdByUserName = $payInfo[3];
        $shopId            = $payInfo[4];
    
        $trade    = $data['trade'];
        $getPoint = $data['get_point'];
        $consume  = $data['consume'];
        $useCouponId = isset($data['use_coupon_id'])?$data['use_coupon_id']:0;
        $useCouponAmount = isset($data['use_coupon_amount'])?$data['use_coupon_amount']:0;
        $useCouponName   = isset($data['use_coupon_name'])?$data['use_coupon_name']:'';
        $consumeRecharge = isset($data['consume_recharge'])?$data['consume_recharge']:0;
        $consumePoint    = isset($data['consume_point'])?$data['consume_point']:0;
        $pointAmount     = isset($data['point_amount'])?$data['point_amount']:0;
        $reduce          = $data['reduce'];
        $save            = $data['save'] + ($couponFee/100);
        $discount        = $data['discount'];
        $memberDiscount  = $data['member_discount'];
        $isMember        = $data['is_member'];
        $useCouponTotal = $useCouponAmount?1:0;
        $payType         = isset($data['pay_type']) ? $data['pay_type'] : 1; //默认是微信支付
        $payFrom         = 'self' == $data['pay_action'] ? 'general' : $data['pay_action'];
        $waimaiRevenue = $mallRevenue = 0;
        if ('waimai' == $data['pay_action']) {
          $detail = '外卖消费';
          $waimaiRevenue = $cashFee;
          $serviceFee      = $data['cash_fee'] * $marketingFeeRate;
        } else if ('ordering' == $data['pay_action']) {
          $detail = '在线点单';
        } else if ('mall' == $data['pay_action']) {
          $detail = '商城消费';
          $mallRevenue = $cashFee;
        } else {
          $detail = '门店消费';
        }
        $sql = "INSERT INTO wechat_pays (appid, mch_id, shop_id, pay_type, pay_from, openid, sub_openid, out_trade_no, transaction_id, prepay_id, bank, trade, save, total_fee, cash_fee, coupon_fee, settlement_total_fee, use_coupon_id, use_coupon_name, use_coupon_total, use_coupon_amount, use_recharge, use_point, point_amount, use_reduce, use_discount, member_discount, get_point, detail, service_fee, created_by_uid, created_by_uname, created_at) VALUES ('$appId', '$mchId', '$shopId', '$payType', '$payFrom', '$openId', '$subOpenId', '$outTradeNo', '$transactionId', '$prepayId', '$bankType', '$trade', '$save', '$totalFee', '$cashFee', $couponFee, $settlementTotalFee, '$useCouponId', '$useCouponName', '$useCouponTotal', $useCouponAmount, $consumeRecharge, $consumePoint, $pointAmount, $reduce, $discount, $memberDiscount, '$getPoint', '$detail', $serviceFee, '$createdByUserId', '$createdByUserName', '$createdAt')";
        $db->query($sql);
        $affectedRows = $db->affected_rows();
        if ($affectedRows) {
          $payId = $db->get_insert_id();
          $today = date('Y-m-d');
          $now   = date('Y-m-d H:i:s');
          $memberTradeTotal = $isMember ? 1 : 0;
          $memberConsumes   = $isMember ? $cashFee : 0;

          $consumesWechat = '1' == $payType ? $cashFee : 0;
          $consumesAlipay = '2' == $payType ? $cashFee : 0;
          $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today' AND shop_id = '$shopId'";
          $row = $db->fetch_row($sql);
          if ($row['id']) {
            $sql = "UPDATE wechat_pays_today SET trade_total = trade_total + 1, member_trade_total = member_trade_total + $memberTradeTotal, trade_amount = trade_amount + $trade, consumes = consumes + $cashFee,  member_consumes = member_consumes + $memberConsumes, use_coupon_amount = use_coupon_amount + $useCouponAmount, use_recharge = use_recharge + $consumeRecharge, save = save + $save, discount = discount + $discount, member_discount = member_discount + $memberDiscount, use_point = use_point + $consumePoint, get_point = get_point + $getPoint, point_amount = point_amount + $pointAmount, reduce = reduce + $reduce, service_fee = service_fee + $serviceFee, service_fee_wechat = service_fee_wechat + $serviceFeeWechat, service_fee_alipay = service_fee_alipay + $serviceFeeAlipay,  use_coupon_total = use_coupon_total + $useCouponTotal, consumes_wechat = consumes_wechat + $consumesWechat, consumes_alipay = consumes_alipay + $consumesAlipay, coupon_fee = coupon_fee + $couponFee, settlement_total_fee = settlement_total_fee + $settlementTotalFee, waimai_revenue = waimai_revenue + $waimaiRevenue, mall_revenue = mall_revenue + $mallRevenue, updated_at = '$now' WHERE id = $row[id]";
          } else {
            $sql = "INSERT INTO wechat_pays_today (mch_id, shop_id, trade_total, member_trade_total, trade_amount, consumes, member_consumes,  use_coupon_amount, use_recharge, save, discount, member_discount, use_point, get_point, point_amount, reduce, service_fee, service_fee_wechat, service_fee_alipay, coupon_fee, settlement_total_fee, use_coupon_total, consumes_wechat, consumes_alipay, waimai_revenue, mall_revenue, date_at, updated_at) VALUES ($mchId, '$shopId', 1, $memberTradeTotal, $trade, '$cashFee', $memberConsumes, $useCouponAmount, $consumeRecharge, $save, $discount, $memberDiscount, $consumePoint, $getPoint, $pointAmount, $reduce, $serviceFee, $serviceFeeWechat, $serviceFeeAlipay, $couponFee, $settlementTotalFee,  $useCouponTotal, $consumesWechat, $consumesAlipay, $waimaiRevenue, $mallRevenue, '$today', '$now')";
          }
          $db->query($sql);
        }

        if ('waimai' == $data['pay_action'] || 'mall' == $data['pay_action']) {
          continue;
        }
        //支付订单提醒
        switch ($data['pay_type']) {
          case '1':
            $payType = '微信支付';
            break;
          case '2':
            $payType = '支付宝支付';
            break;
          case '3':
            $payType = '储值余额支付';
            break;
          case '4':
            $payType = '现金或其它支付';
            break;
          default:
            break;
        }
        $paramData  = array(
                            'first' => '您有新的收款订单',
                            'keyword1' => $consume.'元',
                            'keyword2' => $payType ,
                            'keyword3' => $createdAt,
                            'keyword4' => $outTradeNo,
                            'remark'   => '订单金额'.$trade.'元，优惠'.$data['save'].'元'
                          );
        $remindData = array(
                        'mch_id' => $mchId,
                        'remind_type'     => 'is_pay',
                        'wx_access_token' => $accessToken,
                        'template_id'     => 'ReKtS6tw5GhzcI5NsIb1D91_WG95nagDYXJyxD407yU',
                        'page_path'       => 'pages/trade/detail?out_trade_no='.$outTradeNo,
                        'param_data'      => $paramData
                      );
        send_user_remind($remindData);
      } else if ('rechargenopay' == $payType) {
        //储值N倍当餐免单，也要记录数据
        $now   = date('Y-m-d H:i:s');
        $shopId          = isset($data['shop_id'])?$data['shop_id']:0;
        $payType = 1;
        $recharge    = $attach[2];
        $originTrade = $attach[3];
        $rechargeDiscount = $attach[4];
        if ('0' == $rechargeDiscount) {
          $consumeRecharge = 0;
          $reduce = $save = $originTrade;
        } else {
          $reduce = $save = $originTrade * (10 - $rechargeDiscount) / 10;
          $consumeRecharge = $originTrade - $reduce;
        }

        //先储值
        $sql = "INSERT INTO wechat_pays (appid, mch_id, shop_id, pay_type, pay_from, openid, sub_openid, transaction_id, prepay_id, out_trade_no, trade, total_fee, cash_fee, service_fee, detail, created_at) VALUES ('$appId', '$mchId', $shopId, $payType, 'recharge', '$openId', '$subOpenId', '$transactionId', '$prepayId', '$outTradeNo', '$recharge', '$totalFee', '$cashFee', $serviceFee, '储值', '$now')";
        $db->query($sql);

        //再消费
        $outTradeNo = getOutTradeNo();
        $detail = '门店消费';
        $transactionId = $mchId.$outTradeNo;
        $payType = 3;
        $prepayId = '';
        $sql = "INSERT INTO wechat_pays (appid, mch_id, shop_id, pay_type, openid, sub_openid, transaction_id, prepay_id, out_trade_no, trade, save, total_fee, cash_fee, use_recharge, use_reduce, detail, created_at) VALUES ('$appId', '$mchId', $shopId, $payType, '$openId', '$subOpenId', '$transactionId', '$prepayId', '$outTradeNo', '$originTrade', '$save', 0, 0, $consumeRecharge, $reduce, '$detail','$now')";
        $db->query($sql);

        $today = date('Y-m-d');
        $consumes = $recharge * 100;
        $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today' AND shop_id = $shopId";
        $row = $db->fetch_row($sql);
        if ($row['id']) {
          $sql = "UPDATE wechat_pays_today SET trade_total = trade_total + 2, member_trade_total = member_trade_total + 2, consumes = consumes + $consumes, trade_amount = trade_amount + $recharge, recharges_total = recharges_total + 1, recharges = recharges + $recharge, use_recharge = use_recharge + $consumeRecharge, save = save + $save, reduce = reduce + $reduce, service_fee = service_fee + $serviceFee, service_fee_wechat = service_fee_wechat + $serviceFeeWechat, consumes_wechat = consumes_wechat + $cashFee, updated_at = '$now' WHERE id = $row[id]";
        } else {
          $sql = "INSERT INTO wechat_pays_today (mch_id, shop_id, consumes, trade_total, member_trade_total, trade_amount,use_recharge, save, reduce, service_fee, service_fee_wechat, consumes_wechat, date_at, updated_at) VALUES ($mchId, $shopId, '$consumes', 2, 2, $recharge, $consumeRecharge, $save, $reduce, $serviceFee, $serviceFeeWechat, $cashFee, '$today', '$now')";
        }
        $db->query($sql);
      } else if ('recharge' == $payType) {
        $attach = explode(',', $data['attach']);
        $recharge = $attach[2];
        $trade    = $recharge;
        $detail = '充值';
        $createdByUserId   = isset($data['created_by_uid']) ? $data['created_by_uid'] : 0;
        $createdByUserName = isset($data['created_by_uname']) ? $data['created_by_uname'] : '';
        $payType         = isset($data['pay_type']) ? $data['pay_type'] : 1; //默认是微信支付
        $shopId          = isset($data['shop_id'])?$data['shop_id']:0;
        $shopName        = isset($data['shop_name'])?$data['shop_name']:0;
        $createdByUserName = $shopName.$createdByUserName;
        $serviceFee      = $data['cash_fee'] * $marketingFeeRate;

        $sql = "INSERT INTO wechat_pays (appid, mch_id, shop_id, openid, sub_openid, pay_type, pay_from, out_trade_no, transaction_id, prepay_id, bank, trade, total_fee, cash_fee, coupon_fee, settlement_total_fee, service_fee, detail, created_by_uid, created_by_uname, created_at) VALUES ('$appId', '$mchId', $shopId, '$openId', '$subOpenId', $payType, 'recharge', '$outTradeNo', '$transactionId', '$prepayId', '$bankType', '$trade', '$totalFee', '$cashFee', $couponFee, $settlementTotalFee, $serviceFee, '$detail', $createdByUserId, '$createdByUserName', '$createdAt')";
        $db->query($sql);

        if ('1' == $payType) {
          $consumesWechat = $cashFee;
          $consumesOther  = 0;
        } else {
          $consumesOther  = $cashFee;
          $consumesWechat = 0;
        }
        $today = date('Y-m-d');
        $now   = date('Y-m-d H:i:s');
        $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today' AND shop_id = $shopId";
        $row = $db->fetch_row($sql);
        if ($row['id']) {
          $sql = "UPDATE wechat_pays_today SET trade_total = trade_total + 1, member_trade_total = member_trade_total + 1, trade_amount = trade_amount + $trade, consumes = consumes + $cashFee,  member_consumes = member_consumes + $cashFee,  service_fee = service_fee + $serviceFee, service_fee_wechat = service_fee_wechat + $serviceFeeWechat, recharges_total = recharges_total + 1, recharges = recharges + $trade, coupon_fee = coupon_fee + $couponFee, settlement_total_fee = settlement_total_fee + $settlementTotalFee, consumes_wechat = consumes_wechat + $consumesWechat, consumes_other = consumes_other + $consumesOther,  updated_at = '$now' WHERE id = $row[id]";
        } else {
          $sql = "INSERT INTO wechat_pays_today (mch_id, shop_id, trade_total, member_trade_total, trade_amount, consumes, member_consumes, service_fee, service_fee_wechat, coupon_fee, settlement_total_fee, recharges_total, recharges, consumes_wechat, consumes_other, date_at, updated_at) VALUES ($mchId, $shopId, 1, 1, $trade, '$cashFee', $cashFee, $serviceFee, $serviceFeeWechat, $couponFee, $settlementTotalFee, 1, $trade, $consumesWechat, $consumesOther, '$today', '$now')";
        }
        $db->query($sql);
      } else if ('sms' == $payType) {
        $subMchId  = $attach[2];
        $smsTotal  = $attach[3];
        $detail    = '购买'.$smsTotal.'条短信';
        $trade     = $totalFee/100;

        $sql = "INSERT INTO app_wechat_pays (appid, mch_id, openid, out_trade_no, transaction_id, prepay_id, bank, trade, total_fee, service_fee, detail, created_at) VALUES ('$appId', '$subMchId', '$openId', '$outTradeNo', '$transactionId', '$prepayId', '$bankType', '$trade', '$totalFee', $serviceFee, '$detail', '$createdAt')";
        $db->query($sql);

        $sql = "UPDATE users SET sms_total = sms_total + $smsTotal WHERE mch_id = $subMchId";
        $db->query($sql);
      } else if ('function' == $payType) {
        $openId    = $attach[1];
        $functionName = $attach[2];
        $detail    = '购买功能'.$functionName;
        $trade     = $totalFee/100;

        $sql = "SELECT mch_id FROM users WHERE openid = '$openId'";
        $row = $db->fetch_row($sql);
        $subMchId = $row['mch_id'];

        $sql = "INSERT INTO app_wechat_pays (appid, mch_id, openid, out_trade_no, transaction_id, prepay_id, bank, trade, total_fee, service_fee, detail, created_at) VALUES ('$appId', '$subMchId', '$openId', '$outTradeNo', '$transactionId', '$prepayId', '$bankType', '$trade', '$totalFee', $serviceFee, '$detail', '$createdAt')";
        $db->query($sql);

        $sql = "UPDATE mchs SET $functionName = '1' WHERE mch_id = $subMchId";
        $db->query($sql);
      } else if ('groupon' == $payType) {
        $grouponId = $attach[2];
        $buyTotal  = $attach[3];
        $subMchId  = $attach[4];
        $couponId  = $attach[5];
        $couponTotal = $attach[6];
        $distributeId= isset($attach[7])?$attach[7]:0;
        $serviceFee  = $cashFee * $marketingFeeRate;

        if ($appId != SUISHOUHUI_APP_ID) {
          //判断拼团来自平台小程序还是商户独立小程序
          //$openId = $subOpenId;
        }
        $sql = "INSERT INTO wechat_groupon_pays (appid, mch_id, openid, groupon_id, distribute_id, coupon_id, coupon_total, buy_total, out_trade_no, transaction_id, prepay_id, bank, total_fee, cash_fee, coupon_fee, settlement_total_fee, created_at) VALUES ('$appId', '$subMchId', '$subOpenId', $grouponId, $distributeId, $couponId, $couponTotal, $buyTotal, '$outTradeNo', '$transactionId', '$prepayId', '$bankType', '$totalFee', '$cashFee', $couponFee, $settlementTotalFee, '$createdAt')";
        $db->query($sql);
        $affectedRows = $db->affected_rows();
        if ($affectedRows) {
          $today = date('Y-m-d');
          $sql = "SELECT id FROM wechat_groupon_pays_today WHERE mch_id = $subMchId AND date_at = '$today'";
          $row = $db->fetch_row($sql);
          $buyCouponTotal = $buyTotal * $couponTotal;
          if ($row['id']) {
            $sql = "UPDATE wechat_groupon_pays_today SET trade_total = trade_total + 1, total = total + $buyTotal, trade = trade + $cashFee, coupon_total = coupon_total + $buyCouponTotal, revenue = revenue + $totalFee WHERE id = $row[id]";
          } else {
            $sql = "INSERT INTO wechat_groupon_pays_today (mch_id, trade_total, total, trade, coupon_total, revenue, date_at) VALUES ($subMchId, 1, $buyTotal, $cashFee, $buyCouponTotal, $totalFee, '$today')";
          }
          $db->query($sql);

          $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today'";
          $row = $db->fetch_row($sql);
          if ($row['id']) {
            $sql = "UPDATE wechat_pays_today SET trade_amount = trade_amount + $trade, consumes = consumes + $cashFee, service_fee = service_fee + $serviceFee, service_fee_groupon = service_fee_groupon + $serviceFee, updated_at = '$now' WHERE id = $row[id]";
          } else {
            $sql = "INSERT INTO wechat_pays_today (mch_id, trade_amount, consumes, service_fee, service_fee_wechat, date_at, updated_at) VALUES ($mchId, $trade, $cashFee, $serviceFee, $serviceFee, '$today', '$now')";
          }
          $db->query($sql);
        }
      } else if ('together' == $payType) {
        $togetherId = $attach[2];
        $buyTotal  = $attach[3];
        $subMchId  = $attach[4];
        $couponId  = $attach[5];
        $isHead    = $attach[6];
        $togetherNo = $attach[7];
        $serviceFee = $cashFee * $marketingFeeRate;

        if ('true' == $isHead) {
          $sql = "SELECT expire_times FROM mch_togethers WHERE id = $togetherId";
          $row = $db->fetch_row($sql);
          $togetherExpiredAt = date('Y-m-d H:i:s', strtotime('+ '.$row['expire_times'].'hours'));
        } else {
          $sql = "SELECT together_expired_at FROM wechat_groupon_pays WHERE mch_id = $subMchId AND groupon_id = $togetherId AND together_no = '$togetherNo'";
          $row = $db->fetch_row($sql);
          $togetherExpiredAt = $row['together_expired_at'];
        }

        $couponTotal = 1;
        if ($appId != SUISHOUHUI_APP_ID) {
          //判断拼团来自平台小程序还是商户独立小程序
          $openId = $subOpenId;
        }
        $sql = "INSERT INTO wechat_groupon_pays (appid, mch_id, openid, groupon_id, is_together, together_no, is_head, coupon_id, coupon_total, buy_total, out_trade_no, transaction_id, prepay_id, bank, total_fee, cash_fee, coupon_fee, settlement_total_fee, together_expired_at, created_at) VALUES ('$appId', '$subMchId', '$openId', $togetherId, 1, '$togetherNo', $isHead, $couponId, $couponTotal, $buyTotal, '$outTradeNo', '$transactionId', '$prepayId', '$bankType', '$totalFee', '$cashFee', $couponFee, $settlementTotalFee, '$togetherExpiredAt', '$createdAt')";
        $db->query($sql);
        $affectedRows = $db->affected_rows();
        if ($affectedRows) {
          $today = date('Y-m-d');
          $sql = "SELECT id FROM wechat_groupon_pays_today WHERE mch_id = $subMchId AND date_at = '$today'";
          $row = $db->fetch_row($sql);
          if ($row['id']) {
            if ('true' == $isHead) {
              $sql = "UPDATE wechat_groupon_pays_today SET together_total = together_total + 1,service_fee = service_fee + $serviceFee, service_fee_wechat = service_fee_wechat + $serviceFee WHERE id = $row[id]";
            }
          } else {
            if ('true' == $isHead) {
              $sql = "INSERT INTO wechat_groupon_pays_today (mch_id, together_total, service_fee, service_fee_wechat, date_at) VALUES ($subMchId, 1, $serviceFee, $serviceFeeWechat, '$today')";
            }
          }
          $db->query($sql);

          $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today'";
          $row = $db->fetch_row($sql);
          if ($row['id']) {
            $sql = "UPDATE wechat_pays_today SET trade_amount = trade_amount + $trade, consumes = consumes + $cashFee, service_fee = service_fee + $serviceFee, service_fee_groupon = service_fee_groupon + $serviceFee, updated_at = '$now' WHERE id = $row[id]";
          } else {
            $sql = "INSERT INTO wechat_pays_today (mch_id, trade_amount, consumes, service_fee, service_fee_wechat, date_at, updated_at) VALUES ($mchId, $trade, $cashFee, $serviceFee, $serviceFee, '$today', '$now')";
          }
          $db->query($sql);
        }
      } else if ('vipcard' == $payType) {
        $attach = explode(',', $data['attach']);
        $grade  = $attach[2];
        $gradeName = $attach[3];
        $trade  = $cashFee/100;
        $serviceFee = $cashFee * $marketingFeeRate;

        $sql = "INSERT INTO wechat_vipcard_pays (appid, mch_id, openid, sub_openid, vipcard_grade, vipcard_title, out_trade_no, transaction_id, prepay_id, bank, trade, cash_fee, service_fee, created_at) VALUES ('$appId', '$mchId', '$openId', '$subOpenId', $grade, '$gradeName', '$outTradeNo', '$transactionId', '$prepayId', '$bankType', '$trade', '$cashFee', $serviceFee, '$createdAt')";
        echo $sql.PHP_EOL;
        $db->query($sql);
        $affectedRows = $db->affected_rows();
        if ($affectedRows) {
          $today = date('Y-m-d');
          $now   = date('Y-m-d H:i:s');
          $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today'";
          $row = $db->fetch_row($sql);
          if ($row['id']) {
            $sql = "UPDATE wechat_pays_today SET trade_amount = trade_amount + $trade, service_fee = service_fee + $serviceFee, service_fee_wechat = service_fee_wechat + $serviceFeeWechat,  vipcard_revenue = vipcard_revenue + $trade, updated_at = '$now' WHERE id = $row[id]";
          } else {
            $sql = "INSERT INTO wechat_pays_today (mch_id, service_fee, service_fee_wechat, vipcard_revenue, date_at, updated_at) VALUES ($mchId, $serviceFee, $serviceFeeWechat, $trade, '$today', '$now')";
          }
          $db->query($sql);

          $sql = "UPDATE mch_vipcards SET sold = sold + 1, revenue = revenue + $trade WHERE mch_id = $mchId AND grade = $grade";
          $db->query($sql);
        }
      }
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

