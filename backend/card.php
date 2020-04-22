<?php
  require_once 'common.php';

  $now    = date('Y-m-d H:i:s');

  $action = $_GET['action'];
  switch ($action) {
    case 'get_coupon_ext':
      $couponId = $_GET['coupon_id'];
      $total  = $_GET['total'];

      //有可能要领取多张不同的优惠券
      $couponIdData = explode('#', $couponId);
      $totalData  = explode('#', $total);

      $ticket = getWxCardTicket();  
      $timeStamp = time();

      $data = array();
      for($i=0;$i<count($totalData);$i++) {
        $couponId = $couponIdData[$i];
        if (!$couponId) {
          continue;
        }
        //$cardId = $redis->hget('keyou_coupon_cardids', $couponId);
        $sql = "SELECT wechat_cardid FROM coupons WHERE id = '$couponId'";
        $row = $db->fetch_row($sql);
        $cardId = $row['wechat_cardid'];

        for($j=0;$j<$totalData[$i];$j++) {
          $nonce = getNonceStr();
          // 这里参数的顺序要按照 key 值 ASCII 码升序排序
          $array = array($ticket, $timeStamp, $nonce, $cardId);
          sort($array, SORT_STRING);
          $signature = sha1(implode('', $array));
          $ext = array('timestamp'=>(string)$timeStamp, 'nonce_str'=>$nonce, 'signature'=>$signature);

          $data[] = array('cardId'=>$cardId, 'cardExt'=>json_encode($ext));
        }
      }
      echo json_encode($data);
      break;
    case 'get_membercard_extradata':
      $mchId = $_GET['mch_id'];
      $cardId = $redis->hget('keyou_mch_card', $mchId);

      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $url = 'https://api.weixin.qq.com/card/membercard/activate/geturl?access_token='.$accessToken;
      $ret  = httpPost($url, json_encode(array('card_id'=>$cardId)));
      $data = json_decode($ret, true);
      
      $url  = $data['url'];
      $data = explode('&', $url);
      foreach ($data as $v) {
        if (strstr($v, 'encrypt_card_id')) {
          $encrypt_card_id = substr($v, strpos($v, '=')+1);
        } else if (strstr($v, 'biz')) {
          $biz = substr($v, strpos($v, '=')+1, strpos($v, '#')-4);
        }
      }
      $data = array('encrypt_card_id'=>$encrypt_card_id, 'biz'=>$biz);
      echo json_encode($data);
      break;
    case 'get_memberinfo':
      $mchId     = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $sql = "SELECT member_cardid, cardnum FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);

      $cardId = $row['member_cardid'];
      $cardNum= $row['cardnum'];

      $ticket = getWxCardTicket();  
      $timeStamp = time();
      $nonce = getNonceStr();

      // 这里参数的顺序要按照 key 值 ASCII 码升序排序
      $array = array($ticket, $timeStamp, $nonce, $cardId);
      sort($array, SORT_STRING);
      $signature = sha1(implode('', $array));

      $ext  = array('timestamp'=>(string)$timeStamp, 'nonce_str'=>$nonce, 'signature'=>$signature);
      $data[] = array('cardId'=>$cardId, 'code'=>$cardNum);
      echo json_encode($data);
      break;
    case 'add_coupon':
      $mchId    = $_GET['mch_id'];
      $openId   = $_GET['openid'];
      $couponId = $_GET['coupon_id'];

      $sql = "SELECT id FROM member_coupons WHERE mch_id = $mchId AND openid = '$openId' AND get_type = 'get'";
      $row = $db->fetch_row($sql);
      //每个用户只限领一次
      if ($row['id']) {
        $return = array('result'=>'fail', 'msg'=>'该优惠券限领取一次，您已经领过了');
        echo json_encode($return);
        exit();
      }
      $sql = "SELECT * FROM coupons WHERE id = $couponId AND balance > 0";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $return = array('result'=>'fail', 'msg'=>'该券已领完');
        echo json_encode($return);
        exit();
      }
      $couponName = $row['name'];
      $amount     = $row['amount'];
      $discount   = $row['discount'];
      $detail     = $row['description'];
      $consumeLimit = $row['consume_limit'];
      $couponType   = $row['coupon_type'];

      if ('relative' == $row['validity_type']) {
        $dateStart = $row['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
        $totalDays = $row['total_days'] - 1;
        $dateEnd   = date('Y-m-d', strtotime('+'.$totalDays.' days'));
      } else {
        $dateStart = $row['date_start'];
        $dateEnd   = $row['date_end'];
      }

      $code = mt_rand(10000000, 99999999);
      $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code, detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$openId', '$couponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'get', '$now')";
      $db->query($sql);
      $insertId = $db->get_insert_id();

      $sql = "UPDATE members SET coupons = coupons + 1 WHERE mch_id = $mchId AND sub_openid = '$openId'";
      $db->query($sql);

      $sql = "UPDATE coupons SET balance = balance - 1 WHERE id = $couponId";
      $db->query($sql);

      $return = array('result'=>'success', 'id'=>$insertId);
      echo json_encode($return);
      break;
    case 'open':
      $subOpenId  = $_GET['openid'];

      $sql = "SELECT openid FROM members where sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      $openId = $row['openid'];
      $sql = "SELECT card_id AS cardId, code FROM member_coupons WHERE openid = '$openId' AND status = 1 AND date_end > '$now'";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_list':
      $mchId     = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $sql = "SELECT id, in_wechat, coupon_id, coupon_type, amount, discount, coupon_name, consume_limit, code, date_start, date_end, detail FROM member_coupons WHERE mch_id = $mchId AND status = 1 AND openid = '$subOpenId' ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT coupon_id, coupon_name, consume_limit, detail, date_start, date_end, code, in_wechat FROM member_coupons WHERE id = $id";
      $row = $db->fetch_row($sql);

      $sql = "SELECT wechat_cardid FROM coupons WHERE id = $row[coupon_id]";
      $ret = $db->fetch_row($sql);
      $row['card_id'] = $ret['wechat_cardid'];
 
      echo json_encode($row);
      break;
    case 'get_coupon_detail':
      $id  = $_GET['id'];
      $sql = "SELECT id, mch_id, name,description, consume_limit, validity_type,date_start,date_end,total_days FROM coupons WHERE id = '$id'";
      $row = $db->fetch_row($sql);

      $sql = "SELECT business_name, logo_url FROM shops WHERE mch_id = $row[mch_id]";
      $ret = $db->fetch_row($sql);
      $row['business_name'] = $ret['business_name'];
      $row['logo_url'] = $ret['logo_url'];
      echo json_encode($row);
      break;
    default:
      break;
  }

  function getWxCardTicket()
  {
    global $redis;
    $ticketExpire = $redis->hget('keyouxinxi', 'wxcard_jsapiticket_expire');
    if (time() > $ticketExpire) {
      $wxAccessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$wxAccessToken.'&type=wx_card';
      $ret = httpPost($url);
      $data = json_decode($ret, true);
      
      $ticket = $data['ticket'];
      $redis->hset('keyouxinxi', 'wxcard_jsapiticket', $ticket);
      $redis->hset('keyouxinxi', 'wxcard_jsapiticket_expire', time()+$data['expires_in']);
    } else {
      $ticket = $redis->hget('keyouxinxi', 'wxcard_jsapiticket');
    }
    return $ticket;
  }
