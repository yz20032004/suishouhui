<?php
  //营销设置操作
  require_once 'common.php';
  $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');

  //防止MYSQL注入
  foreach ($_GET AS $key=>&$v) {
    $v = str_replace("'", '', $v);
    $v = str_replace('"', '', $v);
  }
  foreach ($_POST AS $key=>&$v) {
    $v = str_replace("'", '', $v);
    $v = str_replace('"', '', $v);
  }

  $now = date('Y-m-d H:i:s');
  $mchId = $_GET['mch_id'];
  $action  = $_GET['action'];

  switch ($action) {
    case 'delete_recharge':
      $id = $_GET['id'];
      $sql = "DELETE FROM app_recharge_rules WHERE id = $id";
      $db->query($sql);

      $sql = "DELETE FROM app_recharge_coupon_rules WHERE recharge_id = $id";
      $db->query($sql);
      load_recharge_rules_toredis($mchId);
      echo 'success';
      break;
    case 'delete_recharge_coupon':
      $id = $_GET['id'];
      $sql = "DELETE FROM app_recharge_coupon_rules WHERE id = $id";
      $db->query($sql);
      load_recharge_rules_toredis($mchId);
      echo 'success';
      break;
    case 'get_recharges':
      $sql = "SELECT * FROM app_recharge_rules WHERE mch_id = '$mchId' ORDER BY touch";
      $data = $db->fetch_array($sql);
      foreach ($data as $k=>$row) {
        if ('coupon' == $row['award_type']) {
          $sql = "SELECT coupon_name, total FROM app_recharge_coupon_rules WHERE recharge_id = $row[id]";
          $ret = $db->fetch_array($sql);
          $data[$k]['coupons'] = $ret;
        }
      }
      echo json_encode($data);
      break;
    case 'get_recharge':
      $id = $_GET['id'];
      $sql = "SELECT * FROM app_recharge_rules WHERE id = $id";
      $row = $db->fetch_row($sql);
      if ('coupon' == $row['award_type']) {
        $sql = "SELECT id, coupon_name, total FROM app_recharge_coupon_rules WHERE recharge_id = $row[id]";
        $ret = $db->fetch_array($sql);
        $row['coupons'] = $ret;
      }
      echo json_encode($row);
      break;
    case 'add_recharge':
      $touch = $_GET['touch'];
      $awardType = $_GET['award_type'];
      $amount    = $_GET['amount'];
      $percent   = $_GET['percent'];
      $couponId  = $_GET['coupon_id'];
      $couponName= $_GET['coupon_name'];
      $count     = $_GET['count'];
      $remark    = $_GET['remark'];
      $sql = "SELECT id FROM app_recharge_rules WHERE touch = '$touch' AND mch_id = '$mchId' LIMIT 1";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO app_recharge_rules (mch_id, touch, award_type, amount, percent, remark, created_at) VALUES ('$mchId', '$touch', '$awardType', '$amount', '$percent', '$remark', '$now')";
        $db->query($sql);
  
        if ('coupon' == $awardType) {
          $rechargeId = $db->get_insert_id();
          $sql = "INSERT INTO app_recharge_coupon_rules (mch_id, recharge_id, touch, coupon_id, coupon_name, total, created_at) VALUES ($mchId, $rechargeId, $touch, $couponId, '$couponName', $count, '$now')";
          $db->query($sql);
        }
        echo 'success';
      } else {
        echo 'fail';
      }
      load_recharge_rules_toredis($mchId);
      break;
    case 'add_recharge_coupon':
      $rechargeId = $_GET['id'];
      $mchId      = $_GET['mch_id'];
      $touch      = $_GET['touch'];
      $couponId  = $_GET['coupon_id'];
      $couponName= $_GET['coupon_name'];
      $count     = $_GET['count'];
      
      $sql = "SELECT id FROM app_recharge_coupon_rules WHERE recharge_id = $rechargeId AND coupon_id = $couponId";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO app_recharge_coupon_rules (mch_id, recharge_id, touch, coupon_id, coupon_name, total, created_at) VALUES ($mchId, $rechargeId, $touch, $couponId, '$couponName', $count, '$now')";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      load_recharge_rules_toredis($mchId);
      break;
    case 'send_msg':
      $grade = $_GET['grade'];
      $title = $_GET['title'];
      $detail    = $_GET['detail'];
      $date      = $_GET['date'];
      $sendtime  = $_GET['sendtime'];
      $time = $date.' '.$sendtime.':00';
      $sql = "INSERT INTO campaigns (mch_id, grade, title, campaign_type, status, send_at, detail, updated_at, created_at) VALUES ('$mchId', $grade, '$title', 'send_sms', 1, '$time', '$detail', '$now', '$now')";
      $db->query($sql);
      echo 'success';
      break;
    case 'point_exchange_add':
      $point = $_GET['point'];
      $couponId = $_GET['coupon_id'];
      $isLimit  = $_GET['is_limit'];
      $exchangeLimit = $isLimit?$_GET['exchange_limit']:99999;
      $singleLimit   = $_GET['single_limit'];

      $sql = "SELECT id FROM app_point_exchange_rules WHERE mch_id = '$mchId' AND point = '$point'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "SELECT name FROM coupons WHERE id = $couponId";
        $row = $db->fetch_row($sql);
        $couponName = $row['name'];

        $sql = "INSERT INTO app_point_exchange_rules (mch_id, point, is_limit, exchange_limit, single_limit, coupon_id, coupon_name, created_at) VALUES ('$mchId', '$point', $isLimit, $exchangeLimit, $singleLimit, $couponId, '$couponName', '$now')";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'point_exchange_delete':
      $id = $_GET['id'];
      $sql = "DELETE FROM app_point_exchange_rules WHERE id = $id";
      $db->fetch_row($sql);
      echo 'success';
      break;
    case 'get_point_exchange_rules':
      $sql = "SELECT * FROM app_point_exchange_rules WHERE mch_id = '$mchId' ORDER BY point";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_point_exchange_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM app_point_exchange_rules WHERE mch_id = '$mchId' AND id = $id";
      $data = $db->fetch_row($sql);
      echo json_encode($data);
      break;
    case 'point_exchange_update':
      $id    = $_GET['id'];
      $point = $_GET['point'];
      $product = $_GET['product'];
      $sql = "SELECT id FROM app_point_exchange_rules WHERE mch_id = '$mchId' AND point = '$point' AND id != '$id'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "UPDATE app_point_exchange_rules SET point = '$point', product = '$product' WHERE id = $id";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'get_opengifts':
      $sql = "SELECT * FROM app_opengifts WHERE mch_id = $mchId AND grade = 1";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'opengift':
      $couponId  = $_GET['coupon_id'];
      $couponCount = $_GET['coupon_count'];
      $sql = "SELECT id FROM campaigns WHERE mch_id = $mchId AND campaign_type = 'opengift' AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $dateStart = date('Y-m-d');
        $dateEnd   = date('Y-m-d', strtotime('+2 years'));
        $sql = "INSERT INTO campaigns (mch_id, title, campaign_type, status, date_start, date_end, created_at) VALUES ('$mchId', '会员开卡礼', 'opengift', 1, '$dateStart', '$dateEnd', '$now')";
        $db->query($sql);
      }
      $sql = "SELECT id FROM app_opengifts WHERE mch_id = $mchId AND coupon_id = $couponId AND grade = 1";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "SELECT name FROM coupons WHERE id = '$couponId'";
        $row = $db->fetch_row($sql);
        $couponName = $row['name'];

        $sql = "INSERT INTO app_opengifts (mch_id, grade, coupon_id, coupon_total, coupon_name, created_at) VALUES ($mchId, 1, $couponId, $couponCount, '$couponName', '$now')";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'delete_opengift':
      $id = $_GET['id'];
      $sql = "DELETE FROM app_opengifts WHERE id = $id";
      $db->query($sql);
      break;
    case 'delete_point_exchange':
      $id = $_GET['id'];
      $sql = "DELETE FROM app_point_exchange_rules WHERE id = $id";
      $db->query($sql);
      break;
    case 'discount':
      $discount  = $_GET['discount'];
      $reduceMax = $_GET['reduce_max']?$_GET['reduce_max']:0;
      $dateStart = $_GET['date_start'];
      $dateEnd   = $_GET['date_end'];
      $title     = $_GET['title'];
      $sql = "SELECT id FROM campaigns WHERE mch_id = '$mchId' AND campaign_type = 'discount' AND (date_end > '$dateStart' AND date_start < '$dateEnd') AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO campaigns (mch_id, title, campaign_type, status, date_start, date_end, discount, reduce_max, updated_at, created_at) VALUES ('$mchId', '$title', 'discount', 1, '$dateStart', '$dateEnd', '$discount', '$reduceMax', '$now', '$now')";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'reduce':
      $shopId = 0;
      $consume   = $_GET['consume'];
      $reduce    = $_GET['reduce'];
      $reduceMax = $_GET['reduce_max']?$_GET['reduce_max']:0;
      $dateStart = $_GET['date_start'];
      $dateEnd   = $_GET['date_end'];
      $title     = '外卖消费满'.$consume.'元立减'.$reduce.'元';
      $sql = "SELECT id FROM campaigns WHERE mch_id = '$mchId' AND campaign_type = 'reduce' AND (date_end > '$dateStart' AND date_start < '$dateEnd') AND is_stop = 0 AND consume = $consume";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO campaigns (mch_id, title, campaign_type, status, date_start, date_end, consume, reduce, reduce_max, updated_at, created_at) VALUES ('$mchId', '$title', 'waimai_reduce', 1, '$dateStart', '$dateEnd', '$consume', '$reduce', '$reduceMax', '$now', '$now')";
        $db->query($sql);
      } else {
        echo 'fail';
      }
      break;
    case 'rebate':
      $shopId = 0;
      $grade     = $_GET['grade'];
      $condition = $_GET['condition'];
      $consume   = $_GET['consume'];
      $couponId  = $_GET['coupon_id'];
      $dateStart = $_GET['date_start'];
      $dateEnd   = $_GET['date_end'];
      $title     = $_GET['title'];
      $sql = "SELECT id FROM campaigns WHERE mch_id = '$mchId' AND campaign_type = 'rebate' AND (date_end > '$dateStart' AND date_start < '$dateEnd') AND is_stop = 0 AND consume = $consume";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO campaigns (mch_id, grade, title, campaign_type, status, coupon_id, coupon_total, date_start, date_end, award_condition, consume, updated_at, created_at) VALUES ('$mchId', $grade, '$title', 'rebate', 1, '$couponId', 1, '$dateStart', '$dateEnd', '$condition', '$consume', '$now', '$now')";
        $db->query($sql);
      } else {
        echo 'fail';
      }
      break;
    case 'send_coupon':
      $grade = $_GET['grade'];
      $title = $_GET['title'];
      $brandName = $_GET['brand_name'];
      $couponId  = $_GET['coupon_id'];
      $couponName = $_GET['coupon_name'];
      $couponTotal = $_GET['count'];
      $comment     = $_GET['comment'];
      $expireTitle = $_GET['expire_title'];
      $date      = $_GET['date'];
      $sendtime  = $_GET['sendtime'];
      $detail    = $_GET['detail'];
      $grade     = $_GET['grade'];
      $isSendSms = $_GET['is_send_sms'];
      $isSendMe  = $_GET['is_send_me'];
      //短信是否也发给管理员自己
      $sql = "SELECT id FROM campaigns WHERE mch_id = '$mchId' AND grade = $grade AND campaign_type = 'send_coupon' AND title = '$title' AND status = 1 AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $time = $date.' '.$sendtime.':00';

        $sql = "INSERT INTO campaigns (mch_id, title, campaign_type, status, grade, coupon_id, coupon_total, send_at, detail, updated_at, created_at) VALUES ('$mchId','$title', 'send_coupon', 1, $grade, '$couponId', $couponTotal, '$time', '$detail','$now', '$now')";
        $db->query($sql);

        if ($isSendSms) {
          $smsParamData = array(
                            'brand' => $brandName,
                            'reason' => $comment,
                            'total'  => $couponTotal,
                            'coupon' => $couponName
                        );
          $smsParams = json_encode($smsParamData, JSON_UNESCAPED_UNICODE);
          $smsTemplateId = 'SMS_171357437';
          $sql = "INSERT INTO campaigns(mch_id, title, campaign_type, status, grade, send_at, detail, sms_params, sms_template_id, updated_at, created_at) VALUES ($mchId, '$title', 'send_sms', 1, $grade, '$time', '$detail', '$smsParams', '$smsTemplateId', '$now', '$now')";
          $db->query($sql);
        }
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM campaigns WHERE id = $id";
      $row = $db->fetch_row($sql);
      if ($row['is_stop']) {
        $row['status_title'] = '已结束';
      } else {
        if ('send_coupon' == $row['campaign_type'] || 'send_sms' == $row['campaign_type']) {
          if ($now > $row['send_at']) {
            $row['status_title'] = '已结束';
          } else {
            $row['status_title'] = '未开始';
          }
        } else {
          $dateEnd = date('Y-m-d 00:00:00', strtotime($row['date_end'].' +1days'));
          if ($now > $row['date_start'] && $now < $dateEnd) {
            $row['status_title'] = '进行中';
          } else if ($now > $dateEnd) {
            $row['status_title'] = '已结束';
          } else {
            $row['status_title'] = '未开始';
          }         
        }
      }
      if ($row['coupon_id']) {
        $sql = "SELECT name FROM coupons WHERE id = $row[coupon_id]";
        $ret = $db->fetch_row($sql);
        $row['coupon_name'] = $ret['name'];
      }
      if ('opengift' == $row['campaign_type']) {
        $sql = "SELECT coupon_name, coupon_total FROM app_opengifts WHERE mch_id = $row[mch_id] AND grade = 1";
        $row['opengifts'] = $db->fetch_array($sql);
      }
      echo json_encode($row);
      break;
    case 'member_day':
      $title = $_GET['title'];
      $day   = $_GET['day'];
      $campaignType = $_GET['campaign'];
      $pointSpeed   = $_GET['point_speed'];
      $consume      = $_GET['consume'];
      $reduce       = $_GET['reduce'];
      $reduceMax    = $_GET['reduce_max'];
      $discount     = $_GET['discount'];
      $couponId     = $_GET['coupon_id'];
      $couponTotal  = $_GET['coupon_total'];

      $sql = "SELECT id FROM campaigns WHERE mch_id = $mchId AND campaign_type = 'member_day' AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
        exit();
      }

      $dateStart = date('Y-m-d');
      $dateEnd   = date('Y-m-d', strtotime('+2 years'));
      $sql = "INSERT INTO campaigns (mch_id, title, date_start, date_end, campaign_type, day, point_speed, award_condition, consume, discount, reduce, reduce_max, coupon_id, coupon_total, updated_at, created_at) VALUES ('$mchId', '$title', '$dateStart', '$dateEnd', 'member_day', $day, $pointSpeed, 'ge', $consume, $discount, $reduce, $reduceMax, $couponId, $couponTotal, '$now', '$now')";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_list':
      $data = array();
      $sql = "SELECT * FROM campaigns WHERE mch_id = '$mchId'";
      $sql .= " ORDER BY created_at DESC";
      $ret = $db->fetch_array($sql);
      foreach ($ret as $row) {
        if ($row['is_stop']) {
          $data['end'][] = $row;
          continue;
        }
        if (in_array($row['campaign_type'], array('send_coupon', 'send_sms'))) {
          if ($now > $row['send_at']) {
            $data['end'][] = $row;
            continue;
          } else {
            $data['notstart'][]= $row;
            continue;
          }
        } else {
          $dateEnd = date('Y-m-d 00:00:00', strtotime($row['date_end'].' +1days'));
          if ($now > $row['date_start'] && $now < $dateEnd) {
            $data['doing'][] = $row;
            continue;
          } else if ($now > $dateEnd) {
            $data['end'][] = $row;
            continue;
          } else {
            $data['notstart'][] = $row;
            continue;
          }         
        }
      }
      echo json_encode($data);
      break;
    case 'get_wakeups':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, title, coupon_total FROM campaigns WHERE mch_id = $mchId AND campaign_type = 'wakeup' AND is_stop = 0 ORDER BY day";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'add_wakeup':
      $mchId = $_GET['mch_id'];
      $title = $_GET['title'];
      $brandName = $_GET['brand_name'];
      $detail    = $_GET['detail'];
      $wakeupDay = $_GET['wakeup_day'];
      $couponId  = $_GET['coupon_id'];
      $couponName = $_GET['coupon_name'];
      $couponTotal = $_GET['count'];
      $isSendMe    = $_GET['is_send_me'];
      $isSendSms   = $_GET['is_send_sms'];

      $sql = "SELECT id FROM campaigns WHERE mch_id = $mchId AND campaign_type = 'wakeup' AND day = '$wakeupDay' AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
        exit();
      }
      if ($isSendSms) {
        $smsParamData = array(
                          'brand' => $brandName,
                          'total'  => $couponTotal,
                          'coupon' => $couponName
                      );
        $smsParams = json_encode($smsParamData, JSON_UNESCAPED_UNICODE);
        $smsTemplateId = 'SMS_171357437';
      } else {
        $smsTemplateId = $smsParams = '';
      }
      $dateStart = date('Y-m-d');
      $dateEnd   = date('Y-m-d', strtotime('+10 years'));
      $sql = "INSERT INTO campaigns (mch_id, grade, title, date_start, date_end, campaign_type, coupon_id, coupon_total, day, detail, sms_params, sms_template_id, is_stop, created_at) VALUES ($mchId, 0, '$title', '$dateStart', '$dateEnd', 'wakeup', $couponId, $couponTotal, $wakeupDay, '$detail', '$smsParams', '$smsTemplateId', 0, '$now')";
      $db->query($sql);
      echo 'success';
      break;
    case 'stop':
      $campaignId = $_GET['id'];
      $today  = date('Y-m-d');
      $sql = "UPDATE campaigns SET is_stop = 1, date_end = '$today' WHERE id = $campaignId";
      $db->query($sql);

      $sql = "SELECT mch_id, campaign_type FROM campaigns WHERE id = $campaignId";
      $row = $db->fetch_row($sql);
      if ('opengift' == $row['campaign_type']) {
        $sql = "DELETE FROM app_opengifts WHERE mch_id = $row[mch_id]";
        $db->query($sql);
      } else if ('payed_share' == $row['campaign_type']) {
        $sql = "DELETE FROM app_payed_shares WHERE mch_id = $row[mch_id]";
        $db->query($sql);
      } else if ('paybuycoupon' == $row['campaign_type']) {
        $redis->hdel('keyou_pay_buy_coupon_data', $row['mch_id']);
      }
      echo 'success';
      break;
    case 'get_pay_result_recommend':
      $sql = "SELECT title FROM mch_pay_result_recommends WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_pay_result_campaigns':
      $campaignType = $_GET['campaign_type'];
      $campaignData = array('0'=>array('id'=>0, 'title'=>'请选择'));
      switch ($campaignType) {
        case 'point':
          $sql = "SELECT id, point, coupon_name FROM app_point_exchange_rules WHERE mch_id = $mchId AND exchange_limit > 0 ORDER BY exchanged DESC";
          $data = $db->fetch_array($sql);
          foreach ($data as $row) {
            $campaignData[] = array('id'=>$row['id'], 'title'=>$row['point'].'积分兑换'.$row['coupon_name']);
          }
          break;
        case 'groupon':
          $sql = "SELECT id, title FROM mch_groupons WHERE mch_id = $mchId AND total_limit > 0 ORDER BY sold DESC";
          $data = $db->fetch_array($sql);
          foreach ($data as $row) {
            $campaignData[] = array('id'=>$row['id'], 'title'=>$row['title']);
          }
          break;
        case 'recharge':
          $sql = "SELECT id, touch, award_type, amount, percent FROM app_recharge_rules WHERE mch_id = $mchId ORDER BY touch";
          $data = $db->fetch_array($sql);
          foreach ($data as &$row) {
            $title = '储值'.$row['touch'];
            if ('coupon' == $row['award_type']) {
              $couponTitle = array();
              $sql = "SELECT coupon_name, total FROM app_recharge_coupon_rules WHERE recharge_id = $row[id]";
              $ret = $db->fetch_array($sql);
              foreach ($ret as $v) {
                $couponTitle[] = '返'.$v['coupon_name'].$v['total'].'张';
              }
              $title .= implode("\n", $couponTitle);
            } else if ('money_constant' == $row['award_type']) {
              $title .= '返'.$row['amount'].'元';
            } else if ('money_percent' == $row['award_type']) {
              $title .= '返'.$row['percent'].'%';
            }
            $campaignData[] = array('id'=>$row['id'], 'title'=>$title);
          }
          break;
        default:
          break;
      }
      echo json_encode($campaignData);
      break;
    case 'update_pay_result_recommend':
      $campaignType = $_GET['campaign_type'];
      $campaignTypeTitle = $_GET['campaign_type_title'];
      $campaignId   = $_GET['campaign_id'];
      $title        = $_GET['title'];
      $sql = "DELETE FROM mch_pay_result_recommends WHERE mch_id = $mchId";
      $db->query($sql);

      $title = $campaignId != 0 ? $title : '随机推荐'.$campaignTypeTitle;
      $sql = "INSERT INTO mch_pay_result_recommends (mch_id, campaign_type, campaign_id, title, created_at) VALUES ($mchId, '$campaignType', $campaignId, '$title', '$now')";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_payed_gifts':
      $sql = "SELECT consume, date_start, date_end FROM campaigns WHERE mch_id = $mchId AND campaign_type = 'pay_gift' AND is_stop = 0 ORDER BY created_at DESC LIMIT 1";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'create_payed_share':
      $grade     = $_GET['grade'];
      $consume   = $_GET['consume'];
      $couponTotal = $_GET['count'];
      $coupons = urldecode($_GET['coupons']);
      $dateStart = $_GET['date_start'];
      $dateEnd   = $_GET['date_end'];
      $sql = "SELECT id, consume FROM campaigns WHERE mch_id = '$mchId' AND campaign_type = 'payed_share' AND (date_end > '$dateStart' AND date_start < '$dateEnd') AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
      } else {
        $sql = "DELETE FROM app_payed_shares WHERE mch_id = $mchId";
        $db->query($sql);

        $couponData = json_decode($coupons, true);
        $couponDetail = '';
        foreach ($couponData as $ret) {
          $couponId = $ret['id'];
          $couponName = $ret['name'];
          $percent    = $ret['percent'];
          $sql = "INSERT INTO app_payed_shares (mch_id, coupon_id, coupon_name, percent, created_at) VALUES ($mchId, $couponId, '$couponName', $percent, '$now')";
          $db->query($sql);

          $couponDetail .= $couponName.$percent.'%抽中,';
        }
        $gradeName = '所有会员';
        if ($grade > 0) {
          $sql = "SELECT name FROM app_grades WHERE grade = $grade AND mch_id = $mchId";
          $row = $db->fetch_row($sql);
          $gradeName = $row['name'];
        }
        $title = $gradeName.'分享有礼活动';
        $detail = '支付满'.$consume.'元可分享'.$couponTotal.'张代金券，'.substr($couponDetail, 0, -1);
        $sql = "INSERT INTO campaigns (mch_id, grade, title, campaign_type, status, coupon_id, coupon_total, date_start, date_end, consume, detail, updated_at, created_at) VALUES ('$mchId', $grade, '$title', 'payed_share', 1, 0, $couponTotal, '$dateStart', '$dateEnd', $consume, '$detail', '$now', '$now')";
        $db->query($sql);
        $data = array(
                      'consume'    => $consume,
                      'date_start' => $dateStart,
                      'date_end'   => $dateEnd,
                      'coupon_total' => $couponTotal
                      );
        $redis->hset('keyou_mch_payed_share', $mchId, serialize($data));
        echo 'success';
      }
      break;
    case 'create_sharecoupon':
      //好友赠券
      $appId   = $_GET['appid'];
      $openId  = $_GET['openid'];
      $couponTotal = $_GET['count'];
      $coupons = urldecode($_GET['coupons']);

      $shareKey = 'user'.substr(md5($openId.time()), 8, 12);
      $couponData = json_decode($coupons, true);
      $couponDetail = '';
      foreach ($couponData as $ret) {
        $couponId = $ret['id'];
        $couponName = $ret['name'];
        $percent    = $ret['percent'];
        $sql = "INSERT INTO user_sharecoupon_rules (mch_id, share_key, openid, coupon_id, coupon_name, percent, created_at) VALUES ($mchId, '$shareKey', '$openId', $couponId, '$couponName', $percent, '$now')";
        $db->query($sql);
      }
      $sql = "INSERT INTO wechat_payed_share_list (mch_id, share_key, openid, out_trade_no, coupon_total, get, created_at) VALUES ($mchId, '$shareKey', '$openId', '', $couponTotal, 0, '$now')";
      $db->query($sql);

      if (SUISHOUHUI_APP_ID == $appId) {
        $miniAccessToken = $redis->hget('keyou_mini', 'access_token');//随手惠生活ACCESSTOKEN
      } else {
        $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      }
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/index/payed_share_cash?key='.$shareKey.'&payed_share='.$couponTotal);
      $buffer = sendHttpRequest($url, $data);

      $object = 'coupon/'.date('Ymd').'/'.$shareKey.'.png';
      $qrcodeUrl = putOssObject($object, $buffer);

      $sql = "SELECT business_name, city, district, address, open_time FROM shops WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $merchantName = $ret['business_name'];
      $city         = $ret['city'];
      $district     = $ret['district'];
      $address      = $ret['address'];
      $openTime     = $ret['open_time'];
      $shopAdress   = '地址:'.$city.$district.$address;

      $title = '【'.$merchantName.'】'.$couponName;
      if ($couponTotal > 1) {
        $title .= '等'.$couponTotal.'张券';
      }
      if (mb_strlen($title) > 16) {
        $markTitleLineOne = mb_substr($title, 0, 15);
        $markTitleLineTwo = mb_substr($title, 15, 30);
      } else {
        $markTitleLineOne = $title;
        $markTitleLineTwo = '';
      }

      $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $couponId AND is_icon = 1";
      $row = $db->fetch_row($sql);
      if ($row['image_url']) {
        $imageUrl = $row['image_url'];
      } else {
        $sql = "SELECT logo_url FROM user_mch_submit WHERE sub_mch_id = '$mchId'";
        $ret = $db->fetch_row($sql);
        $imageUrl = $ret['logo_url'];
      }

      $headImgObject = 'coupon/'.date('Ymd').'/'.substr(md5('coupon_'.$couponId.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($headImgObject, file_get_contents($imageUrl));

      $markHeadImgUrl = $photoUrl.'?x-oss-process=image/resize,w_620,h_550,limit_0,m_fill';
      $markHeadObject = 'coupon/'.date('Ymd').'/'.substr(md5('coupon_mark'.$couponId.'_'.time()), 8, 8).'.jpg';
      $markedUrl = putOssObject($markHeadObject, file_get_contents($markHeadImgUrl));

      $url = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/coupon/20200104/sharecouponbg.jpg?x-oss-process=image/resize,w_750/watermark,';
      $url .= 'image_'.urlencode(base64_encode($markHeadObject)).',t_90,g_nw,x_60,y_70/watermark,';
      $url .= 'image_'.urlencode(base64_encode($object.'?x-oss-process=image/resize,P_22')).',t_90,g_se,x_110,y_130/watermark,';
      $lineOneObject = str_replace('/', '_', base64_encode($markTitleLineOne));
      $lineOneObject = str_replace('+', '-', $lineOneObject);
      $url .= 'text_'.$lineOneObject.',color_000000,size_42,g_sw,x_65,y_470';
      if ($markTitleLineTwo) {
        $lineTwoObject = str_replace('/', '_', base64_encode($markTitleLineTwo));
        $lineTwoObject = str_replace('+', '-', $lineTwoObject);
        $url .= '/watermark,text_'.$lineTwoObject.',size_42,g_sw,x_65,y_410';
      }

      $shareImgObject = 'coupon/'.date('Ymd').'/'.substr(md5('sharecoupon_'.$couponId.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($shareImgObject, file_get_contents($url));

      echo $photoUrl;
      break;
    case 'create_rechargenopay':
      $consume  = $_GET['consume'];
      $count    = $_GET['count'];
      $discount = $_GET['discount'];
      $dateStart= $_GET['date_start'];
      $dateEnd  = $_GET['date_end'];
      $title    = $_GET['title'];
      $sql = "SELECT id FROM campaigns WHERE mch_id = $mchId AND campaign_type = 'rechargenopay' AND (date_end > '$dateStart' AND date_start < '$dateEnd') AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
      } else {
        //total=>储值倍数
        $sql = "INSERT INTO campaigns (mch_id, title, campaign_type, status, award_condition, consume, discount, total, date_start, date_end, updated_at, created_at) VALUES ($mchId, '$title', 'rechargenopay', 1, 'egt', $consume, $discount, $count, '$dateStart', '$dateEnd', '$now', '$now')";
        $db->query($sql);
        echo $db->get_insert_id();
      }
      break;
    case 'create_paybuycoupon':
      $consume  = $_GET['consume'];
      $couponId = $_GET['coupon_id'];
      $couponName = $_GET['coupon_name'];
      $couponTotal= $_GET['coupon_total'];
      $amount   = $_GET['amount'];
      $dateStart= $_GET['date_start'];
      $dateEnd  = $_GET['date_end'];
      $title    = '加'.$amount.'元购'.$couponName.$couponTotal.'张';
      $sql = "SELECT id FROM campaigns WHERE mch_id = $mchId AND campaign_type = 'paybuycoupon' AND (date_end > '$dateStart' AND date_start < '$dateEnd') AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
      } else {
        $sql = "INSERT INTO campaigns (mch_id, title, campaign_type, status, consume, coupon_amount, coupon_id, coupon_total, date_start, date_end, updated_at, created_at) VALUES ($mchId, '$title', 'paybuycoupon', 1, $consume, $amount, $couponId, $couponTotal, '$dateStart', '$dateEnd', '$now', '$now')";
        file_put_contents('/tmp/mchsql', $sql.PHP_EOL,FILE_APPEND);
        $db->query($sql);
        echo $db->get_insert_id();
      }
      $redis->hset('keyou_pay_buy_coupon_data', $mchId, json_encode($_GET));
      break;
    case 'create_lbs_coupon':
      $couponId = $_GET['coupon_id'];
      $title    = $_GET['title'];
      $dateStart= $_GET['date_start'];
      $dateEnd  = $_GET['date_end'];
      $sql = "SELECT id FROM campaigns WHERE mch_id = '$mchId' AND campaign_type = 'lbs_coupon' AND (date_end > '$dateStart' AND date_start < '$dateEnd') AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
      } else {
        $sql = "INSERT INTO campaigns (mch_id, grade, title, campaign_type, status, coupon_id, coupon_total, date_start, date_end, updated_at, created_at) VALUES ('$mchId', 0, '$title', 'lbs_coupon', 1, $couponId, 1, '$dateStart', '$dateEnd', '$now', '$now')";
        $db->query($sql);
        echo $db->get_insert_id();
      }
      break;
    default:
      break;
  }

  function load_recharge_rules_toredis($mchId)
  {
    global $db, $redis;
    $sql = "SELECT id, touch, award_type, amount, percent FROM app_recharge_rules WHERE mch_id = $mchId ORDER BY touch";
    $data = $db->fetch_array($sql);
    foreach ($data as $k=>$row) {
      if ('coupon' == $row['award_type']) {
        $sql = "SELECT coupon_name, total FROM app_recharge_coupon_rules WHERE recharge_id = $row[id]";
        $ret = $db->fetch_array($sql);
        $data[$k]['coupons'] = $ret;
      }
    }
    $redis->hset('keyou_mch_recharge_rules', $mchId, serialize($data));
  }
