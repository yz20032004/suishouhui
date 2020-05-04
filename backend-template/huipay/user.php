<?php
  //小程序用户类操作
  require_once dirname(__FILE__).'/../common.php';

  $action  = $_GET['action'];
  $now = date('Y-m-d H:i:s');

  switch ($action) {
    case 'login':
      $appId = $_GET['appid'];
      $jsCode= $_GET['js_code'];
      $componentAccessToken = $redis->hget('keyou_suishouhui_open', 'component_access_token');
      $url = 'https://api.weixin.qq.com/sns/component/jscode2session?appid='.$appId.'&js_code='.$jsCode.'&grant_type=authorization_code&component_appid='.COMPONENT_APP_ID.'&component_access_token='.$componentAccessToken;
      $ret = httpPost($url);
      echo $ret;
      break;
    case 'get_detail':
      $mchId     = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];

      $sql  = "SELECT id, mch_id, openid, sub_openid, member_cardid, mobile, cardnum, grade, grade_title, name, nickname, point, recharge, coupons, amount_total, distribute_cash_out, expired_at FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId' ORDER BY last_consume_at DESC LIMIT 1";
      $row = $db->fetch_row($sql);
      if (!$row) {
        $row = array('grade'=>0, 'point'=>0, 'recharge'=>0, 'coupons'=>0, 'mch_id'=>$mchId, 'amount_total'=>'0.00', 'distribute_cash_out'=>'0.00');
      }
      echo json_encode($row);
      break;
    case 'get_shop_list':
      $subOpenId = $_GET['openid'];
      $sql = "SELECT mch_id, grade_title, headimgurl FROM members WHERE sub_openid = '$subOpenId'";
      $data = $db->fetch_array($sql);
      foreach($data as &$row) {
        $sql = "SELECT business_name, logo_url FROM shops WHERE mch_id = $row[mch_id]";
        $ret = $db->fetch_row($sql);
        $row['business_name'] = $ret['business_name'];
        $row['logo_url']      = $ret['logo_url'];
      }
      echo json_encode($data);
      break;
    case 'get_mch_detail':
      $mchId = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $sql  = "SELECT id, mch_id, sub_openid, grade, grade_title, name, nickname, mobile, point, recharge, coupons FROM members WHERE sub_openid = '$subOpenId' AND mch_id = $mchId LIMIT 1";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $row = array('point'=>0, 'recharge'=>0, 'coupons'=>0);
      } 
      echo json_encode($row);
      break;
    case 'get_trade_list':
      $mchId = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $sql = "SELECT COUNT(id) AS total FROM wechat_pays WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;

      $sql = "SELECT pay_from, out_trade_no, trade, ROUND(cash_fee / 100, 2) AS consume, use_coupon_name, use_coupon_amount, point_amount, use_recharge, use_reduce, use_discount, member_discount, coupon_fee, refund_fee, detail, get_point, created_at FROM wechat_pays WHERE mch_id = $mchId AND sub_openid = '$subOpenId' ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);

      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'get_groupon_detail':
      $mchId = $_GET['mch_id'];
      $outTradeNo= $_GET['out_trade_no'];
      $status = '';
      $sql  = "SELECT groupon_id, is_together, out_trade_no, together_no, together_status, is_head, coupon_id, coupon_total, buy_total, total_fee, refund_fee, created_at FROM wechat_groupon_pays WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);
      $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $row[coupon_id] AND is_icon = 1";
      $ret = $db->fetch_row($sql);
      if ($ret) {
        $row['image_url'] = $ret['image_url'];
      } else {
        $sql = "SELECT logo_url FROM user_mch_submit WHERE sub_mch_id = '$mchId'";
        $ret = $db->fetch_row($sql);
        $row['image_url'] = $ret['logo_url'];
      }
      if ($row['is_together']) {
        $sql = "SELECT title FROM mch_togethers WHERE id = $row[groupon_id]";
      } else {
        $sql = "SELECT title FROM mch_groupons WHERE id = $row[groupon_id]";
      }
      $ret = $db->fetch_row($sql);
      $row['title'] = $ret['title'];
      echo json_encode($row);
      break;
    case 'get_groupon_list':
      $mchId = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];

      $status = '';
      $sql  = "SELECT groupon_id, is_together, is_head, coupon_id, coupon_total, buy_total, total_fee, out_trade_no, refund_fee, created_at FROM wechat_groupon_pays WHERE mch_id = $mchId AND openid = '$subOpenId' ORDER BY id DESC";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        if (!isset($grouponData) || !in_array($row['groupon_id'], $grouponData)) {
          $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $row[coupon_id] AND is_icon = 1";
          $ret = $db->fetch_row($sql);
          if ($ret) {
            $row['image_url'] = $ret['image_url'];
          } else {
            $sql = "SELECT logo_url FROM user_mch_submit WHERE sub_mch_id = '$mchId'";
            $ret = $db->fetch_row($sql);
            $row['image_url'] = $ret['logo_url'];
          }
          if ($row['is_together']) {
            $sql = "SELECT title FROM mch_togethers WHERE id = $row[groupon_id]";
          } else {
            $sql = "SELECT title FROM mch_groupons WHERE id = $row[groupon_id]";
          }
          $ret = $db->fetch_row($sql);
          $row['title'] = $ret['title'];
        } else {
          $grouponData[] = $row['groupon_id'];
        }
      }
      echo json_encode($data);
      break;
    case 'get_point_history':
      $mchId = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $sql = "SELECT COUNT(id) AS total FROM member_point_history WHERE mch_id = $mchId AND openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT modify_point, detail, created_at FROM member_point_history WHERE mch_id = $mchId AND openid = '$subOpenId' ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);

      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'getphonenumber':
      require_once "../lib/wxBizDataCrypt.php";
      $appId = $_GET['appid'];
      $mchId = $_GET['mch_id'];
      $sessionKey    = $_GET['session_key'];
      $subOpenId     = $_GET['openid'];
      $encryptedData = $_GET['encryptedData'];
      $iv            = $_GET['iv'];

      $pc = new WXBizDataCrypt($appId, $sessionKey);
      $errCode = $pc->decryptData($encryptedData, $iv, $data);
      $ret = json_decode($data, true);
      $phoneNumber = $ret['phoneNumber'];

      /*include(dirname(__FILE__).'/../lib/phpqrcode/phpqrcode.php');
      // 二维码数据 
      $errorCorrectionLevel = 'L';
      // 点的大小：1到10 
      $matrixPointSize = 8;
      // 生成的文件名 
      $buffer = '/mnt/tmp/memberqrcode/'.$phoneNumber.'.png';
      QRcode::png($phoneNumber, $buffer, $errorCorrectionLevel, $matrixPointSize, 2);

      $filename = substr(md5('mobile_'.$mchId.$phoneNumber), 8, 16);
      $object = 'memberqrcode/'.$mchId.'/'.$filename.'.png';
      $memberCodeUrl = putOssObject($object, file_get_contents($buffer));
      unlink($buffer);*/

      $sql = "UPDATE members SET mobile = '$phoneNumber' WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $db->query($sql);
      echo $phoneNumber;
      break;
    case 'update_user_info':
      require_once "../lib/wxBizDataCrypt.php";
      $appId         = $_GET['appid'];
      $sessionKey    = $_GET['session_key'];
      $encryptedData = $_GET['encryptedData'];
      $iv            = $_GET['iv'];

      $mchId = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];

      $sql = "SELECT * FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo json_encode($row);
        return;
      }

      $pc = new WXBizDataCrypt($appId, $sessionKey);
      $errCode = $pc->decryptData($encryptedData, $iv, $data);
      if ('0' == $errCode) {
        $ret = json_decode($data, true);
        $avatarUrl = $ret['avatarUrl'];
        $city      = $ret['city'];
        $province  = $ret['province'];
        $nickName  = $ret['nickName'];
        $gender    = $ret['gender'];
        $unionId   = $ret['unionId'];

        $openId = '';
        $sql = "INSERT INTO members (mch_id, openid, sub_openid, unionid, grade, grade_title, nickname, headimgurl, province, city, gender, last_consume_at, created_at) VALUES ($mchId, '$openId', '$subOpenId', '$unionId', 0, '粉丝', '$nickName', '$avatarUrl', '$province', '$city', $gender, '$now', '$now')";
        $db->query($sql);
        
        $sql = "SELECT * FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
        $row = $db->fetch_row($sql);
        echo json_encode($row);
      } else {
        echo 'fail';
      }
      break;
    case 'bindphone':
      $mchId = $_GET['mch_id'];
      $openId = $_GET['openid'];
      $name = $_GET['name'];
      $mobile = $_GET['mobile'];
      $sql = "UPDATE members SET name = '$name', cardnum = '$mobile', mobile = '$mobile' WHERE mch_id = $mchId AND sub_openid = '$openId'";
      $db->query($sql);

      $today = date('Y-m-d');
      $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE wechat_pays_today SET members = members + 1 WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO wechat_pays_today (mch_id, members, date_at, updated_at) VALUES ($mchId, 1, '$today', '$now')";
      }
      $db->query($sql);
      echo 'success';
      break;
    case 'opencard':
      $mchId = $_GET['mch_id'];
      $openId = $_GET['openid'];
      $name = $_GET['name'];
      $mobile = $_GET['mobile'];
      $sql = "SELECT id FROM members WHERE mch_id = $mchId AND sub_openid = '$openId' AND cardnum != ''";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'success';
        break;
      }
      $sql = "UPDATE members SET name = '$name', cardnum = '$mobile', mobile = '$mobile' WHERE mch_id = $mchId AND sub_openid = '$openId'";
      $db->query($sql);

      $today = date('Y-m-d');
      $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE wechat_pays_today SET members = members + 1 WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO wechat_pays_today (mch_id, members, date_at, updated_at) VALUES ($mchId, 1, '$today', '$now')";
      }
      $db->query($sql);
      $data = array('job'=>'update_member_opengift', 'access_token'=>'', 'mch_id'=>$mchId, 'code'=>$mobile, 'point'=>0);
      $redis->rpush('member_job_list', serialize($data));

      echo 'success';
      break;
    case 'get_coupons':
      $mchId  = $_GET['mch_id'];
      $openId = $_GET['openid'];
      $timingData = array();
      $today = date('Y-m-d');
      $couponData = array('enable'=>array(), 'unenable'=>array(), 'expire'=>array(), 'used'=>array(), 'timing'=>array());
      $sql = "SELECT coupon_id, id, coupon_name, coupon_type, amount, discount, status, date_start, date_end, in_wechat, updated_at, created_at FROM member_coupons WHERE mch_id = '$mchId' AND openid = '$openId' AND coupon_type != 'wechat_cash' ORDER BY created_at DESC";
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
      echo json_encode($couponData);
      break;
    case 'get_revenue_today':
      $mchId = $_GET['mch_id'];
      $openId= $_GET['openid'];
      $sql = "SELECT SUM(id) AS total, SUM(bonus) AS bonus FROM member_distribute_bonus_list WHERE mch_id = $mchId AND openid = '$openId'";
      $row = $db->fetch_row($sql);
      if (!$row['total']) {
        echo json_encode(array('total'=>0, 'bonus'=>0.00));
      } else {
        echo json_encode($row);
      }
      break;
    case 'get_distribute_history':
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];
      $mchId = $_GET['mch_id'];
      $openId= $_GET['openid'];

      $sql = "SELECT COUNT(id) AS total FROM member_distribute_bonus_list WHERE mch_id = $mchId AND openid = '$openId'";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT bonus, groupon_title, nickname, headimgurl, created_at FROM member_distribute_bonus_list WHERE mch_id = $mchId AND openid = '$openId' ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);

      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'get_waimai_address':
      $mchId = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $sql = "SELECT * FROM member_address WHERE mch_id = $mchId AND openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'update_waimai_contact':
      $mchId = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $name      = addslashes(trim($_GET['name']));
      $mobile    = addslashes(trim($_GET['mobile']));
      $sql = "SELECT id FROM member_address WHERE mch_id = $mchId AND openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE member_address SET name = '$name', mobile = '$mobile' WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO member_address (mch_id, openid, name, mobile, created_at) VALUES ($mchId, '$subOpenId', '$name', '$mobile', '$now')";
      }
      $db->query($sql);
      echo 'success';
      break;

    case 'update_waimai_address':
      $mchId = $_GET['mch_id'];
      $subOpenId = $_GET['openid'];
      $name      = addslashes(trim($_GET['name']));
      $mobile    = addslashes(trim($_GET['mobile']));
      $address   = addslashes(trim($_GET['address']));
      $addressNo = addslashes(trim($_GET['address_no']));
      $latitude  = $_GET['latitude'];
      $longitude  = $_GET['longitude'];
      $sql = "SELECT id FROM member_address WHERE mch_id = $mchId AND openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE member_address SET name = '$name', mobile = '$mobile', address = '$address', address_no = '$addressNo', latitude = $latitude, longitude = $longitude WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO member_address (mch_id, openid, name, mobile, address, address_no, latitude, longitude, created_at) VALUES ($mchId, '$subOpenId', '$name', '$mobile', '$address', '$addressNo', $latitude, $longitude, '$now')";
      }
      $db->query($sql);
      echo 'success';
      break;
    case 'get_ordering_detail':
      //获取在线点单详情
      $mchId      = $_GET['mch_id'];
      $outTradeNo = $_GET['out_trade_no'];
      $sql = "SELECT pay_from, trade, ROUND(cash_fee / 100, 2) AS consume, use_coupon_name, use_coupon_amount, point_amount, use_recharge, use_reduce, use_discount, member_discount, coupon_fee, refund_fee, detail, get_point, created_at FROM wechat_pays WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $payData = $db->fetch_row($sql);

      $sql = "SELECT * FROM member_ordering_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);

      $data = array('pay'=>$payData, 'order'=>$row, 'dishes'=>unserialize($row['detail']));
      echo json_encode($data);
      break;
    case 'get_mall_detail':
      $mchId      = $_GET['mch_id'];
      $outTradeNo = $_GET['out_trade_no'];
      $sql = "SELECT pay_from, trade, ROUND(cash_fee / 100, 2) AS consume, use_coupon_name, use_coupon_amount, point_amount, use_recharge, use_reduce, use_discount, member_discount, coupon_fee, refund_fee, detail, get_point, created_at FROM wechat_pays WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $payData = $db->fetch_row($sql);

      $sql = "SELECT * FROM member_mall_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);

      $data = array('pay'=>$payData, 'order'=>$row, 'products'=>unserialize($row['detail']));
      echo json_encode($data);
      break;
    case 'get_order_detail':
      $mchId      = $_GET['mch_id'];
      $outTradeNo = $_GET['out_trade_no'];
      $sql = "SELECT pay_from, trade, ROUND(cash_fee / 100, 2) AS consume, use_coupon_name, use_coupon_amount, point_amount, use_recharge, use_reduce, use_discount, member_discount, coupon_fee, refund_fee, detail, get_point, created_at FROM wechat_pays WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $payData = $db->fetch_row($sql);

      $sql = "SELECT * FROM member_waimai_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);

      $data = array('pay'=>$payData, 'order'=>$row, 'dishes'=>unserialize($row['detail']));
      echo json_encode($data);
      break;
    default:
      break;
  }
