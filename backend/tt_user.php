<?php
  //小程序用户类操作
  require_once 'common.php';
  require_once dirname(__FILE__).'/lib/WxPay.Api.php';
  require_once dirname(__FILE__).'/unit/WxPay.JsApiPay.php';
  require_once dirname(__FILE__).'/unit/log.php';

  $action  = $_GET['action'];
  $now = date('Y-m-d H:i:s');

  switch ($action) {
    case 'login':
      $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.SSHTT_APP_ID.'&secret='.SSHTT_APP_SECRET.'&grant_type=authorization_code&js_code='.$_GET['js_code'];
      $ret    = httpPost($url);
      echo $ret;
      break;
    case 'get_detail':
      $openId = $_GET['openid'];
      $sql = "SELECT * FROM tuitui_users WHERE openid = '$openId' AND status = 1";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'getphonenumber':
      require_once "lib/wxBizDataCrypt.php";
      $openId        = $_GET['openid'];
      $encryptedData = $_GET['encryptedData'];
      $iv            = $_GET['iv'];
      $sessionKey    = $_GET['session_key'];

      $pc = new WXBizDataCrypt(SSHTT_APP_ID, $sessionKey);
      $errCode = $pc->decryptData($encryptedData, $iv, $data);
      $ret = json_decode($data, true);
      $phoneNumber = $ret['phoneNumber'];

      $sql = "SELECT id FROM users WHERE openid = '$openId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE users SET mobile = '$phoneNumber' WHERE id = $row[id]";
      } else {
        $sql = "INSERT INTO users (openid, mobile, is_admin, created_at) VALUES ('$openId', '$phoneNumber', 1, '$now')";
      }
      $db->query($sql);

      echo $data;
      break;
    case 'update_user_info':
      require_once "lib/wxBizDataCrypt.php";
      $sessionKey    = $_GET['session_key'];
      $encryptedData = $_GET['encryptedData'];
      $iv            = $_GET['iv'];
      $openId = $_GET['openid'];

      $pc = new WXBizDataCrypt(SSHTT_APP_ID, $sessionKey);
      $errCode = $pc->decryptData($encryptedData, $iv, $data);
      if ('0' == $errCode) {
        $ret = json_decode($data, true);
        $avatarUrl = $ret['avatarUrl'];
        $city      = $ret['city'];
        $province  = $ret['province'];
        $nickName  = $ret['nickName'];
        $gender    = $ret['gender'];
        $unionId   = $ret['unionId'];

        $sql = "INSERT INTO tuitui_users (openid, unionid, nickname, headimgurl, province, city, gender, created_at) VALUES ('$openId', '$unionId', '$nickName', '$avatarUrl', '$province', '$city', $gender, '$now')";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'get_validate_code':
      $mobile = $_GET['mobile'];

      $validateCode = rand(1000, 9999);
      $redis->set('tt_validate_code_'.$mobile, $validateCode);
      $redis->expire('tt_validate_code_'.$mobile, 600);

      $data = array(
                'mobile' => $mobile,
                'template_code' => SMS_TEMPLATE_VALIDATE_CODE,
                'sms_params'  => json_encode(array('code'=>(string)$validateCode)),
              );
      $redis->rpush('keyou_sms_job_list', serialize($data));
      echo 'success';
      break;
    case 'register':
      $name   = $_GET['name'];
      $mobile = $_GET['mobile'];
      $code   = $_GET['code'];
      $openId = $_GET['openid'];

      $validateCode = $redis->get('tt_validate_code_'.$mobile);
      if ($code != $validateCode) {
        echo 'fail_code';
      } else {
        $sql = "UPDATE tuitui_users SET name = '$name', mobile = '$mobile', status = 1 WHERE openid = '$openId'";
        $db->query($sql);

        $sql = "SELECT * FROM tuitui_users WHERE openid = '$openId'";
        $row = $db->fetch_row($sql);
        echo json_encode($row);
      }
      break;
    case 'register_fromleader':
      $leaderId = $_GET['leader_id'];
      $name   = $_GET['name'];
      $mobile = $_GET['mobile'];
      $code   = $_GET['code'];
      $openId = $_GET['openid'];

      $validateCode = $redis->get('tt_validate_code_'.$mobile);
      if ($code != $validateCode) {
        echo 'fail_code';
      } else {
        $sql = "UPDATE tuitui_users SET leader_id = '$leaderId', name = '$name', mobile = '$mobile', agent_type = 'marketing', status = 1 WHERE openid = '$openId'";
        $db->query($sql);

        $sql = "SELECT * FROM tuitui_users WHERE openid = '$openId'";
        $row = $db->fetch_row($sql);
        echo json_encode($row);
      }
      break;
    case 'get_mch_list':
      $mchType   = $_GET['mch_type'];
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $uid    = $_GET['uid'];
      $sql = "SELECT COUNT(id) AS total FROM user_mch_submit WHERE applyment_state != 'REJECTED'";
      if ('2' != $uid) {
        $sql .=" AND uid = $uid";
      }
      if ('xiaowei' == $mchType) {
        $sql .=" AND mch_type = 'xiaowei'";
      } else if ('teyue' == $mchType) {
        $sql .=" AND mch_type = 'getihu'";
      } else {
        $sql .=" AND mch_type = 'general'";
      }
      $row = $db->fetch_row($sql);
      $total = $row['total'];

      $pageTotal = ceil($total/$pageCount);
      $startRow = ($page-1) * $pageCount;

      $sql = "SELECT merchant_shortname AS merchant_name, sub_mch_id AS mch_id, applyment_state, created_at FROM user_mch_submit WHERE applyment_state != 'REJECTED'";
      if ('2' != $uid) {
        $sql .= " AND uid = $uid";
      }
      if ('xiaowei' == $mchType) {
        $sql .=" AND mch_type = 'xiaowei'";
      } else if ('teyue' == $mchType) {
        $sql .=" AND mch_type = 'getihu'";
      } else {
        $sql .=" AND mch_type = 'general'";
      }

      $sql .=" ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);

      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'get_team_list':
      $uid = $_GET['uid'];
      $sql = "SELECT id, name, headimgurl FROM tuitui_users WHERE leader_id = $uid ORDER BY id DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_merchant_list':
      $uid = $_GET['uid'];
      $merchantData = array();
      $sql = "SELECT sub_mch_id, merchant_shortname FROM user_mch_submit WHERE uid = $uid";
      $data = $db->fetch_array($sql);
      foreach ($data as $row) {
        $merchantData[] = array('mch_id'=>$row['sub_mch_id'], 'name'=>$row['merchant_shortname']);
      }
      if ($merchantData) {
        $dateAt = date('Y-m-d', strtotime('-1 days'));
        $tmpMerchantData = array();
        $zeroMerchantData = array();
        foreach ($merchantData as $mchId=>&$row) {
          $sql = "SELECT trade_amount FROM wechat_pays_today WHERE mch_id = $row[mch_id] AND date_at = '$dateAt'";
          $data = $db->fetch_row($sql);
          if ($data) {
            $row['trade_amount'] = $data['trade_amount'];
          } else {
            $row['trade_amount'] = 0;
            $zeroMerchantData[] = $row;
            continue;
          }
          $tmpMerchantData[$row['trade_amount']] = $row;
        }
        $newMerchantData = array();
        krsort($tmpMerchantData);
        $merchantData = array_merge($tmpMerchantData, $zeroMerchantData);
        foreach ($merchantData as $row) {
          $newMerchantData[] = $row;
        }
        echo json_encode($newMerchantData);
      } else {
        echo false;
      }
      break;
    case 'get_cash_out_history':
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];
      $uid       = $_GET['uid'];

      $sql = "SELECT COUNT(id) AS total FROM tuitui_cash_out_history WHERE userid = $uid AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT cash_out,created_at FROM tuitui_cash_out_history WHERE userid = $uid  AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      $sql .= " ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);

      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'cashout':
      $userId   = $_GET['uid'];
      $openId   = $_GET['openid'];
      $username = $_GET['username'];
      $cashout  = $_GET['cashout'];

      $partnerTradeNo = getOutTradeNo();
      $ip =  $_SERVER['REMOTE_ADDR'];
      $data = array(
                  'mch_appid'   => SSHTT_APP_ID,
                  'mchid'  => KEYOU_MCHID,
                  'nonce_str'  => WxPayApi::getNonceStr(),
                  'partner_trade_no' => $partnerTradeNo,
                  'openid'       => $openId,
                  'check_name'   => 'FORCE_CHECK',
                  're_user_name' => $username,
                  'amount'       => $cashout * 100,
                  'desc'         => '推手收入提现',
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
         $sql = "INSERT INTO tuitui_cash_out_history (userid, openid, re_user_name, partner_trade_no, cash_out, spbill_create_ip, created_at) VALUES ($userId, '$openId', '$username', '$partnerTradeNo', $cashout, '$ip', '$now')";
         $db->query($sql);

         $sql = "UPDATE tuitui_users SET wait_cash_out = wait_cash_out - $cashout WHERE id = $userId";
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
