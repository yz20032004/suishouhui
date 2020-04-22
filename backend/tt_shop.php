<?php
  require_once 'common.php';
  require_once "lib/WxPay.Api.php";

  $now = date('Y-m-d H:i:s');
  $action = $_GET['action'];

  switch ($action) {
    case 'get_detail':
      $mchId = $_GET['mch_id'];
      $shopId= isset($_GET['shop_id'])?$_GET['shop_id']:0;
      $sql = "SELECT * FROM shops WHERE mch_id = $mchId";
      if ($shopId) {
        $sql .= " AND id = $shopId";
      }
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'add':
      $mchId = $_GET['mch_id'];
      $branchName = trim($_GET['branch_name']);
      $province     = $_GET['province'];
      $city         = $_GET['city'];
      $district     = $_GET['district'];
      $address      = trim($_GET['address']);
      $latitude     = $_GET['latitude'];
      $longitude    = $_GET['longitude'];
      $storeEntranceMedia = $_GET['store_entrance_media'];
      $storeEntranceUrl   = $redis->hget('keyou_upload_pic', $storeEntranceMedia);

      $sql = "SELECT id FROM shops WHERE mch_id = $mchId AND branch_name = '$branchName'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
        exit();
      }
      $sql = "SELECT merchant_shortname, logo_url FROM user_mch_submit WHERE sub_mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $businessName = $row['merchant_shortname'];
      $logoUrl      = $row['logo_url'];
      $sql = "INSERT INTO shops (mch_id, business_name, branch_name, province, city, district, address, latitude, longitude, logo_url, store_entrance_url, created_at) VALUES ($mchId, '$businessName', '$branchName', '$province', '$city', '$district', '$address', '$latitude', '$longitude', '$logoUrl', '$storeEntranceUrl', '$now')";
      $db->query($sql);

      $shopId = $db->get_insert_id();

      $miniAccessToken = $redis->hget('keyou_mini', 'access_token');//随手惠生活ACCESSTOKEN
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/index/get_membercard?key=key&mch_id='.$mchId.'&get_point=0&shop_id='.$shopId);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('membercard_'.$mchId.time()), 8, 16);
      $object   = 'xiaowei/'.date('Ymd').'/'.$filename.'.png';
      $cardUrl  = putOssObject($object, $buffer);

      $sql = "UPDATE shops SET card_url = '$cardUrl' WHERE id = $shopId";
      $db->query($sql);

      $sql = "UPDATE mchs SET shops = shops + 1 WHERE mch_id = $mchId";
      $db->query($sql);

      $sql = "INSERT INTO app_counters (mch_id, shop_id, merchant_name, branch_name, counter_type, name, created_at) VALUES ($mchId, $shopId, '$businessName', '$branchName', 'scan', '扫码买单', '$now')";
      $db->query($sql);

      $counter = getPayCounter();
      $sql = "INSERT INTO app_counters (counter, mch_id, shop_id, merchant_name, branch_name, counter_type, name, created_at) VALUES ($counter, $mchId, $shopId, '$businessName', '$branchName', 'self', '自助买单', '$now')";
      $db->query($sql);

      echo 'success';
      break;
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, business_name, branch_name, card_url, created_at FROM shops WHERE mch_id = $mchId ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'upload_photo':
      $uid = $_POST['uid'];
      $type   = $_POST['type'];
      $name   = $_POST['name'];
      $ret = explode('.', $_FILES['file']['name']);

      $extension = end($ret);
      $tmpFile = '/tmp/xiaowei_'.$uid.time().'.'.$extension;
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);
      $md5 = md5_file($tmpFile);

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

      $mediaId = $data['media_id'];
      $redis->hset('keyou_upload_pic', $mediaId, $picUrl);
      unlink($tmpFile);
      echo json_encode($data);
      break;
    case 'upload_id_photo':
      $uid = $_POST['uid'];
      $type   = $_POST['type'];
      $side   = $_POST['side'];
      $ret = explode('.', $_FILES['file']['name']);

      $extension = end($ret);
      $tmpFile = '/tmp/xiaowei_id_'.$uid.'.'.$extension;
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);
      $md5 = md5_file($tmpFile);

      $object = 'merchant/'.date('Ymd').'/'.md5($uid.'_'.time()).'.'.$extension; 
      $photoUrl = putOssObject($object, file_get_contents($tmpFile));

      $ret = getIdCard($tmpFile, $side);
      if ('200' != $ret['http_code']) {
        echo json_encode($ret);
        return;
      } else {
        $redis->hset('keyou_xiaowei_idcard_'.$side, $uid, $ret['msg']);
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
      unlink($tmpFile);
      echo json_encode($data);
      break;
    case 'getLicenseImage':
      //获取营业执照数据
      $type   = $_POST['type'];
      $name   = $_POST['name'];
      $mchId  = $_POST['mch_id'];
      $ret = explode('.', $_FILES['file']['name']);

      $extension = end($ret);
      $tmpFile = '/tmp/'.$mchId.'.jpg';
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);

      $miniAccessToken = $redis->hget('keyou_mini', 'tuitui_access_token');
      $cmd = "curl -F 'img=@$tmpFile' 'https://api.weixin.qq.com/cv/ocr/bizlicense?access_token=$miniAccessToken'";
      exec($cmd, $output);
      unlink($tmpFile);
      echo $output[0];
      break;
    case 'submit':
      $uid = $_GET['uid'];
      $formId = $_GET['formId'];
      $mobile = $_GET['mobile'];
      $businessId = $_GET['business_id'];
      $logoPhotoMedia = $_GET['logo_photo_media'];
      $insidePhotoMedia = $_GET['inside_photo_media'];
      $headPhotoMedia = $_GET['head_photo_media'];
      $countryPhotoMedia = $_GET['country_photo_media'];
      $accountNumber = $_GET['account_number'];

      $url = 'https://api17.aliyun.venuscn.com/bank-card/query?number='.$accountNumber;
      $data = aliyunApiRequest($url);
      if ('200' != $data['ret']) {
        echo json_encode($data);
        return;
      }
      $accountBank = $data['data']['bankname'];
  
      $idDataFace = json_decode($redis->hget('keyou_xiaowei_idcard_face', $uid), true);
      $idCardName = $idDataFace['name'];
      $idCardNumber = $idDataFace['num'];

      $idDataBack = json_decode($redis->hget('keyou_xiaowei_idcard_back', $uid), true);
      $idCardValidTime = '["'.date('Y-m-d', strtotime($idDataBack['start_date'])).'","'.date('Y-m-d', strtotime($idDataBack['end_date'])).'"]';
      $shopData = json_decode($redis->hget('keyou_shops', md5($uid)), true);
      $shop     = $shopData[$businessId];

      $url = 'https://ali-city.showapi.com/areaName?areaName='.$shop['city'].'&level=2';
      $data = aliyunApiRequest($url);
      $bankAddressCode = substr($data['showapi_res_body']['data'][0]['parentId'], 0, 6);

      $logoUrl = $redis->hget('keyou_upload_pic', $logoPhotoMedia);
      $insideUrl = $redis->hget('keyou_upload_pic', $insidePhotoMedia);
      $headUrl   = $redis->hget('keyou_upload_pic', $headPhotoMedia);
      $countryUrl= $redis->hget('keyou_upload_pic', $countryPhotoMedia);
      $params = array('account_bank'=>$accountBank, 'id_card_name'=>$idCardName, 'id_card_number'=>$idCardNumber, 'id_card_valid_time'=>$idCardValidTime, 'shop'=>$shop, 'bank_address_code'=>$bankAddressCode, 'logo_url'=>$logoUrl, 'inside_url'=>$insideUrl, 'head_url'=>$headUrl, 'country_url'=>$countryUrl, 'formId'=>$formId, 'submit_at'=>$now);
      $newParams = array_merge($params, $_GET);

      $key = substr(md5($uid.$now), 8, 16);
      $newParams['business_code'] = $key;
      $redis->hset('keyou_micro_apply_list', $key, serialize($newParams));
      echo 'success';
      break;
    case 'bindPayQrCode':
      $counter = $_GET['counter'];
      $mchId   = $_GET['mch_id'];
      $shopId  = $_GET['shop_id'];

      $sql = "SELECT id FROM app_counters WHERE mch_id = $mchId AND shop_id = $shopId AND counter = '$counter'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "UPDATE app_counters SET counter = '$counter' WHERE mch_id = $mchId AND shop_id = $shopId";
        $db->query($sql);

        $sql = "UPDATE mchs SET is_bind_payqrcode = 1 WHERE mch_id = $mchId";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'update_info':
      $mchId = $_GET['mch_id'];
      $address = $_GET['address'];
      $latitude= $_GET['latitude'];
      $longitude= $_GET['longitude'];
      $telephone= $_GET['telephone'];
      $openTime = $_GET['open_time'];
      $sql = "UPDATE shops SET address = '$address', latitude = '$latitude', longitude = '$longitude', telephone='$telephone', open_time = '$openTime' WHERE mch_id = $mchId";
      echo $sql.PHP_EOL;
      $db->query($sql);
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

  function getPayCounter()
  {
    global $db;
    $counter  = rand(10000000, 99999999);
    $sql = "SELECT id FROM app_counters WHERE counter = $counter";
    $row = $db->fetch_row($sql);
    if ($row['id']) {
      getPayCounter();
    } else {
      return $counter;
    }
  }
