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
        $conditionSql .= " AND nickname LIKE '%$keyword%' OR mobile = '$keyword'";
      }
      $sql = "SELECT COUNT(id) AS total FROM members WHERE mch_id = '$mchId' ".$conditionSql;
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1)*$pageCount;
      $conditionSql .= " ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $sql = "SELECT openid, sub_openid, nickname, name, headimgurl, grade_title, mobile FROM members WHERE mch_id = '$mchId' ".$conditionSql;

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
      $grade = $_GET['grade'];
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
    default:
      break;
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
