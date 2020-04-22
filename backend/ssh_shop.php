<?php
  require_once 'common.php';
  require_once "lib/WxPay.Api.php";

  $now = date('Y-m-d H:i:s');
  $action = $_GET['action'];

  switch ($action) {
    case 'get_detail':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT * FROM shops WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_city':
      $latitude = $_GET['latitude'];
      $longitude = $_GET['longitude'];

      if (!$latitude || !$longitude) {
        exit();
      }

      $url = 'http://apis.map.qq.com/ws/geocoder/v1/?key='.TENSENT_LBS_KEY.'&location='.$latitude.','.$longitude;
      $ret = file_get_contents($url);
      $data = json_decode($ret, true);

      $component = $data['result']['address_component'];
      //上海市=》上海，点评API城市名没有市
      $result = array('province'=>$component['province'], 'city'=>substr($component['city'], 0, -3), 'district'=>$component['district']);
      echo json_encode($result);
      break;
    case 'search_list':
      $url = 'https://openapi.dianping.com/router/oauth/token';
      $params = 'app_key='.DIANPING_APP_KEY.'&app_secret='.DIANPING_APP_SECRET.'&grant_type=authorize_platform';
      $ret = sendHttpGet($url.'?'.$params);
      $data = json_decode($ret, true);
      $session = $data['access_token'];

      $openId = $_GET['openid'];
      $city = substr($_GET['city'], 0, -3); //去掉“市”，点评API没有市
      $district = $_GET['district'];
      $keyword = $_GET['keyword'];
      
      $url = 'https://openapi.dianping.com/router/poi/querypoi';
      $params = array(
                    'app_key'  => DIANPING_APP_KEY,
                    'timestamp'=> date('Y-m-d H:i:s'),
                    'session'  => $session,
                    'format'   => 'json',
                    'v'        => '1',
                    'sign_method'=>'MD5',
                    'deviceId' => 'aa',
                    'city' => $city,
                    'region' => $district,
                    'keyword' => $keyword,
                    'category' => '美食'
                    );
       ksort($params);
       //连接待加密的字符串
       $codes = DIANPING_APP_SECRET;
       //请求的URL参数
       $queryString = '';
       while (list($key, $val) = each($params))
       {
          $codes .=($key.$val);
          if ('timestamp' == $key) {
            $queryString .=('&'.$key.'='.urlencode($val));
          } else {
            $queryString .=('&'.$key.'='.$val);
          }
       }             
       $codes .= DIANPING_APP_SECRET;
       $sign = strtolower(md5($codes));
       $url .= '?sign='.$sign.$queryString;
       $r = sendHttpGet($url);
       $ret = json_decode($r, true);
       if ($ret['data']['records']) {
         $data = array();
         foreach ($ret['data']['records'] as $row) {
            $data[$row['business_id']] = array(
                            'name'       =>$row['name'],
                            'branch_name'=>$row['branch_name'],
                            'business_id'=>$row['business_id'],
                            'openshopid' =>$row['openshopid'],
                            'province'   =>$row['province'],
                            'city'       =>$row['city'],
                            'district'   =>$row['district'],
                            'latitude'   =>$row['latitude'],
                            'longitude'  =>$row['longitude'],
                            'address'    =>$row['address'],
                            'telephone'  =>$row['telephone'],
                            'introduction'=>$row['introduction'],
                            'business_hour'=>$row['business_hour'],
                            'traffic'      =>$row['traffic'],
                            'photo_urls'   =>$row['photo_urls'],
                            'categories'   =>end($row['categories'])
                            );
          }
          $key = md5($openId);
          $redis->hset('keyou_shops', $key, json_encode($data));
          echo $key;
       } else {
          echo 'fail';
       }
       break;
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, business_name, branch_name, card_url, created_at FROM shops WHERE mch_id = $mchId ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'upload_photo':
      $openId = $_POST['openid'];
      $type   = $_POST['type'];
      $name   = $_POST['name'];
      $ret = explode('.', $_FILES['file']['name']);

      $extension = end($ret);
      $tmpFile = '/tmp/xiaowei_'.$openId.'.'.$extension;
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);

      $media = '@'.$tmpFile;
      //上传到微信
      $wxAccessToken = $redis->hget('keyouxinxi','wx_access_token');
      exec('curl -F media='.$media.' https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$wxAccessToken, $result);
      $retData = json_decode($result[0], true);
      $picUrl = $retData['url'];

      //商户自助申请
      $mediaId = getNonceStr();
      $redis->hset('keyou_upload_pic', $mediaId, $picUrl);
      $data = array('media_id'=>$mediaId);
      echo json_encode($data);
      break;
    case 'upload_id_photo':
      $openId = $_POST['openid'];
      $type   = $_POST['type'];
      $side   = $_POST['side'];
      $ret = explode('.', $_FILES['file']['name']);

      $extension = end($ret);
      $tmpFile = '/tmp/xiaowei_id_'.$openId.'.'.$extension;
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);
      $md5 = md5_file($tmpFile);

      $object = 'merchant/'.date('Ymd').'/'.md5($openId.'_'.time()).'.'.$extension; 
      $photoUrl = putOssObject($object, file_get_contents($tmpFile));

      $ret = getIdCard($tmpFile, $side);
      if ('200' != $ret['http_code']) {
        echo json_encode($ret);
        return;
      } else {
        $redis->hset('keyou_xiaowei_idcard_'.$side, $openId, $ret['msg']);
      }

      $url = 'https://api.mch.weixin.qq.com/secapi/mch/uploadmedia';
      $data = array('mch_id'=>MCHID, 'media_hash'=>$md5);
      $sign = MakeSign($data);
      $data['sign'] = $sign;
      $media = '@'.$tmpFile;

      //上传到微信
      $wxAccessToken = $redis->hget('keyouxinxi','wx_access_token');
      exec('curl -F media='.$media.' https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$wxAccessToken, $result);
      $retData = json_decode($result[0], true);
      $picUrl = $retData['url'];

      exec('curl --cert '.WxPayConfig::SSLCERT_PATH.' --key '.WxPayConfig::SSLKEY_PATH.' -F "mch_id='.MCHID.'" -F "media_hash='.$md5.'" -F "sign='.$sign.'" -F "media='.$media.'" https://api.mch.weixin.qq.com/secapi/mch/uploadmedia', $out);
      $data = FromXml(implode("\n", $out));
      $data['http_code'] = '200';

      $mediaId = $data['media_id'];
      $redis->hset('keyou_upload_pic', $mediaId, $picUrl);

      echo json_encode($data);
      break;
    case 'selfsubmit':
      $openId = $_GET['openid'];
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
      $storeEntranceMedia = $_GET['inside_photo_media'];
      $mchType       = $_GET['mch_type'];

      $logoUrl    = $redis->hget('keyou_upload_pic', $logoPhotoMedia);
      $storeEntranceUrl = $redis->hget('keyou_upload_pic', $storeEntranceMedia);

      $shopData = array(
                    'name' => $merchantName,
                    'address' => $address,
                  );
      $params = array('logo_url'=>$logoUrl, 'store_entrance_url'=>$storeEntranceUrl, 'submit_at'=>$now, 'openid'=>$openId, 'shop'=>$shopData);
      $newParams = array_merge($params, $_GET);

      $sql = "SELECT id FROM user_mch_submit WHERE openid = '$openId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
        exit();
      }
      $mchId = rand(80000000, 99999999);
      $sql = "INSERT INTO user_mch_submit (openid, mch_type, mobile, applyment_state, category, id_card_name, store_name, store_street, merchant_shortname, service_phone, store_entrance_pic, indoor_pic, logo_url, inside_url, sub_mch_id, created_at) VALUES ('$openId', '$mchType', '$contactPhone', 'FINISH', '$category', '$contact', '$merchantName', '$address', '$merchantName', '$contactPhone', '$logoPhotoMedia', '$storeEntranceMedia', '$logoUrl', '$storeEntranceUrl', $mchId, '$now')";
      $db->query($sql);

      $sql = "INSERT INTO shops (openid, mch_id, business_name, categories, province, city, district, address, longitude, latitude, telephone, logo_url, updated_at, created_at) VALUES ('$openId', $mchId, '$merchantName', '$category', '$province', '$city', '$district', '$address', '$longitude', '$latitude', '$contactPhone', '$logoUrl', '$now', '$now')";
      $db->query($sql);

      $sql = "INSERT INTO users (mch_id, merchant_name, openid, name, mobile, is_admin, sms_total, created_at) VALUES ($mchId, '$merchantName', '$openId', '$contact', '$contactPhone', 1, 20, '$now')";
      $db->query($sql);

      $appId = SUISHOUHUI_APP_ID;
      $sql = "INSERT INTO mchs(mch_id, appid, merchant_name, mch_type, marketing_type, created_at) VALUES ($mchId, '$appId', '$merchantName', 'general', 'marketing', '$now')";
      $db->query($sql);

      $pointData = array('mch_id'=>$mchId, 'award_need_consume'=>1, 'can_cash'=>false,'exchange_need_points'=>0, 'recharge_point_speed'=>0);
      $redis->hset('keyou_mch_point_rules', $mchId, serialize($pointData));

      $key = substr(md5($openId.$now), 8, 16);
      $redis->hset('keyou_micro_apply_list_history', $key, serialize($newParams));

      $data = array(
                'job'    => 'init_general_merchant',
                'mch_id' => $mchId,
                'wx_access_token' => $redis->hget('keyouxinxi', 'wx_access_token'),
                'mini_access_token'=> $redis->hget('keyou_mini', 'access_token')
              );
      $redis->rpush('keyou_mch_job_list', serialize($data));
      echo 'success';
      break;
    case 'submit':
      $openId = $_GET['openid'];
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

      $key = substr(md5($openId.$now), 8, 16);
      $newParams['business_code'] = $key;
      $redis->hset('keyou_micro_apply_list_history', $key, serialize($newParams));
      $redis->hset('keyou_micro_apply_list', $key, serialize($newParams));
      echo 'success';
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

  function getIdCard($file, $side)
  {
   $url = "https://dm-51.data.aliyun.com/rest/160601/ocr/ocr_idcard.json";
    $appcode = ALIYUN_IDCARD_APPCODE;
    //如果没有configure字段，config设为空
    $config = array(
        "side" => $side 
    );

    if($fp = fopen($file, "rb", 0)) { 
        $binary = fread($fp, filesize($file)); // 文件读取
        fclose($fp); 
        $base64 = base64_encode($binary); // 转码
    }
    $headers = array();
    array_push($headers, "Authorization:APPCODE " . $appcode);
    //根据API的要求，定义相对应的Content-Type
    array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
    $querys = "";
    $request = array(
        "image" => "$base64"
    );
    if(count($config) > 0){
        $request["configure"] = json_encode($config);
    }
    $body = json_encode($request);
    $method = "POST";
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_FAILONERROR, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, true);
    if (1 == strpos("$".$url, "https://"))
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    }
    curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    $result = curl_exec($curl);
    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
    $rheader = substr($result, 0, $header_size); 
    $rbody = substr($result, $header_size);
    $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
    
    $data = array('http_code'=>$httpCode, 'msg'=>$rbody);
    return $data;
  }
