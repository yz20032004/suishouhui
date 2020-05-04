<?php
  require_once 'common.php';

  $now    = date('Y-m-d H:i:s');

  $action = $_GET['action'];
  switch ($action) {
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

      $code = mt_rand(1000000000, 9999999999);
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
      $sql = "SELECT coupon_id, coupon_name, consume_limit, detail, date_start, date_end, code FROM member_coupons WHERE id = $id";
      $row = $db->fetch_row($sql);
      
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
