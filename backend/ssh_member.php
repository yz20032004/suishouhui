<?php
  //会员操作
  require_once 'common.php';

  $mchId = $_GET['mch_id'];
  $action  = $_GET['action'];
  $now = date('Y-m-d H:i:s');

  switch ($action) {
    case 'send_coupon':
      $mchId  = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $couponId = $_GET['coupon_id'];
      $couponTotal  = $_GET['count'];

      $sql = "SELECT openid, cardnum, member_cardid, coupons FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      $openId = $row['openid'];
      $cardNum = $row['cardnum'];
      $memberCardId = $row['member_cardid'];
      $coupons = $row['coupons'];

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
      break;
    case 'set_point':
      $mchId  = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $operate = $_GET['operate'];
      $amount  = $_GET['amount'];
      $username = $_GET['username'];
      
      $modifyPoint = $amount;
      if ($operate == '1') {
        $sql = "SELECT point FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
        $row = $db->fetch_row($sql);
        if ($amount > $row['point']) {
          echo 'fail';
          exit();
        }
        $modifyPoint = -$amount;
      }

      $sql = "UPDATE members SET point = point + $modifyPoint WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $db->query($sql);

      $sql = "INSERT INTO member_point_history (mch_id, openid, modify_point, detail, created_at) VALUES ($mchId, '$subOpenId', $modifyPoint, '门店手工调整积分', '$now')";
      $db->query($sql);

      $message = $username.'手工调整会员'.$modifyPoint.'积分';
      $sql = "INSERT INTO logs (mch_id, openid, message, created_at) VALUES ($mchId, '$subOpenId', '$message', '$now')";
      $db->query($sql);
      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');     
      $data = array(
                    'job'          => 'update_member_point',
                    'wx_access_token' => $accessToken,
                    'mch_id'       => $mchId,
                    'sub_openid'   => $subOpenId,
                    'point'        => $modifyPoint,
                    );
      $redis->rpush('member_job_list', serialize($data));
      echo 'success';
      break;
    case 'update_grade':
      $subOpenId = $_GET['openid'];
      $grade     = $_GET['grade'];
      $sql = "SELECT name FROM app_grades WHERE mch_id = $mchId AND grade = $grade";
      $row = $db->fetch_row($sql);
      $gradeTitle = $row['name'];

      $sql = "SELECT id, member_cardid, cardnum, consumes, amount_total, recharge_total, grade FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      $memberId = $row['id'];
      $cardNum  = $row['cardnum'];
      $cardId   = $row['member_cardid'];
      $sql = "UPDATE members SET grade = $grade, grade_title = '$gradeTitle', upgrade_at = '$now' WHERE id = $memberId";
      $db->query($sql);

      if ($cardNum && $cardId) {
        $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');     
        $postData = array(
                  'code' => $cardNum,
                  'card_id' => $cardId,
                  'custom_field_value2' => urlencode($gradeTitle),
                  'notify_optional' => array('is_notify_custom_field2'=>true)
        );
        $url = 'https://api.weixin.qq.com/card/membercard/updateuser?access_token='.$accessToken;
        $a = httpPost($url, urldecode(json_encode($postData)));
      }
      break;
    case 'get_info':
      $openId = isset($_GET['openid']) ? $_GET['openid'] : '';
      $mobile = isset($_GET['mobile']) ? $_GET['mobile'] : '';
      $sql  = "SELECT * FROM members WHERE mch_id = '$mchId'";
      if ($openId) {
        $sql .= " AND sub_openid = '$openId'";
      } else {
        $sql .= " AND mobile = '$mobile'";
      }
      $row  = $db->fetch_row($sql);
      if (!$row['id']) {
        echo 'fail';
      } else {
        $sql = "SELECT discount FROM app_grades WHERE mch_id = '$mchId' AND grade = '$row[grade]'";
        $ret = $db->fetch_row($sql);
        $row['discount'] = $ret['discount'];
        echo json_encode($row);
      }
      break;     
    case 'trade_list':
      $openId = $_GET['openid'];
      $sql = "SELECT id, out_trade_no, trade, date_format(created_at, '%y-%m-%e %H:%i') AS create_time FROM wechat_pays WHERE mch_id = '$mchId' AND sub_openid = '$openId' ORDER BY id DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'recharge_list':
      $openId = $_GET['openid'];
      $sql = "SELECT id, mobile, detail, date_format(created_at, '%y-%m-%e %H:%i') AS created_at, recharge_amount FROM member_recharges WHERE mch_id = '$mchId' AND openid = '$openId' ORDER BY id DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_coupons':
      $openId = $_GET['openid'];
      $today = date('Y-m-d');
      $timingData = array();
      $couponData = array('enable'=>array(), 'unenable'=>array(), 'expire'=>array(), 'used'=>array(), 'timing'=>array());
      $sql = "SELECT coupon_id, coupon_id AS id, coupon_name, coupon_type, code, amount, discount, status, date_start, date_end, updated_at, created_at FROM member_coupons WHERE mch_id = '$mchId' AND openid = '$openId' AND coupon_type != 'wechat_cash' ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      foreach ($data as $key=>&$row) {
        if ('0' == $row['status'] && $row['updated_at'] < date('Y-m-d 23:59:59',strtotime($row['date_end']))) {
          $couponData['used'][] = $row;
          continue;
        }
        if ($today < $row['date_start']) {
          $couponData['unenable'][] = $row;
        } else if ($today > $row['date_end']) {
          $couponData['expire'][] = $row;
        } else {
          $couponData['enable'][] = $row;
        }
      }
      foreach ($couponData['enable'] as $key=>$row) {
        if ('timing' == $row['coupon_type']) {
          $couponId = $row['coupon_id'];
          unset($couponData['enable'][$key]);
          if (array_key_exists($couponId, $timingData)) {
            $timingData[$couponId]['times'] = $timingData[$couponId]['times'] + 1;
          } else {
            $row['times'] = 1;
            $timingData[$couponId] = $row;
          }
        }
      }
      $couponData['timing'] = array_values($timingData);
      $couponData['enable'] = array_values($couponData['enable']);
      echo json_encode($couponData);
      break;
    default:
      break;
  }
