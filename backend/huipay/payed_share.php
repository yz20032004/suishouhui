<?php
  /*
   * 支付后分享券
   */
  require_once dirname(__FILE__).'/../common.php';
  require_once dirname(__FILE__).'/../lib/WxPay.Config.php';
  require_once dirname(__FILE__).'/../lib/WxPay.Exception.php';
  
  $action = $_GET['action'];
  switch ($action) {
    case 'create':
      $key = $_GET['key'];
      $couponTotal = $_GET['coupon_total'];
      $openId = $_GET['openid'];
      $mchId  = $_GET['mch_id'];
      $date   = date('Ymd');
      $data   = unserialize($redis->hget('keyou_trade_history_'.$date, $key));
      $outTradeNo = $data['out_trade_no'];

      $now = date('Y-m-d H:i:s');
      $sql = "INSERT INTO wechat_payed_share_list (mch_id, share_key, openid, out_trade_no, coupon_total, get, created_at) VALUES ($mchId, '$key', '$openId', '$outTradeNo', $couponTotal, 0, '$now')";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_list':
      $key = $_GET['key'];
      $sql = "SELECT mch_id FROM wechat_payed_share_list WHERE share_key = '$key'";
      $row = $db->fetch_row($sql);
      $mchId = $row['mch_id'];

      $sql = "SELECT mch_id, business_name, logo_url FROM shops WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);

      $sql = "SELECT * FROM wechat_payed_share_gets WHERE mch_id = $mchId AND share_key = '$key' ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      $row['list'] = $data;
      echo json_encode($row);
      break;
    case 'grab_wechatcard':
      //领取微信卡券
      $key = $_GET['key'];
      $openId = $_GET['openid'];
      $mchId  = $_GET['mch_id'];

      $sql = "SELECT id FROM wechat_payed_share_gets WHERE share_key = '$key' AND openid = '$openId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $data = array('result'=>'fail', 'message'=>'您已经领取过了');
        echo json_encode($data);
        exit();
      }

      if ('user' == substr($key, 0, 4)) {
        //管理员或服务员好友赠券规则
        $sql = "SELECT * FROM user_sharecoupon_rules WHERE share_key = '$key'";       
      } else {
        //支付后裂变券规则
        $sql = "SELECT * FROM app_payed_shares WHERE mch_id = $mchId";
      }
      $data = $db->fetch_array($sql);
      foreach ($data as $k=>$v) { 
          $arr[$k] = $v['percent']; 
      } 
      $rid = get_rand($arr); //根据概率获取奖项id 
      $couponId      = $data[$rid]['coupon_id'];
      $couponName    = $data[$rid]['coupon_name'];

      $sql = "SELECT cardnum, member_cardid, coupons, nickname, headimgurl FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
      $row = $db->fetch_row($sql);
      $cardNum = $row['cardnum'];
      $memberCardId = $row['member_cardid'];
      $coupons = $row['coupons'];
      $nickname = $row['nickname'];
      $headImageUrl = $row['headimgurl'];

      $now = date('Y-m-d H:i:s');
      $sql = "INSERT INTO wechat_payed_share_gets (mch_id, share_key, openid, nickname, headimgurl, coupon_stock_id, coupon_id, coupon_name, created_at) VALUES ($mchId, '$key', '$openId', '$nickname', '$headImageUrl', 0, $couponId, '$couponName', '$now')";
      $db->query($sql);

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
      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');     
      $data = array(
                    'job'          => 'send_member_coupon',
                    'wx_access_token' => $accessToken,
                    'mch_id'       => $mchId,
                    'sub_openid'   => $openId,
                    'coupon_id'    => $couponId,
                    'coupon_total' => 1,
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

      $sql = "UPDATE wechat_payed_share_list SET get = get + 1 WHERE share_key = '$key'";
      $db->query($sql);

      $data = array(
                    'result'   => 'success',
                    'coupon_id'  => $couponId,
                    );
      echo json_encode($data);
      break;
    case 'grab_coupon':
      $key = $_GET['key'];
      $openId = $_GET['openid'];

      $sql = "SELECT id FROM wechat_payed_share_gets WHERE share_key = '$key' AND openid = '$openId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $data = array('result'=>'fail', 'message'=>'您已经领取过了');
        echo json_encode($data);
        exit();
      }
      $sql = "SELECT mch_id FROM wechat_payed_share_list WHERE share_key = '$key'";
      $row = $db->fetch_row($sql);
      $mchId = $row['mch_id'];

      $sql = "SELECT * FROM app_payed_shares WHERE mch_id = $mchId";
      $data = $db->fetch_array($sql);

      foreach ($data as $k=>$v) { 
          $arr[$k] = $v['percent']; 
      } 
      $rid = get_rand($arr); //根据概率获取奖项id 
      $couponStockId = $data[$rid]['coupon_stock_id'];
      $couponId      = $data[$rid]['coupon_id'];
      $couponName    = $data[$rid]['coupon_name'];

      $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/send_coupon';
      $data = array(
                'coupon_stock_id' => $couponStockId,
                'openid_count'    => 1,
                'partner_trade_no'=> MCHID.getOutTradeNo(),
                'openid'          => $openId,
                'appid'           => SUISHOUHUI_APP_ID,
                'mch_id'          => MCHID,
                'nonce_str'        => getNonceStr()
                );
      $sign = MakeSign($data);
      $data['sign'] = $sign;
      $xml = ToXml($data);

      $response = postXmlCurl($xml, $url, true, 6);
      $responseData = FromXml($response);
      if ($responseData && $responseData['success_count'] > 0) {
        $wechatCashCouponId = $responseData['coupon_id'];

        $sql = "SELECT nickname, headimgurl FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
        $row = $db->fetch_row($sql);
        $nickname = $row['nickname'];
        $headImageUrl = $row['headimgurl'];

        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO wechat_payed_share_gets (mch_id, share_key, openid, nickname, headimgurl, coupon_stock_id, coupon_id, coupon_name, created_at) VALUES ($mchId, '$key', '$openId', '$nickname', '$headImageUrl', $couponStockId, $couponId, '$couponName', '$now')";
        $db->query($sql);

        $sql = "SELECT * FROM coupons WHERE id = $couponId";
        $row = $db->fetch_row($sql);

        $couponName = $row['name'];
        $amount = $row['amount'];
        $detail = $row['description'];
        $consumeLimit = $row['consume_limit'];
        if ('relative' == $row['validity_type']) {
          $dateStart = $row['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
          $totalDays = $row['total_days'] - 1;
          $dateEnd   = date('Y-m-d', strtotime('+'.$totalDays.' days'));
        } else {
          $dateStart = $row['date_start'];
          $dateEnd   = $row['date_end'];
        }

        $code = $wechatCashCouponId;
        $couponType = 'wechat_cash';
        $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, consume_limit, code, detail, date_start, date_end, get_type, in_wechat, created_at) VALUES ('$mchId', '$openId', '$couponId', '$couponType', '$couponName', '$amount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'share', 1, '$now')";
        $db->query($sql);

        $sql = "UPDATE members SET coupons = coupons + 1 WHERE mch_id = $mchId AND sub_openid = '$openId'";
        $db->query($sql);

        $sql = "UPDATE coupons SET balance = balance - 1 WHERE id = $couponId";
        $db->query($sql);

        $sql = "UPDATE wechat_payed_share_list SET get = get + 1 WHERE share_key = '$key'";
        $db->query($sql);

        $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
        $postData = array(
                  'job'    => 'update_member_card',
                  'mch_id' => $mchId,
                  'openid' => $openId,
                  'access_token' => $accessToken,
                  'point'  => 0,
                  'detail' => '',
                );
        $redis->rpush('member_job_list', serialize($postData));

        $data = array(
                      'result'   => 'success',
                      'nickname' => $nickname,
                      'headimgurl' => $headImageUrl,
                      'coupon_name'=> $couponName,
                      );
        echo json_encode($data);
      } else {
        $data = array('result'=>'fail', 'message'=>$responseData['ret_msg']);
        echo json_encode($data);
      }
      break;
    default:
      break;
  }

  function get_rand($proArr) { 
      $result = ''; 
      //概率数组的总概率精度 
      $proSum = array_sum($proArr); 
      //概率数组循环 
      foreach ($proArr as $key => $proCur) { 
          $randNum = mt_rand(1, $proSum); 
          if ($randNum <= $proCur) { 
              $result = $key; 
              break; 
          } else { 
              $proSum -= $proCur; 
          } 
      } 
      unset ($proArr); 
      return $result; 
  }
