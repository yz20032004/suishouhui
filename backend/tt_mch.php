<?php
  require_once 'common.php';
  $now = date('Y-m-d H:i:s');
 
  $action = $_GET['action'];
  switch ($action) {
    case 'general_apply':
      $uid = $_GET['uid'];
      $formId = $_GET['formId'];
      $mobile = trim($_GET['mobile']);
      $merchantName = trim($_GET['name']);
      $category     = $_GET['category'];
      $province     = $_GET['province'];
      $city         = $_GET['city'];
      $district     = $_GET['district'];
      $address      = trim($_GET['address']);
      $bankAddressCode = $_GET['postcode'];
      $contact      = trim($_GET['contact']);
      $contactPhone = trim($_GET['contact_phone']);
      $latitude     = $_GET['latitude'];
      $longitude    = $_GET['longitude'];
      $logoPhotoMedia = $_GET['logo_photo_media'];
      $storeEntranceMedia = $_GET['store_entrance_media'];
      $mchType       = $_GET['mch_type'];

      $storeEntranceUrl = $redis->hget('keyou_upload_pic', $storeEntranceMedia);
      $logoUrl = $redis->hget('keyou_upload_pic', $logoPhotoMedia) ? $redis->hget('keyou_upload_pic', $logoPhotoMedia) : $storeEntranceUrl;

      $shopData = array(
                    'name' => $merchantName,
                    'address' => $address,
                  );
      $params = array('logo_url'=>$logoUrl, 'store_entrance_url'=>$storeEntranceUrl, 'formId'=>$formId, 'submit_at'=>$now, 'openid'=>'', 'shop'=>$shopData);
      $newParams = array_merge($params, $_GET);

      $key = substr(md5($uid.$now), 8, 16);
      $newParams['business_code'] = $key;
      $redis->hset('keyou_micro_apply_list_history', $key, serialize($newParams));
      $redis->hset('keyou_micro_apply_list', $key, serialize($newParams));
      echo 'success';
      break;
    case 'teyue_apply':
      $uid = $_GET['uid'];
      $formId = $_GET['formId'];
      $mobile = trim($_GET['mobile']);
      $merchantName = trim($_GET['name']);
      $category     = $_GET['category'];
      $marketingType= $_GET['marketing_type'];
      $province     = $_GET['province'];
      $city         = $_GET['city'];
      $district     = $_GET['district'];
      $address      = trim($_GET['address']);
      $bankAddressCode = $_GET['postcode'];
      $contact      = trim($_GET['contact']);
      $contactPhone = trim($_GET['contact_phone']);
      $latitude     = $_GET['latitude'];
      $longitude    = $_GET['longitude'];
      $logoPhotoMedia = $_GET['logo_photo_media'];
      $licensePhotoMedia = $_GET['license_photo_media'];
      $permitPhotoMedia = $_GET['permit_photo_media'];
      $headPhotoMedia = $_GET['head_photo_media'];
      $countryPhotoMedia = $_GET['country_photo_media'];
      $accountBank   = $_GET['account_bank'];
      $accountNumber = trim($_GET['account_number']);
      $mchType       = $_GET['mch_type'];

      $idDataFace = json_decode($redis->hget('keyou_xiaowei_idcard_face', $uid), true);
      $idCardName = $idDataFace['name'];
      $idCardNumber = $idDataFace['num'];

      $idDataBack = json_decode($redis->hget('keyou_xiaowei_idcard_back', $uid), true);
      $idCardValidTime = '["'.date('Y-m-d', strtotime($idDataBack['start_date'])).'","'.date('Y-m-d', strtotime($idDataBack['end_date'])).'"]';

      $logoUrl    = $redis->hget('keyou_upload_pic', $logoPhotoMedia);
      $licenseUrl = $redis->hget('keyou_upload_pic', $licensePhotoMedia);
      $permitUrl = $redis->hget('keyou_upload_pic', $permitPhotoMedia);
      $headUrl   = $redis->hget('keyou_upload_pic', $headPhotoMedia);
      $countryUrl= $redis->hget('keyou_upload_pic', $countryPhotoMedia);

      $shopData = array(
                    'name' => $merchantName,
                    'address' => $address,
                  );
      $params = array('account_bank'=>$accountBank, 'id_card_name'=>$idCardName, 'id_card_number'=>$idCardNumber, 'id_card_valid_time'=>$idCardValidTime, 'bank_address_code'=>$bankAddressCode, 'logo_url'=>$logoUrl, 'license_url'=>$licenseUrl, 'permit_url'=>$permitUrl, 'head_url'=>$headUrl, 'country_url'=>$countryUrl, 'formId'=>$formId, 'submit_at'=>$now, 'shop'=>$shopData, 'openid'=>'');
      $newParams = array_merge($params, $_GET);

      $key = substr(md5($uid.$now), 8, 16);
      $newParams['business_code'] = $key;
      $redis->hset('keyou_micro_apply_list_history', $key, serialize($newParams));
      $redis->hset('keyou_micro_apply_list', $key, serialize($newParams));
      echo 'success';
      break;
    case 'apply':
      $uid = $_GET['uid'];
      $formId = $_GET['formId'];
      $mobile = $_GET['mobile'];
      $merchantName = $_GET['name'];
      $category     = $_GET['category'];
      $province     = $_GET['province'];
      $city         = $_GET['city'];
      $district     = $_GET['district'];
      $bankAddressCode = $_GET['postcode'];
      $address      = $_GET['address'];
      $contact      = $_GET['contact'];
      $contactPhone = $_GET['contact_phone'];
      $latitude     = $_GET['latitude'];
      $longitude    = $_GET['longitude'];
      $logoPhotoMedia = $_GET['logo_photo_media'];
      $insidePhotoMedia = $_GET['inside_photo_media'];
      $headPhotoMedia = $_GET['head_photo_media'];
      $countryPhotoMedia = $_GET['country_photo_media'];
      $accountBank   = $_GET['account_bank'];
      $accountNumber = $_GET['account_number'];
      $mchType       = $_GET['mch_type'];

      $idDataFace = json_decode($redis->hget('keyou_xiaowei_idcard_face', $uid), true);
      $idCardName = $idDataFace['name'];
      $idCardNumber = $idDataFace['num'];

      $idDataBack = json_decode($redis->hget('keyou_xiaowei_idcard_back', $uid), true);
      $idCardValidTime = '["'.date('Y-m-d', strtotime($idDataBack['start_date'])).'","'.date('Y-m-d', strtotime($idDataBack['end_date'])).'"]';


      $logoUrl = $redis->hget('keyou_upload_pic', $logoPhotoMedia);
      $insideUrl = $redis->hget('keyou_upload_pic', $insidePhotoMedia);
      $headUrl   = $redis->hget('keyou_upload_pic', $headPhotoMedia);
      $countryUrl= $redis->hget('keyou_upload_pic', $countryPhotoMedia);

      $shopData = array(
                    'name' => $merchantName,
                    'address' => $address,
                  );
      $params = array('account_bank'=>$accountBank, 'id_card_name'=>$idCardName, 'id_card_number'=>$idCardNumber, 'id_card_valid_time'=>$idCardValidTime, 'bank_address_code'=>$bankAddressCode, 'logo_url'=>$logoUrl, 'inside_url'=>$insideUrl, 'head_url'=>$headUrl, 'country_url'=>$countryUrl, 'formId'=>$formId, 'submit_at'=>$now, 'shop'=>$shopData, 'openid'=>'');
      $newParams = array_merge($params, $_GET);

      $key = substr(md5($uid.$now), 8, 16);
      $newParams['business_code'] = $key;
      $redis->hset('keyou_micro_apply_list_history', $key, serialize($newParams));
      $redis->hset('keyou_micro_apply_list', $key, serialize($newParams));
      echo 'success';
      break;
    case 'bindPayQrCode':
      $counter = $_GET['counter'];
      $mchId   = $_GET['mch_id'];

      $sql = "SELECT id FROM app_counters WHERE mch_id = $mchId AND counter = '$counter'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "UPDATE app_counters SET counter = '$counter' WHERE mch_id = $mchId";
        $db->query($sql);

        $sql = "UPDATE mchs SET is_bind_payqrcode = 1 WHERE mch_id = $mchId";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'get_alipay_bind_qrcode':
      $mchId = $_GET['mch_id'];
      $url   = 'https://openauth.alipay.com/oauth2/appToAppAuth.htm?app_id=2018052360189175&redirect_uri=https://coupons.keyouxinxi.com/aliauthback.php&mch_id='.$mchId;

      include('./lib/phpqrcode/phpqrcode.php');
      // 二维码数据 
      // 纠错级别：L、M、Q、H 
      $errorCorrectionLevel = 'L';
      // 点的大小：1到10 
      $matrixPointSize = 8;
      // 生成的文件名 
      $filename = '/tmp/alipayqrcode_'.$mchId.'.png';
      QRcode::png($url, $filename, $errorCorrectionLevel, $matrixPointSize, 2);

      $object = 'paycode/'.date('Ymd').'/'.$mchId.'/alipaySign_'.$mchId.'.png';
      $wxUrl = putOssObject($object, file_get_contents($filename));
      unlink($filename);
      echo $wxUrl;
      break;
    case 'get_pay_counter':
      $mchId = $_GET['mch_id'];
      $shopId= $_GET['shop_id'];
      $sql   = "SELECT * FROM app_counters WHERE mch_id = $mchId";
      if ($shopId) {
        $sql .= " AND shop_id = $shopId";
      }
      $sql .= " AND counter_type = 'scan'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_father_categories':
      $appId = 'wx3d1deaee16e267a9'; //潢川沸腾水煮鱼
      $accessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/getallcategories?access_token='.$accessToken;
      $ret = json_decode(sendHttpGet($url), true);

      $cateData = array(array('id'=>'0', 'name'=>'请选择'));
      foreach ($ret['categories_list']['categories'] as $key=>$row) {
        if ('0' == $key) continue;
        if ('0' == $row['father']) {
          $cateData[] = array('id'=>$row['id'], 'name'=>$row['name']);
        }
      }
      echo json_encode($cateData);
      break;
    case 'get_child_categories':
      $father = $_GET['father'];
      $appId = 'wx3d1deaee16e267a9'; //潢川沸腾水煮鱼
      $accessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/getallcategories?access_token='.$accessToken;
      $ret = json_decode(sendHttpGet($url), true);

      $cateData = array(array('id'=>'0', 'name'=>'请选择'));
      foreach ($ret['categories_list']['categories'] as $key=>$row) {
        if ('0' == $key) continue;
        if ($father == $row['father']) {
          $cateData[] = array('id'=>$row['id'], 'name'=>$row['name']);
        }
      }
      echo json_encode($cateData);
      break;
    case 'update_fee_rate':
      $mchId = $_GET['mch_id'];
      $basicFeeRate = $_GET['basic_fee_rate']/100;
      $marketingFeeRate = $_GET['marketing_fee_rate']/100;
      $sql = "UPDATE mchs SET wechat_fee_rate = $basicFeeRate, ali_fee_rate = $basicFeeRate, marketing_fee_rate = $marketingFeeRate WHERE mch_id = $mchId";
      $db->query($sql);

      $redis->hset('keyou_merchant_basic_fee_rate_list', $mchId, $basicFeeRate);
      $redis->hset('keyou_merchant_marketing_fee_rate_list', $mchId, $marketingFeeRate);

      $sql = "SELECT * FROM mchs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'getPayQrCode':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT qrcode_url FROM app_counters WHERE mch_id = $mchId AND counter_type = 'scan'";
      $row = $db->fetch_row($sql);
      $qrCodeUrl = $row['qrcode_url'];
      $markObject = str_replace('https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/', '', $qrCodeUrl);
      $markObject = str_replace('http://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/', '', $markObject);
      $url = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/paycode/assets/paycodeback.jpg?x-oss-process=image/resize,w_750/watermark,';
      $url .= 'image_'.urlencode(base64_encode($markObject.'?x-oss-process=image/resize,P_72')).',t_90,g_nw,x_115,y_250';
      $data = array('url'=>$url);
      echo json_encode($data);
      break;
    case 'reg_mini':
      //微信开放平台快速注册小程序
      $uid = $_GET['uid'];
      $mchId   = $_GET['mch_id'];
      $merchantName = $_GET['merchant_name'];
      $name= $_GET['name'];
      $code= $_GET['code'];
      $legalName = $_GET['legal_persona_name'];
      $legalWechat = $_GET['legal_persona_wechat'];
      $cateFirst   = $_GET['cate_first'];
      $cateSecond  = $_GET['cate_second'];
      $firstClass  = $_GET['first_class'];
      $secondClass = $_GET['second_class'];
      $data = array(
            'name' => urlencode($name),
            'code' => $code,
            'code_type' => '1',
            'legal_persona_wechat' => $legalWechat,
            'legal_persona_name'   => urlencode($legalName),
            'component_phone'  => '13917486084'
          );
      $componentAccessToken = $redis->hget('keyou_suishouhui_open', 'component_access_token');
      $url = 'https://api.weixin.qq.com/cgi-bin/component/fastregisterweapp?action=create&component_access_token='.$componentAccessToken;
      $ret = httpPost($url, urldecode(json_encode($data)));
      $retData = json_decode($ret, true);
      if ('0' == $retData['errcode']) {
        $sql = "INSERT INTO apps (mch_id, nickname, principal_name, cate_first, cate_second, first_class, second_class, legal_persona_wechat, created_at) VALUES ($mchId, '$merchantName', '$name', $cateFirst, $cateSecond, '$firstClass', '$secondClass', '$legalWechat', '$now')";
        $db->query($sql);
      }
      echo $ret;
      break;
    default:
      break;
  }

  function aliyunApiRequest($url)
  {
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($curl, CURLOPT_URL, $url);
      $headers = array();
      array_push($headers, "Authorization:APPCODE " . ALIYUN_IDCARD_APPCODE);
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_FAILONERROR, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      //curl_setopt($curl, CURLOPT_HEADER, true);
      if (1 == strpos("$".$url, "https://"))
      {
          curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
          curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
      }
      $ret = curl_exec($curl);
      return json_decode($ret, true);
  }
