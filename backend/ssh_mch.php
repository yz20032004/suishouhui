<?php
  require_once 'common.php';
  require_once dirname(__FILE__).'/lib/WxPay.Api.php';
  require_once dirname(__FILE__).'/unit/WxPay.JsApiPay.php';
  require_once dirname(__FILE__).'/unit/log.php';

  //防止MYSQL注入
  foreach ($_GET AS $key=>&$v) {
    $v = str_replace("'", '', $v);
    $v = str_replace('"', '', $v);
  }
  foreach ($_POST AS $key=>&$v) {
    $v = str_replace("'", '', $v);
    $v = str_replace('"', '', $v);
  }

  $action = $_GET['action'];
  $mchId  = isset($_GET['mch_id'])?$_GET['mch_id']:$_POST['mch_id'];
  $now    = date('Y-m-d H:i:s');

  switch ($action) {
    case 'get_detail':
      $sql = "SELECT * FROM mchs WHERE mch_id = '$mchId'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_mch_submit_info':
      $sql = "SELECT mch_type, category, mobile, account_name, account_number FROM user_mch_submit WHERE sub_mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'search_member':
      $grade  = $_GET['grade'];
      $keyword = $_GET['keyword'];
      $pageCount = $_GET['page_count'];
      $page      = $_GET['page'];
 
      $conditionSql = '';
      if ($grade) {
        $conditionSql .= " AND grade = $grade";
      }
      if ($keyword) {
        $conditionSql .= " AND (nickname LIKE '%$keyword%' OR mobile = '$keyword')";
      }
      $sql = "SELECT COUNT(id) AS total FROM members WHERE mch_id = '$mchId' ".$conditionSql." AND sub_openid != ''";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1)*$pageCount;
      $conditionSql .= " AND sub_openid != '' ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $sql = "SELECT openid, sub_openid, nickname, name, headimgurl, grade_title, mobile FROM members WHERE mch_id = '$mchId' ".$conditionSql;

      file_put_contents('/tmp/mchsql', $sql.PHP_EOL,FILE_APPEND);

      $data = $db->fetch_array($sql);
      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'get_grades':
      $sql = "SELECT mch_id, id, name, `condition`, `grade` FROM app_grades WHERE mch_id = '$mchId' ORDER BY `grade`";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'add_grade':
      $name = trim($_GET['name']);
      $discount = isset($_GET['discount'])?$_GET['discount']:0;
      $pointSpeed = $_GET['point_speed'];
      $privilege = trim(addslashes($_GET['privilege']));
      $condition = $_GET['condition'];
      $catch     = $_GET['catch_value'];
      $validDays = isset($_GET['valid_days']) ? $_GET['valid_days'] : $_GET['valid_days'];

      $conditionTitle = '';
      switch ($condition) {
        case 'recharge':
          $conditionTitle = '充值满'.$catch.'元获得';
          break;
        case 'frequency':
          $conditionTitle = '消费满'.$catch.'次获得';
          break;
        case 'amount':
          $conditionTitle = '消费满'.$catch.'元获得';
          break;
        case 'pay':
          $conditionTitle = '支付'.$catch.'元购买';
          break;
        default:
          break;
      }
      
      $sql = "SELECT MAX(grade) AS grade FROM app_grades WHERE mch_id = '$mchId'";
      $row = $db->fetch_row($sql);
      $maxGrade = $row['grade'];

      $grade = $maxGrade + 1;
      $sql = "INSERT INTO app_grades (mch_id, name, `condition`, catch_type, catch_value, privilege, `grade`, valid_days, discount, point_speed, created_at) VALUES ('$mchId', '$name', '$conditionTitle', '$condition', '$catch', '$privilege', $grade, '$validDays', '$discount', '$pointSpeed', '$now')";
      $db->query($sql);
      echo 'success';
      break;
    case 'update_grade':
      $id = $_GET['id'];
      $grade = $_GET['grade'];
      $name = trim($_GET['name']);
      $discount = isset($_GET['discount'])?$_GET['discount']:0;
      $pointSpeed = $_GET['point_speed'];
      $privilege = trim(addslashes($_GET['privilege']));
      $condition = $_GET['condition'];
      $catch     = $_GET['catch_value'];
      $validDays = isset($_GET['valid_days']) ? $_GET['valid_days'] : 0;

      $conditionTitle = '';
      switch ($condition) {
        case 'recharge':
          $conditionTitle = '充值满'.$catch.'元获得';
          break;
        case 'frequency':
          $conditionTitle = '消费满'.$catch.'次获得';
          break;
        case 'amount':
          $conditionTitle = '消费满'.$catch.'元获得';
          break;
        case 'pay':
          $conditionTitle = '支付'.$catch.'元购买';
          break;
        default:
          break;
      }
      $sql = "UPDATE app_grades SET name = '$name', `condition` = '$conditionTitle', catch_type = '$condition', catch_value = '$catch', privilege = '$privilege', valid_days = '$validDays', discount = '$discount', point_speed = '$pointSpeed' WHERE mch_id = '$mchId' AND id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'delete_grade':
      //@todo 需要将该等级的会员降级
      $id = $_GET['id'];
      $sql = "SELECT * FROM app_grades WHERE id = $id";
      $row = $db->fetch_row($sql);
      $condition = $row['catch_type'];
      $deleteGrade =  $row['grade'];
      $downGrade = $deleteGrade - 1;

      $sql = "SELECT * FROM app_grades WHERE mch_id = '$mchId' AND `grade` = $downGrade";
      $row = $db->fetch_row($sql);
      $downGradeName = $row['name'];

      $sql = "DELETE FROM app_grades WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_grade':
      $id = $_GET['id'];
      $sql = "SELECT * FROM app_grades WHERE id = $id";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_members_total':
      $grade = isset($_GET['grade']) ? $_GET['grade'] : 0;
      $sql = "SELECT COUNT(id) AS total FROM members WHERE mch_id = $mchId";
      if ($grade) {
        $sql .= " AND grade = $grade";
      }
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $sql = "SELECT COUNT(id) AS total FROM members WHERE mch_id = $mchId AND mobile != ''";
      if ($grade) {
        $sql .= " AND grade = $grade";
      }
      $row = $db->fetch_row($sql);
      $mobileTotal = $row['total'];
      
      $data = array('total'=>$total, 'mobile_total'=>$mobileTotal);
      echo json_encode($data);
      break;
    case 'get_staff_detail':
      $id = $_GET['id'];
      $sql = "SELECT id, name, mobile, status FROM users WHERE id = '$id'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_staff_list':
      $sql = "SELECT id, openid, role, name, head_img, branch_name, status FROM users WHERE mch_id = '$mchId' AND is_admin = 0";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'add_staff':
      $shopId = $_GET['shop_id'];
      $name   = $_GET['name'];
      $role   = $_GET['role'];
      $sql = "INSERT INTO users (mch_id, shop_id, role, is_admin, name, created_at) VALUES ('$mchId', '$shopId', '$role', 0, '$name', '$now')";
      $db->query($sql);
      echo 'success';
      break;
    case 'disable_staff':
      $id = $_GET['id'];
      $sql = "UPDATE users SET status = 0 WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'enable_staff':
      $id = $_GET['id'];
      $sql = "UPDATE users SET status = 1 WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'update_staff':
      $id = $_GET['id'];
      $name   = $_GET['name'];
      $mobile = $_GET['mobile'];
      $sql = "UPDATE users SET name='$name', mobile='$mobile' WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'delete_staff':
      $id = $_GET['id'];
      $sql = "DELETE FROM users WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_campaigns':
      $today = date('Y-m-d');
      $sql = "SELECT coupon_id, coupon_total, award_condition, consume, campaign_type, reduce, discount, reduce_max FROM campaigns WHERE mch_id = '$mchId' AND date_start <= '$today' AND date_end >= '$today' AND is_stop = 0 AND campaign_type IN ('rebate', 'reduce', 'discount')";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_point_rule':
      $sql = "SELECT * FROM app_point_rules WHERE mch_id = '$mchId'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'update_point_rule':
      $canCash          = $_GET['can_cash'];
      $awardNeedConsume = $_GET['award_need_consume'];
      $exchangeNeedPoints = $_GET['exchange_need_points'];
      $rechargePointSpeed = $_GET['recharge_point_speed'];
      $sql = "SELECT id FROM app_point_rules WHERE mch_id = '$mchId'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO app_point_rules (mch_id, can_used_for_money, award_need_consume, exchange_need_points, recharge_point_speed, updated_at, created_at) VALUES ('$mchId', '$canCash', $awardNeedConsume, '$exchangeNeedPoints', $rechargePointSpeed, '$now', '$now')";
      } else {
        $sql = "UPDATE app_point_rules SET can_used_for_money = '$canCash', award_need_consume = $awardNeedConsume, exchange_need_points = '$exchangeNeedPoints',  recharge_point_speed = $rechargePointSpeed, updated_at = '$now'  WHERE mch_id = '$mchId'";
      }
      $db->query($sql);

      $redis->hset('keyou_mch_point_rules', $mchId, serialize($_GET));
      echo 'success';
      break;
    case 'get_counter_list':
      $sql = "SELECT * FROM app_counters WHERE mch_id = $mchId AND counter_type = 'scan' ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_scan_counter':
      $sql = "SELECT * FROM app_counters WHERE mch_id = $mchId AND counter_type = 'scan'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_counter':
      $id = $_GET['id'];
      $sql = "SELECT * FROM app_counters WHERE id = $id";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'delete_counter':
      $id = $_GET['id'];
      $sql = "DELETE FROM app_counters WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'add_counter':
      $counterName = $_GET['name'];
      $merchantName= $_GET['merchant_name'];
      $counter     = mt_rand(10000000, 99999999);
      $sql = "SELECT id FROM app_counters WHERE counter = $counter";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $counter   = mt_rand(10000000, 99999999);       
      }
      $sql = "SELECT business_name FROM shops WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $merchantName = $row['business_name'];
      $sql = "INSERT INTO app_counters (mch_id, merchant_name, counter, name, created_at) VALUES ($mchId, '$merchantName', $counter, '$counterName', '$now')";
      $db->query($sql);
      $counterId = $db->get_insert_id();
      $miniAccessToken = $redis->hget('keyou_mini', 'access_token');
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/index/selfpay?counter='.$counter);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('wxacode_'.$mchId.$counter), 8, 16);
      $object = 'selfpayqrcode/'.date('Ymd').'/'.$filename.'.png';
      $wxCodeUrl = putOssObject($object, $buffer);


      $payUrl = 'http://pay.keyouxinxi.com/selfpay.php?counter='.$counter;

      include('./lib/phpqrcode/phpqrcode.php'); 
      // 二维码数据 
      $qrcode = $payUrl;
      $errorCorrectionLevel = 'L';  
      // 点的大小：1到10 
      $matrixPointSize = 8;
      // 生成的文件名 
      $buffer = '/mnt/tmp/qrcodes/counter_'.$counter.'.png'; 
      QRcode::png($qrcode, $buffer, $errorCorrectionLevel, $matrixPointSize, 2); 


      $filename = substr(md5('qrcode_'.$mchId.$counter), 8, 16);
      $object = 'selfpayqrcode/'.date('Ymd').'/'.$filename.'.png';
      $qrCodeUrl = putOssObject($object, file_get_contents($buffer));
      unlink($buffer);

      $sql = "UPDATE app_counters SET qrcode_url = '$qrCodeUrl', wxcode_url = '$wxCodeUrl' WHERE id = $counterId";
      $db->query($sql);
      echo json_encode(array('id'=>$counterId));
      break;
    case 'get_wechat_group':
      $sql = "SELECT * FROM app_wechat_groups WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'upload_wechat_group_photo':
      $openId = $_POST['openid'];
      $ret = explode('.', $_FILES['file']['name']);

      $extension = end($ret);
      $tmpFile = '/tmp/wechatgroup_'.$openId.'.'.$extension;
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);
      $media = '@'.$tmpFile;
      //上传到微信
      $miniAccessToken = $redis->hget('keyou_mini', 'access_token');
      exec('curl -F media='.$media.' "https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$miniAccessToken.'&type=image"', $result);
      $retData = json_decode($result[0], true);
      $mediaId = $retData['media_id'];
      unlink($tmpFile);
      $data = array('media_id'=>$mediaId);
      echo json_encode($data);
      break;
    case 'update_wechat_group':
      $mediaId = $_GET['media_id'];
      $guide   = $_GET['guide'];
      $expireAt = date('Y-m-d', strtotime('+ 7days'));
      $sql = "SELECT id FROM app_wechat_groups WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE app_wechat_groups SET media = '$mediaId', guide = '$guide', expire_at = '$expireAt', updated_at = '$now' WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO app_wechat_groups (mch_id, media, guide, expire_at, updated_at, created_at) VALUES ($mchId, '$mediaId', '$guide', '$expireAt', '$now', '$now')";
      }
      $db->query($sql);
      echo 'success';
      break;
    case 'update_top_background_img':
      $imageUrlStr  = $_GET['image_url'];
      $sql = "DELETE FROM mch_index_top_images WHERE mch_id = $mchId";
      $db->query($sql);

      $imageUrlList = explode(',', $imageUrlStr);
      foreach ($imageUrlList as $url) {
        if ($url) {
          $sql = "INSERT INTO mch_index_top_images(mch_id, pic_url, created_at) VALUES ($mchId, '$url', '$now')";
          $db->query($sql);
        }
      }
      break;
    case 'get_rebate_campaign':
      $sql = "SELECT grade, title, coupon_id, coupon_total, award_condition, consume FROM campaigns WHERE mch_id = '$mchId' AND campaign_type = 'rebate' AND date_start <= '$now' AND date_end >= '$now' AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['coupon_id']) {
        $sql = "SELECT name FROM coupons WHERE id = $row[coupon_id]";
        $ret = $db->fetch_row($sql);
        $row['coupon_name'] = $ret['name'];
      }
      echo json_encode($row);
      break;
    case 'get_revenue_today':
      $sql = "SELECT groupon_revenue, wait_cash_out, cash_out, total_revenue, service_fee FROM mch_revenues WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      if (!$row) {
        $row = array(
                    'groupon_revenue'=>'0.00',
                    'wait_cash_out'=>'0.00',
                    'cash_out'=>'0.00',
                    'total_revenue'=>'0.00',
                    'service_fee'  => '0.00');
      }
      echo json_encode($row);
      break;
    case 'get_cash_out_history':
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $sql = "SELECT COUNT(id) AS total FROM mch_cash_out_history WHERE mch_id = $mchId AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT cash_out,created_at FROM mch_cash_out_history WHERE mch_id = $mchId  AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      $sql .= " ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);

      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'cashout':
      $openId   = $_GET['openid'];
      $username = $_GET['username'];
      $cashout  = $_GET['cashout'];
      $serviceFee = $cashout * 0.01;
      $amount     = $cashout - $serviceFee;

      $partnerTradeNo = getOutTradeNo();
      $ip =  $_SERVER['REMOTE_ADDR'];
      $data = array(
                  'mch_appid'   => SSHGJ_APP_ID,
                  'mchid'  => KEYOU_MCHID,
                  'nonce_str'  => WxPayApi::getNonceStr(),
                  'partner_trade_no' => $partnerTradeNo,
                  'openid'       => $openId,
                  'check_name'   => 'FORCE_CHECK',
                  're_user_name' => $username,
                  'amount'       => $amount * 100,
                  'desc'         => '团购收入提现',
                  'spbill_create_ip' => $ip
                  );
       $sign = MakeKeyouSign($data);
       $data['sign'] = $sign;
       $xml = ToXml($data);
       //统一下单
       $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
       $response = postKeyouXmlCurl($xml, $url, true, 6);
       $responseData = FromXml($response);
       if ('SUCCESS' == $responseData['return_code'] && 'SUCCESS' == $responseData['result_code']) {
         $sql = "INSERT INTO mch_cash_out_history (mch_id, openid, re_user_name, partner_trade_no, cash_out, spbill_create_ip, created_at) VALUES ($mchId, '$openId', '$username', '$partnerTradeNo', $amount, '$ip', '$now')";
         $db->query($sql);

         $sql = "UPDATE mch_revenues SET wait_cash_out = wait_cash_out - $cashout, cash_out = cash_out + $amount, service_fee = service_fee + $serviceFee, updated_at = '$now' WHERE mch_id = $mchId";
         $db->query($sql);
         $result = array('result'=>'success');
       } else {
         if ('SYSTEMERROR' == $responseData['err_code']) {
          //todo 有可能会打款成功，用原订单号重试
         }
         $result = array('result'=>'fail', 'message'=>$responseData['err_code_des']);
       }
       echo json_encode($result);
      break;
    case 'update_functions':
      $mchId = $_GET['mch_id'];
      $isGroupon = $_GET['is_groupon'];
      $isDistribute = $_GET['is_distribute'];
      $isTogether   = $_GET['is_together'];
      $isMall       = $_GET['is_mall'];
      $isPayedShare = $_GET['is_payed_share'];
      $isPayGift    = $_GET['is_pay_gift'];
      $isRecharge   = $_GET['is_recharge'];
      $isRechargenopay = $_GET['is_rechargenopay'];
      $isVipcard    = $_GET['is_vipcard'];
      $isWakeup     = $_GET['is_wakeup'];
      $isMemberday  = $_GET['is_memberday'];
      $marketingType= $_GET['marketing_type'];
      $isGrade      = $_GET['is_grade'];
      $isWaimai     = $_GET['is_waimai'];
      $isReduce     = $_GET['is_reduce'];
      $isOrdering   = $_GET['is_ordering'];
      $isTiming     = $_GET['is_timing'];
      $isPayBuyCoupon = $_GET['is_paybuycoupon'];
      $isShareCoupon  = $_GET['is_sharecoupon'];
      $isWechatGroup  = $_GET['is_wechatgroup'];

      $sql = "UPDATE mchs SET is_timing = $isTiming, is_paybuycoupon = $isPayBuyCoupon, is_sharecoupon = $isShareCoupon, is_grade = $isGrade, is_waimai = $isWaimai, is_reduce = $isReduce, is_ordering = $isOrdering, is_recharge = $isRecharge, is_groupon = $isGroupon, is_distribute = $isDistribute, is_together = $isTogether, is_payed_share = $isPayedShare, is_pay_gift = $isPayGift, is_rechargenopay = $isRechargenopay, is_vipcard = $isVipcard, is_wakeup = $isWakeup, is_memberday = $isMemberday, is_wechatgroup = $isWechatGroup, is_mall = $isMall, marketing_type = '$marketingType' WHERE mch_id = $mchId";
      $db->query($sql);

      $sql = "SELECT * FROM mchs WHERE mch_id = '$mchId'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'export_members':
      $fileName = 'members_'.time().'.xlsx';
      $tmpFile  = 'tmpfiles/'.$fileName;
      $headArr  = array('姓名', '手机号', '性别', '生日', '会员等级', '微信昵称', '积分余额', '储值余额', '消费次数', '消费总额', '储值总额', '等级有效期', '最近消费', '开卡时间');
      $sql = "SELECT COUNT(id) AS total FROM members WHERE mch_id = $mchId AND mobile != ''";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      if (!$total) {
        echo 'fail';
        exit();
      }
      $memberData = array();
      for($i=0;$i<$total;$i+=100) {
        $start = $i;
        $sql = "SELECT name, mobile, gender, birthday, grade_title, nickname, point, recharge, consumes, amount_total, recharge_total, expired_at, last_consume_at, created_at FROM members WHERE mch_id = $mchId AND mobile != '' LIMIT $start, 100";
        $ret = $db->fetch_array($sql);
        foreach ($ret as &$row) {
          foreach ($row as $k=>&$v) {
            if ('gender' == $k) {
              $v = '1' == $v ? '男' : '女';
            } else if ('expire_at' == $k) {
              $v = '0000-00-00 00:00:00' == $v ? '永久' : $v;
            }
          }
        }
        $memberData = array_merge($memberData, $ret);
      }
      importExcel($tmpFile, $headArr, $memberData);

      $object = 'memberfiles/'.date('Ymd').'/'.$fileName;
      $downloadUrl = putOssObject($object, file_get_contents($tmpFile));
      unlink($tmpFile);
      echo $downloadUrl;
      break;
    case 'get_ordering_config':
      $sql = "SELECT * FROM mch_ordering_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'update_ordering_config':
      $isOpen = $_GET['is_open'];
      $payFirst = $_GET['pay_first'];
      $orderTimeStart = $_GET['time_start'];
      $orderTimeEnd   = $_GET['time_end'];
      $sql = "UPDATE mch_ordering_configs SET is_open = $isOpen, pay_first = $payFirst, order_time_start = '$orderTimeStart', order_time_end = '$orderTimeEnd' WHERE mch_id = $mchId";
      $db->query($sql);
      if (isset($_GET['jiabo_device_no'])) {
        $sql = "UPDATE mch_ordering_configs SET jiabo_device_no = '$_GET[jiabo_device_no]' WHERE mch_id = $mchId";
        $db->query($sql);
      }
      echo 'success';
      break;
    case 'get_research_config':
      $sql = "SELECT * FROM mch_form_list WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'update_research_config':
      $mchId  = $_GET['mch_id'];
      $isOpen = $_GET['is_open'];
      $formId = $_GET['formid'];
      $awardType = $_GET['award_type'];
      $awardValue= $_GET['award_value'];
      $awardCouponName = $_GET['coupon_name'];
      $sql = "SELECT id FROM mch_form_list WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE mch_form_list SET is_open = $isOpen, formid = '$formId', award_type = '$awardType', award_value = '$awardValue', coupon_name = '$awardCouponName' WHERE mch_id = $mchId";
      } else {
        $sql = "INSERT INTO mch_form_list (mch_id, is_open, formid, award_type, award_value, coupon_name, created_at) VALUES ($mchId, $isOpen, '$formId', '$awardType', '$awardValue', '$awardCouponName', '$now')";
      }
      echo $sql;
      $db->query($sql);
      echo 'success';
      break;
    case 'get_research_form_qrcode':
      $id = $_GET['id'];
      $appId = $_GET['appid'];
      if (SUISHOUHUI_APP_ID == $appId) {
        $miniAccessToken = $redis->hget('keyou_mini', 'access_token');//随手惠生活ACCESSTOKEN
      } else {
        $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $ret['appid']);
      } 
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/form/index?id='.$id);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('form_'.$id.time()), 8, 16);
      $object = 'form/'.date('Ymd').'/'.$filename.'.png';
      $qrcodeUrl = putOssObject($object, $buffer);

      $row['qrcode_url'] = $qrcodeUrl;
      echo json_encode($row);
      break;
    case 'get_waimai_config':
      $sql = "SELECT * FROM mch_waimai_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'update_waimai_config':
      $isOpen = $_GET['is_open'];
      $deliveryDistance = $_GET['delivery_distance'];
      $deliveryTime     = $_GET['delivery_time'];
      $costAtleast = $_GET['cost_atleast'];
      $deliveryCost = $_GET['delivery_cost'];
      $deliveryFreeAtleast = $_GET['delivery_free_atleast'];
      $packageCost = $_GET['package_cost'];
      $canRecharge = $_GET['can_recharge'];
      $canSelf     = $_GET['can_self'];
      $deliveryTimeStart = $_GET['time_start'];
      $deliveryTimeEnd   = $_GET['time_end'];
      $sql = "UPDATE mch_waimai_configs SET is_open = $isOpen, can_self = $canSelf, can_recharge = $canRecharge, delivery_distance = $deliveryDistance, delivery_time = '$deliveryTime', cost_atleast = $costAtleast, delivery_cost = $deliveryCost, delivery_free_atleast = $deliveryFreeAtleast, package_cost = $packageCost, delivery_time_start = '$deliveryTimeStart', delivery_time_end = '$deliveryTimeEnd' WHERE mch_id = $mchId";
      $db->query($sql);
      if (isset($_GET['jiabo_device_no'])) {
        $sql = "UPDATE mch_waimai_configs SET jiabo_device_no = '$_GET[jiabo_device_no]' WHERE mch_id = $mchId";
        $db->query($sql);
      }
      $sql = "UPDATE mchs SET is_selftaking = $canSelf WHERE mch_id = $mchId";
      $db->query($sql);
      echo 'success';
      break;
    case 'bind_tenpay':
      $mchId = $_GET['mch_id'];
      $authenKey= $_GET['authen_key'];
      $outShopId = $_GET['out_shop_id'];
      $outMchId    = $_GET['out_mch_id'];
      $outSubMchId = $_GET['out_sub_mch_id'];
      $cloudCashierId = $_GET['cloud_cashier_id'];
      $sql = "UPDATE app_counters SET tenpay_shopid = '$outShopId' WHERE mch_id = $mchId";
      $db->query($sql);
      $sql = "SELECT id FROM mch_tenpay_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE mch_tenpay_configs SET authen_key = '$authenKey', out_shop_id = '$outShopId', out_mch_id = '$outMchId', out_sub_mch_id = '$outSubMchId', cloud_cashier_id = '$cloudCashierId', created_at = '$now' WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO mch_tenpay_configs (mch_id, authen_key, out_shop_id, out_mch_id, out_sub_mch_id,cloud_cashier_id, created_at) VALUES ($mchId, '$authenKey', '$outShopId', '$outMchId', '$outSubMchId', '$cloudCashierId', '$now')";
      }
      $db->query($sql);
      $sql = "UPDATE mchs SET pay_platform = 'tenpay' WHERE mch_id = $mchId";
      $db->query($sql);
      echo 'success';
      break;
    case 'update_mchid':
      $newMchId = $_GET['new_mch_id'];
      if (!$newMchId) {
        return;
      }
      $sql = "SHOW TABLES";
      $data = $db->fetch_array($sql);
      foreach ($data as $row) {
        $table = current($row);
        $sql = "UPDATE $table SET mch_id = $newMchId WHERE mch_id = $mchId";
        $db->query($sql);
      }
      $sql = "UPDATE user_mch_submit SET sub_mch_id = $newMchId WHERE sub_mch_id = $mchId LIMIT 1";
      $db->query($sql);

      $sql = "UPDATE user_mch_submit SET mch_type = 'getihu' WHERE sub_mch_id = $newMchId";
      $db->query($sql);
      $sql = "UPDATE mchs SET mch_tpe = 'getihu' WHERE mch_id = $newMchId";
      $db->query($sql);

      $sql = "SELECT appid FROM mchs WHERE mch_id = $newMchId";
      $ret = $db->fetch_row($sql);
      if (SUISHOUHUI_APP_ID == $ret['appid']) {
        $miniAccessToken = $redis->hget('keyou_mini', 'access_token');//随手惠生活ACCESSTOKEN
      } else {
        $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $ret['appid']);
      }
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/index/get_membercard?mch_id='.$newMchId);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('membercard_'.$newMchId.time()), 8, 16);
      $object   = 'xiaowei/'.date('Ymd').'/'.$filename.'.png';
      $cardUrl  = putOssObject($object, $buffer);
      $sql = "UPDATE shops SET card_url = '$cardUrl' WHERE mch_id = $newMchId";
      $db->query($sql);

      $sql = "SELECT member_cardid FROM user_mch_submit WHERE sub_mch_id = $newMchId";
      $row = $db->fetch_row($sql);
      $cardId = $row['member_cardid'];
      $redis->hset('keyou_card_mch', $cardId, $newMchId);
      $redis->hset('keyou_mch_card', $newMchId, $cardId);
      $pointRules = $redis->hget('keyou_mch_point_rules', $mchId);
      if ($pointRules) {
        $redis->hset('keyou_mch_point_rules', $newMchId, $pointRules);
      }
  
      $sql = "SELECT * FROM mchs WHERE mch_id = $newMchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    default:
      break;
  }

  /**
   * Excel导出
   * @param $fileName（文件名）
   * @param $headArr （表头）
   * @param $data  （每一行的数据）
   * @throws \PHPExcel_Exception
   * @throws \PHPExcel_Reader_Exception
   */
  function importExcel($fileName,$headArr,$data){
    include_once ("lib/PHPExcel/PHPExcel.php");
    include_once ("lib/PHPExcel/PHPExcel/Writer/Excel2007.php");
    include_once ("lib/PHPExcel/PHPExcel/Writer/Excel5.php");
    include_once ("lib/PHPExcel/PHPExcel/IOFactory.php");
    if(empty($data) || !is_array($data)){
      die("data must be a array");
    }
    if(empty($fileName)){
      exit;
    }
  
    //创建新的PHPExcel对象
    $objPHPExcel = new PHPExcel();
    $objProps = $objPHPExcel->getProperties();
  
    //设置表头
    $key = ord("A");
    $key2 = ord("A");
    $colum2 = '';
    $objActSheet = $objPHPExcel->getActiveSheet();
    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
    foreach($headArr as $v){
      $colum = chr($key);
      $objPHPExcel->setActiveSheetIndex(0) ->setCellValue($colum2.$colum.'1', $v);
      if($key < 90){
        $key += 1;
      }else{
        $key = ord("A");
        $colum2 = chr($key2);
        $key2++;
      }
    }
    //exit;
    $column = 2;
  
    foreach($data as $key => $rows){ //行写入
      $span = ord("A");
      $span2 = ord("A");
      $j2 = '';
      foreach($rows as $keyName=>$value){// 列写入
        $j = chr($span);
        //$objActSheet->setCellValue($j.$column, $value);
        //把每个单元格设置成分文本类型
        //dump($j2.$j.$column);
        $objActSheet->setCellValueExplicit($j2.$j.$column,$value,\PHPExcel_Cell_DataType::TYPE_STRING);
  
        if($span < 90){
          $span += 1;
        }else{
          $span = ord("A");
          $j2 = chr($span2);
          $span2++;
        }
      }
      $column++;
    }
    // exit;
    //$fileName = iconv("utf-8", "gb2312", $fileName);
    //重命名表
    $objPHPExcel->getActiveSheet()->setTitle('Simple');
    //设置活动单指数到第一个表,所以Excel打开这是第一个表
    $objPHPExcel->setActiveSheetIndex(0);
    //将输出重定向到一个客户端web浏览器(Excel2007)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"$fileName\"");
    header('Cache-Control: max-age=0');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    //if(!empty($_GET['excel'])){
    //  $objWriter->save('php://output'); //文件通过浏览器下载
    //}else{
    $objWriter->save($fileName); //脚本方式运行，保存在当前目录
    //}
  }

  function MakeKeyouSign($values, $type='MD5')
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

	function postKeyouXmlCurl($xml, $url, $useCert = false, $second = 30)
	{		
        //初始化curl        
       	$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		
        //如果有配置代理这里就设置代理
		if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0" 
			&& WxPayConfig::CURL_PROXY_PORT != 0){
			curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
			curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
		}
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if($useCert == true){
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLCERT, dirname(__FILE__).'/lib/cert/1233793002/apiclient_cert.pem');
			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLKEY, dirname(__FILE__).'/lib/cert/1233793002/apiclient_key.pem');
		}
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
        $data = curl_exec($ch);

		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		} else { 
			$error = curl_errno($ch);
			curl_close($ch);
			throw new WxPayException("curl出错，错误码:$error");
		}
	}
