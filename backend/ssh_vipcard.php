<?php
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

  $action     = $_GET['action'];
  $now        = date('Y-m-d H:i:s');

  switch ($action) {
    case 'start':
      $id  = $_GET['id'];
      $sql = "UPDATE mch_vipcards SET is_stop = 0 WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'stop':
      $id  = $_GET['id'];
      $sql = "UPDATE mch_vipcards SET is_stop = 1 WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM mch_vipcards WHERE id = $id";
      $row = $db->fetch_row($sql);

      $sql = "SELECT discount, point_speed, privilege FROM app_grades WHERE mch_id = $row[mch_id] AND grade = $row[grade]";
      $ret = $db->fetch_row($sql);
      $row['grade_info'] = $ret;

      $sql = "SELECT coupon_name, coupon_total FROM app_opengifts WHERE mch_id = $row[mch_id] AND grade = $row[grade]";
      $data = $db->fetch_array($sql);
      $row['opengifts'] = $data;
      echo json_encode($row);
      break;
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, pic_url, sold, price, grade_name,revenue FROM mch_vipcards WHERE mch_id = $mchId ORDER BY id DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_vipgrades':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT * FROM app_grades WHERE mch_id = $mchId AND catch_type = 'pay' ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'upload_logo':
      $openId = $_POST['openid'];
      $ret = explode('.', $_FILES['file']['name']);

      $extension = end($ret);
      $tmpFile = '/tmp/vipmember_'.$openId.'.'.$extension;
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);

      $object = 'merchant/'.date('Ymd').'/'.md5($openId.'_'.time()).'.'.$extension;
      $photoUrl = putOssObject($object, file_get_contents($tmpFile));
      unlink($tmpFile);

      $data = array('pic_url'=>$photoUrl);
      echo json_encode($data);
      break;
    case 'create':
      $mchId = $_GET['mch_id'];
      $grade = $_GET['grade'];
      $gradeName = $_GET['grade_name'];
      $price     = $_GET['price'];
      $isLimit   = $_GET['is_limit'];
      $totalLimit= $_GET['total_limit'];
      $picUrl    = $_GET['pic_url'];
      $couponIds = $_GET['coupon_ids'];
      $couponNames = $_GET['coupon_names'];
      $couponTotals = $_GET['totals'];

      $sql = "SELECT id FROM mch_vipcards WHERE mch_id = $mchId AND grade = $grade AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
        exit();
      }

      //有可能要领取多张不同的优惠券
      $couponIdData = explode('#', $couponIds);
      $couponNameData = explode('#', $couponNames);
      $totalData  = explode('#', $couponTotals);

      $sql = "DELETE FROM app_opengifts WHERE mch_id = $mchId AND grade = $grade";
      $db->query($sql);
      foreach ($couponIdData as $key=>$couponId) {
        if (!$couponId) {
          continue;
        }
        $couponName = $couponNameData[$key];
        $couponTotal= $totalData[$key];
        $sql = "INSERT INTO app_opengifts (mch_id, grade, coupon_id, coupon_name, coupon_total, created_at) VALUES ($mchId, $grade, $couponId, '$couponName', $couponTotal, '$now')";
        $db->query($sql);
      }
      $sql = "SELECT valid_days FROM app_grades WHERE mch_id = $mchId AND grade = $grade";
      $row = $db->fetch_row($sql);
      $validDays = $row['valid_days'];

      $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $merchantName = $row['merchant_name'];

      $sql = "INSERT INTO mch_vipcards (mch_id, merchant_name, grade, grade_name, valid_days, price, is_limit, total_limit, pic_url, created_at) VALUES ($mchId, '$merchantName', $grade, '$gradeName', $validDays, $price, $isLimit, $totalLimit, '$picUrl', '$now')";
      echo $sql.PHP_EOL;
      $db->query($sql);
      echo 'success';
      break;
    case 'get_qrcode':
      $id = $_GET['id'];
      $sql = "SELECT mch_id, qrcode_url FROM mch_vipcards WHERE id = $id";
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
        $data = array('path'=>'pages/vipcard/detail?id='.$id);
        $buffer = sendHttpRequest($url, $data);

        $filename = substr(md5('vipcard_'.$id.time()), 8, 16);
        $object = 'vipcard/'.date('Ymd').'/'.$filename.'.png';
        $qrcodeUrl = putOssObject($object, $buffer);

        $sql = "UPDATE mch_vipcards SET qrcode_url = '$qrcodeUrl' WHERE id = $id";
        $db->query($sql);
        $row['qrcode_url'] = $qrcodeUrl;
        echo json_encode($row);
      }
      break;
    default:
      break;
  }
