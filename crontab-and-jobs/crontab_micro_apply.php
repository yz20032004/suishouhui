<?php
  /*
   * 执行商户申请程序
   */
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/WxPay.Config.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $applyList = $redis->hgetall('keyou_micro_apply_list'); 
  foreach ($applyList as $key=>$micro) {
    $data = unserialize($micro);
    $uid    = isset($data['uid'])?$data['uid']:0;
    $openId = isset($data['openid'])?$data['openid']:'';
    $formId = $data['formId'];
    $mchType= $data['mch_type'];
    $province = $data['province'];
    $city     = $data['city'];
    $district = $data['district'];
    $address  = $data['address'];
    $latitude = $data['latitude'];
    $longitude= $data['longitude'];
    $mobile = $data['mobile'];
    $category  = $data['category'];
    $marketingType= isset($data['marketing_type'])?$data['marketing_type']:'marketing';
    //$businessId = $data['business_id'];
    $logoPhotoMedia = isset($data['logo_photo_media'])?$data['logo_photo_media']:'';
    $insidePhotoMedia = isset($data['inside_photo_media'])?$data['inside_photo_media']:'';
    $headPhotoMedia = isset($data['head_photo_media'])?$data['head_photo_media']:'';
    $countryPhotoMedia = isset($data['country_photo_media'])?$data['country_photo_media']:'';
    $accountNumber = isset($data['account_number'])?$data['account_number']:'';
    $accountBank   = isset($data['account_bank'])?$data['account_bank']:'';
    $idCardName    = isset($data['id_card_name'])?$data['id_card_name']:$data['contact'];
    $idCardNumber  = isset($data['id_card_number'])?$data['id_card_number']:'';
    $idCardValidTime = isset($data['id_card_valid_time'])?$data['id_card_valid_time']:'';
    $shop            = $data['shop'];
    $bankAddressCode = isset($data['bank_address_code'])?$data['bank_address_code']:'';
    $logoUrl         = isset($data['logo_url'])?$data['logo_url']:'';
    $storeEntranceUrl= isset($data['store_entrance_url'])?$data['store_entrance_url']:'';
    $insideUrl       = isset($data['inside_url'])?$data['inside_url']:'';
    $licenseUrl      = isset($data['license_url'])?$data['license_url']:'';
    $permitUrl       = isset($data['permit_url'])?$data['permit_url']:'';
    $headUrl         = isset($data['head_url'])?$data['head_url']:'';
    $countryUrl      = isset($data['country_url'])?$data['country_url']:'';
    $submitAt        = $data['submit_at'];
    $now = date('Y-m-d H:i:s');
    $businessCode = $key;

    $sql = "UPDATE tuitui_users SET merchants = merchants + 1 WHERE id = $uid";
    $db->query($sql);

    if ('xiaowei' != $mchType) {
      $sql = "INSERT INTO user_mch_submit (openid, uid, mch_type, marketing_type, mobile, applyment_state, category, id_card_name, id_card_number, id_card_valid_time, account_name, account_bank, bank_address_code, account_number, store_name, store_street, merchant_shortname, id_card_copy, id_card_national, store_entrance_pic, indoor_pic, logo_url, inside_url, license_url, permit_url, head_url, country_url, business_code, formId, created_at) VALUES ('$openId', '$uid', '$mchType', '$marketingType', '$mobile', 'TO_BE_SIGNED', '$category', '$idCardName', '$idCardNumber', '$idCardValidTime', '$idCardName', '$accountBank', '$bankAddressCode', '$accountNumber', '$shop[name]', '$shop[address]', '$shop[name]', '$headPhotoMedia', '$countryPhotoMedia', '$storeEntranceUrl', '$insidePhotoMedia', '$logoUrl', '$insideUrl', '$licenseUrl', '$permitUrl', '$headUrl', '$countryUrl', '$businessCode', '$formId', '$now')";
      $db->query($sql);

      $sql = "INSERT INTO shops (openid, business_name, province, city, district, address, longitude, latitude, telephone, logo_url, updated_at, created_at) VALUES ('$openId', '$shop[name]', '$province', '$city', '$district', '$address', '$longitude', '$latitude', '$mobile', '$logoUrl', '$now', '$now')";
      $db->query($sql);

      $sql = "UPDATE users SET name='$idCardName', merchant_name = '$shop[name]' WHERE mobile = '$mobile'";
      $db->query($sql);

      $redis->hdel('keyou_micro_apply_list', $key);
      echo 'APPLY '.$shop['name'].' END '.date('Y-m-d H:i:s').PHP_EOL;
      continue;
    }

    $data = array(
                  'version' => '3.0',
                  'cert_sn' => SERIAL_NO,
                  'mch_id'  => MCH_ID,
                  'nonce_str' => getNonceStr(),
                  'sign_type' => 'HMAC-SHA256',
                  'business_code' => $businessCode,
                  'id_card_copy'  => $headPhotoMedia,
                  'id_card_national' => $countryPhotoMedia,
                  'id_card_name'     => getEncrypt($idCardName),
                  'id_card_number'   => getEncrypt($idCardNumber),
                  'id_card_valid_time' => $idCardValidTime,
                  'account_name'       => getEncrypt($idCardName),
                  'account_bank'       => $accountBank,
                  'bank_address_code'  => $bankAddressCode,
                  'account_number'     => getEncrypt($accountNumber),
                  'store_name'         => $shop['name'],
                  'store_street'       => $shop['address'],
                  'merchant_shortname' => $shop['name'],
                  'service_phone'      => $mobile,
                  'store_entrance_pic' => $logoPhotoMedia,
                  'indoor_pic'         => $insidePhotoMedia,
                  'product_desc'       => $category,
                  'rate'               => '0.38%',
                  'store_address_code' => $bankAddressCode,
                  'contact'            => getEncrypt($idCardName),
                  'contact_phone'      => getEncrypt($mobile)
                );
    $url = 'https://api.mch.weixin.qq.com/applyment/micro/submit';
    $sign = MakeSign($data, 'HMAC-SHA256');
    $data['sign'] = $sign;
    $xml = ToXml($data);
    $response = postXmlCurl($xml, $url, true, 6);
    $retData = FromXml($response);
    if ($retData['return_code'] == 'SUCCESS' && $retData['return_code'] == 'SUCCESS' && isset($retData['applyment_id'])) {
      $applymentId = $retData['applyment_id'];
      $sql = "INSERT INTO user_mch_submit (openid, uid, mch_type, mobile, category, id_card_name, id_card_number, id_card_valid_time, account_name, account_bank, bank_address_code, account_number, store_name, store_street, merchant_shortname, id_card_copy, id_card_national, store_entrance_pic, indoor_pic, logo_url, inside_url, head_url, country_url, applyment_id, business_code, formId, created_at) VALUES ('$openId', '$uid', '$mchType', '$mobile', '$category', '$idCardName', '$idCardNumber', '$idCardValidTime', '$idCardName', '$accountBank', '$bankAddressCode', '$accountNumber', '$shop[name]', '$shop[address]', '$shop[name]', '$headPhotoMedia', '$countryPhotoMedia', '$logoPhotoMedia', '$insidePhotoMedia', '$logoUrl', '$insideUrl', '$headUrl', '$countryUrl', '$applymentId','$businessCode', '$formId', '$now')";
      $db->query($sql);

      $sql = "INSERT INTO shops (openid, business_name, province, city, district, address, longitude, latitude, telephone, logo_url, updated_at, created_at) VALUES ('$openId', '$shop[name]', '$province', '$city', '$district', '$address', '$longitude', '$latitude', '$mobile', '$logoUrl', '$now', '$now')";
      $db->query($sql);

      $sql = "UPDATE users SET name='$idCardName', merchant_name = '$shop[name]' WHERE mobile = '$mobile'";
      $db->query($sql);

      $redis->hdel('keyou_micro_apply_list', $key);
    }
    echo 'APPLY '.$shop['name'].' END '.date('Y-m-d H:i:s').PHP_EOL;
  }

  function getEncrypt($str){
     $url = 'https://suishouh.applinzi.com/encrypt.php';
     $data = array('string'=>$str);
     $sign = httpSSLPost($url, $data);
     return $sign;
  }
