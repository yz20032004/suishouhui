<?php
  require_once dirname(__FILE__).'/../common.php';

  $action = $_GET['action'];
  switch ($action) {
    case 'list':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, point, coupon_id, coupon_name FROM app_point_exchange_rules WHERE mch_id = $mchId ORDER BY point";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $couponId = $row['coupon_id'];
        $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $couponId AND is_icon = 1";
        $v   = $db->fetch_row($sql);
        if (!$v) {
          $sql = "SELECT logo_url FROM shops WHERE mch_id = $mchId ORDER BY id LIMIT 1";
          $v   = $db->fetch_row($sql);
          $row['image_url'] = $v['logo_url'];
        } else {
          $row['image_url'] = $v['image_url'];
        }
      }
      echo json_encode($data);
      break;
    case 'get_hots':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT * FROM app_point_exchange_rules WHERE mch_id = $mchId AND (is_limit - exchange_limit != 1) ORDER BY exchanged DESC LIMIT 2";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM app_point_exchange_rules WHERE id = $id";
      $row = $db->fetch_row($sql);

      $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $row[coupon_id] AND is_icon = 1";
      $v   = $db->fetch_row($sql);
      if (!$v) {
        $sql = "SELECT logo_url FROM shops WHERE mch_id = $row[mch_id] ORDER BY id LIMIT 1";
        $v   = $db->fetch_row($sql);
        $row['image_url'] = $v['logo_url'];
      } else {
        $row['image_url'] = $v['image_url'];
      }

      $sql = "SELECT amount, is_usefully_sendday, total_days, date_start, date_end, validity_type, description FROM coupons WHERE id = $row[coupon_id]";
      $ret = $db->fetch_row($sql);
      if ('relative' == $ret['validity_type']) {
        $validityDays = $ret['total_days'].'天';
        if (!$ret['is_usefully_sendday']) {
          $validityDays .= '，第二天生效';
        }
      } else {
        $validityDays = $ret['date_start'].'到'.$ret['date_end'];
      }
      $description = $ret['description'];
      $row['validity_days'] = $validityDays;
      $row['description']   = $description;
      $row['coupon_amount'] = $ret['amount'];
      echo json_encode($row);
      break;
    case 'exchange':
      $id = $_GET['id'];
      $openId = $_GET['openid'];
      $couponId = $_GET['coupon_id'];
      $point    = $_GET['point'];
      $singleLimit = $_GET['single_limit'];
      $mchId       = $_GET['mch_id'];
      $formId      = $_GET['formId'];

      $sql = "SELECT * FROM app_point_exchange_rules WHERE id = $id";
      $row = $db->fetch_row($sql);
      if ($row['exchange_limit'] <= 0) {
        $return = '该礼品已兑完';
        echo $return;
        exit();
      }
      $ruleId = $row['id'];

      if ($row['is_limit']) {
        $singleLimit = $row['single_limit'];
        $sql = "SELECT COUNT(id) AS total FROM member_point_exchanges WHERE mch_id = $mchId AND openid = '$openId' AND coupon_id = $couponId AND rule_id = $ruleId";
        $ret = $db->fetch_row($sql);
        if ('0' != $singleLimit && $ret['total'] >= $singleLimit)  {
          $return = '该礼品限制每会员仅兑换'.$singleLimit.'次';
          echo $return;
          exit();
        }
      }
      $sql = "SELECT * FROM coupons WHERE id = $couponId";
      $row = $db->fetch_row($sql);
      if ($row['balance'] <= 0) {
        $return = '该礼品已兑完';
        echo $return;
        exit();
      }

      $now = date('Y-m-d H:i:s');
      $sql = "UPDATE app_point_exchange_rules SET exchanged = exchanged +1, exchange_limit = exchange_limit - 1 WHERE id = $id";
      $db->query($sql);

      $sql = "UPDATE members SET point = point - $point, coupons = coupons + 1 WHERE mch_id = $mchId AND sub_openid = '$openId'";
      $db->query($sql);

      $couponName = $row['name'];
      $amount = $row['amount'];
      $discount = $row['discount'];
      $detail = $row['description'];
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
      $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code, detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$openId', '$couponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', '$detail', '$dateStart', '$dateEnd', 'exchange', '$now')";
      $db->query($sql);

      $sql = "UPDATE coupons SET balance = balance - 1 WHERE id = $couponId";
      $db->query($sql);

      $sql = "INSERT INTO member_point_exchanges (mch_id, rule_id, openid, form_id, point, coupon_id, created_at) VALUES ($mchId, $ruleId, '$openId', '$formId', $point, $couponId, '$now')";
      $db->query($sql);

      $detail = '积分兑换'; 
      $sql = "INSERT INTO member_point_history (mch_id, openid, modify_point, detail, created_at) VALUES ($mchId, '$openId', -$point, '$detail', '$now')";
      $db->query($sql);
    
      //更新日报表
      $dateAt = date('Y-m-d');
      $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$dateAt'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO wechat_pays_today (mch_id, use_point, date_at, updated_at) VALUES ($mchId, $point, '$dateAt', '$now')";
      } else {
        $sql = "UPDATE wechat_pays_today SET use_point = use_point + $point WHERE mch_id = $mchId AND date_at = '$dateAt'";
      }
      $db->query($sql);

      $miniAccessToken = $redis->hget('keyou_mini', 'access_token');
      $data = array('job'=>'send_point_exchange_notice', 'mini_access_token'=>$miniAccessToken, 'point'=>$point, 'mch_id'=>$mchId, 'code'=>$code, 'form_id'=>$formId);
      $redis->rpush('member_job_list', serialize($data));

      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $data = array('job'=>'update_member_card', 'access_token'=>$accessToken, 'point'=>"-".$point, 'detail'=>'积分兑换', 'mch_id'=>$mchId, 'openid'=>$openId);
      $redis->rpush('member_job_list', serialize($data));

      $return = 'success';
      echo $return;
      break;
    default:
      break;
  }
