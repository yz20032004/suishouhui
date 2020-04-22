<?php
  //商户商城管理
  require_once 'common.php';
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
  $action  = $_GET['action'];

  switch ($action) {
    case 'get_config':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT * FROM mch_mall_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'update_config':
      $mchId = $_GET['mch_id'];
      $deliveryCost = $_GET['delivery_cost'];
      $deliveryFreeAtleast = $_GET['delivery_free_atleast'] ? $_GET['delivery_free_atleast'] : 0;
      $canRecharge = $_GET['can_recharge'];
      $deliveryTip = $_GET['delivery_tip'];
      $canSelf = 0;
      $sql = "SELECT id FROM mch_mall_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE mch_mall_configs SET can_self = $canSelf, can_recharge = $canRecharge, delivery_cost = $deliveryCost, delivery_free_atleast = $deliveryFreeAtleast, delivery_tip = '$deliveryTip'  WHERE mch_id = $mchId";
      } else {
        $sql = "INSERT INTO mch_mall_configs (mch_id, can_self, can_recharge, delivery_cost, delivery_free_atleast, delivery_tip, created_at) VALUES ($mchId, $canSelf, $canRecharge, $deliveryCost, $deliveryFreeAtleast, '$deliveryTip', '$now')";
      }
      $db->query($sql);
      if (isset($_GET['jiabo_device_no'])) {
        $sql = "UPDATE mch_mall_configs SET jiabo_device_no = '$_GET[jiabo_device_no]' WHERE mch_id = $mchId";
        $db->query($sql);
      }
      echo 'success';
      break;
    case 'open':
      $id  = $_GET['id'];
      $sql = "UPDATE mch_mall_products SET is_selling = 1 WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'stop':
      $id  = $_GET['id'];
      $sql = "UPDATE mch_mall_products SET is_selling = 0 WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_qrcode':
      $id = $_GET['id'];
      $sql = "SELECT mch_id, qrcode_url FROM mch_mall_products WHERE id = $id";
      $row = $db->fetch_row($sql);
      if ($row['qrcode_url']) {
        echo json_encode($row);
      } else {
        $sql = "SELECT appid FROM mchs WHERE mch_id = $row[mch_id]";
        $ret = $db->fetch_row($sql);
        if (SUISHOUHUI_APP_ID == $ret['appid']) {
          $miniAccessToken = $redis->hget('keyou_mini', 'access_token');//随手惠生活ACCESSTOKEN
        } else {
          $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $ret['appid']);
        } 
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
        $data = array('path'=>'pages/mall/detail?id='.$id);
        $buffer = sendHttpRequest($url, $data);

        $filename = substr(md5('mall_'.$id.time()), 8, 16);
        $object = 'mall/'.date('Ymd').'/'.$filename.'.png';
        $qrcodeUrl = putOssObject($object, $buffer);

        $sql = "UPDATE mch_mall_products SET qrcode_url = '$qrcodeUrl' WHERE id = $id";
        $db->query($sql);
        $row['qrcode_url'] = $qrcodeUrl;
        echo json_encode($row);
      }
      break;
    case 'update_express':
      $mchId      = $_GET['mch_id'];
      $outTradeNo = $_GET['out_trade_no'];
      $express    = $_GET['express'];
      $expressNo  = $_GET['express_no'];
      $sql = "UPDATE member_mall_orders SET delivery_at = '$now', delivery_type = '$express', delivery_no = '$expressNo' WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      echo $sql.PHP_EOL;
      $db->query($sql);

      $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $merchantName = $row['merchant_name'];
      $sql = "SELECT contact_mobile FROM member_mall_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);
      $mobile = $row['contact_mobile'];
      $smsParam = json_encode(array('merchant'=>$merchantName, 'express_no'=>$express.$expressNo));
      $smsData = array('template_code'=>SMS_TEMPLATE_CODE_MALL_DELIVER, 'sms_params'=>$smsParam, 'mobile'=>$mobile);
      $redis->rpush('keyou_sms_job_list', serialize($smsData));
      echo 'success';
      break;
    case 'upload_photo':
      $ret = explode('.', $_FILES['file']['name']);
      $extension = end($ret);
      $tmpFile = '/tmp/cardpic_'.rand(1000,9999).'.'.$extension;
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);

      $media = '@'.$tmpFile;
      //上传到微信
      $wxAccessToken = $redis->hget('keyouxinxi','wx_access_token');
      exec('curl -F media='.$media.' https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$wxAccessToken, $result);
      $retData = json_decode($result[0], true);
      $picUrl = $retData['url'];
      unlink($tmpFile);
    
      $data = array('pic_url'=>$picUrl);
      echo json_encode($data);
      break;
    case 'get_detail':
      $id  = $_GET['id'];
      $sql = "SELECT * FROM mch_mall_products WHERE id = $id";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $productData = array('selling'=>array(), 'nostock'=>array(),'notselling'=>array());
      $sql = "SELECT * FROM mch_mall_products WHERE mch_id = $mchId ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        if (!$row['is_selling']) {
          $productData['notselling'][] = $row;
        } else if ('0' == $row['total_limit'] - $row['sold']) {
          $productData['nostock'] = $row;
        } else {
          $productData['selling'][] = $row;
        }
      }
      echo json_encode($productData);
      break;
    case 'set_stock':
      $mchId     = $_GET['mch_id'];
      $productId = $_GET['id'];
      $stock     = $_GET['stock'];
      $sql = "UPDATE mch_mall_products SET stock = $stock WHERE mch_id = $mchId AND id = $productId";
      $db->query($sql);
      echo 'success';
      break;
    case 'create':
      $mchId = $_GET['mch_id'];
      $iconUrl   = $_GET['icon_url'];
      $imageUrlStr  = $_GET['image_url'];
      $title = $_GET['title'];
      $amount   = $_GET['amount'];
      $price    = $_GET['price'];
      $detail   = $_GET['detail'];
      $totalLimit = $_GET['total_limit'];
      $singleLimit= $_GET['single_limit'];
      $isDistribute = $_GET['is_distribute'];
      $grade        = $_GET['grade'];
      $bonus        = $_GET['bonus'] ? $_GET['bonus'] : 0;

      $sql = "SELECT id FROM mch_mall_products WHERE mch_id = $mchId AND title = '$title'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
        exit();
      }
      $sql = "INSERT INTO mch_mall_products (mch_id, title, amount, price, detail, icon_url, detail_images, total_limit, single_limit, is_selling, is_distribute, distribute_grade, distribute_bonus, created_at) VALUES ($mchId, '$title', $amount, $price,'$detail', '$iconUrl', '$imageUrlStr', $totalLimit, '$singleLimit', 1, $isDistribute, $grade, $bonus, '$now')";
      $db->query($sql);
      echo 'success';
      break;
    default:
      break;
  }
