<?php
  //商户团购管理
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
    case 'stop':
      $id  = $_GET['id'];
      $sql = "UPDATE mch_togethers SET is_stop = 1 WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_qrcode':
      $togetherId = $_GET['id'];
      $sql = "SELECT mch_id, qrcode_url FROM mch_togethers WHERE id = $togetherId";
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
        $data = array('path'=>'pages/together/detail?id='.$togetherId);
        $buffer = sendHttpRequest($url, $data);

        $filename = substr(md5('together_'.$togetherId.time()), 8, 16);
        $object = 'together/'.date('Ymd').'/'.$filename.'.png';
        $qrcodeUrl = putOssObject($object, $buffer);

        $sql = "UPDATE mch_togethers SET qrcode_url = '$qrcodeUrl' WHERE id = $togetherId";
        $db->query($sql);
        $row['qrcode_url'] = $qrcodeUrl;
        echo json_encode($row);
      }
      break;
    case 'get_detail':
      $id  = $_GET['id'];
      $sql = "SELECT * FROM mch_togethers WHERE id = $id";
      $row = $db->fetch_row($sql);
      if ($row['is_stop']) {
        $row['status_title'] = '已结束';
      } else {
        if ($now > $row['date_start'] && $now < $row['date_end']) {
          $row['status_title'] = '进行中';
        } else if ($now > $row['date_end']) {
          $row['status_title'] = '已结束';
        } else {
          $row['status_title'] = '未开始';
        }
      }
      echo json_encode($row);
      break;
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $grouponData = array('enable'=>array(), 'unenable'=>array(),'stop'=>array());
      $sql = "SELECT * FROM mch_togethers WHERE mch_id = $mchId ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $couponId = $row['coupon_id'];
        $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $couponId AND is_icon = 1";
        $ret = $db->fetch_row($sql);
        if ($ret['image_url']) {
          $row['icon_url'] = $ret['image_url'];
        } else {
          $sql = "SELECT logo_url FROM user_mch_submit WHERE sub_mch_id = $mchId";
          $ret = $db->fetch_row($sql);
          $row['icon_url'] = $ret['logo_url'];
        }
        if ($row['is_stop']) {
          $grouponData['stop'][] = $row;
        } else if (time() - strtotime($row['date_end']) > 0) {
          $grouponData['stop'][] = $row;
        } else if (time() - strtotime($row['date_start']) < 0) {
          $grouponData['unenable'][] = $row;
        } else {
          $grouponData['enable'][] = $row;
        }
      }
      echo json_encode($grouponData);
      break;
    case 'get_enable_coupons':
      $mchId = $_GET['mch_id'];
      $sql   = "SELECT id, name, amount FROM coupons WHERE mch_id = $mchId AND coupon_type = 'groupon' AND balance > 0 ORDER BY id DESC";
      $data  = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'create':
      $mchId = $_GET['mch_id'];
      $couponId      = $_GET['coupon_id'];
      $couponName    = $_GET['coupon_name'];
      $amount        = $_GET['amount'];
      $price         = $_GET['price'];
      $people        = $_GET['people'];
      $expireTimes   = $_GET['expire_times'];
      $isLimit       = $_GET['is_limit'];
      $totalLimit    = $isLimit ? $_GET['total_limit'] : 10000;
      $singleLimit   = $isLimit ? $_GET['single_limit'] : 0;
      $dateStart     = $_GET['date_start'];
      $dateEnd       = $_GET['date_end'];

      $sql = "SELECT id FROM mch_togethers WHERE coupon_id = $couponId AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
        exit();
      }
      $title = $people.'人成团'.$price.'元抢'.$couponName;
      $sql = "INSERT INTO mch_togethers (mch_id, coupon_id, coupon_name, title, amount, price, people, expire_times, is_limit, total_limit, single_limit, date_start, date_end, created_at) VALUES ($mchId, $couponId, '$couponName', '$title', $amount, $price, $people, $expireTimes, $isLimit, $totalLimit, $singleLimit, '$dateStart', '$dateEnd', '$now')";
      $db->query($sql);
      echo 'success';
      break;
    default:
      break;
  }
