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
      $sql = "UPDATE mch_groupons SET is_stop = 1 WHERE id = $id";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_qrcode':
      $id = $_GET['id'];
      $sql = "SELECT mch_id, qrcode_url FROM mch_groupons WHERE id = $id";
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
        $data = array('path'=>'pages/groupon/detail?id='.$id);
        $buffer = sendHttpRequest($url, $data);

        $filename = substr(md5('groupon_'.$id.time()), 8, 16);
        $object = 'groupon/'.date('Ymd').'/'.$filename.'.png';
        $qrcodeUrl = putOssObject($object, $buffer);

        $sql = "UPDATE mch_groupons SET qrcode_url = '$qrcodeUrl' WHERE id = $id";
        $db->query($sql);
        $row['qrcode_url'] = $qrcodeUrl;
        echo json_encode($row);
      }
      break;
    case 'get_detail':
      $id  = $_GET['id'];
      $sql = "SELECT * FROM mch_groupons WHERE id = $id";
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
      $couponType = $_GET['coupon_type'];
      $grouponData = array('enable'=>array(), 'unenable'=>array(),'stop'=>array());
      $sql = "SELECT * FROM mch_groupons WHERE mch_id = $mchId AND coupon_type = '$couponType' ORDER BY created_at DESC";
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
      $couponType = $_GET['coupon_type'];
      $sql   = "SELECT id, name, amount FROM coupons WHERE mch_id = $mchId AND coupon_type = '$couponType' AND balance > 0 ORDER BY id DESC";
      $data  = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'create':
      $mchId = $_GET['mch_id'];
      $couponId = $_GET['coupon_id'];
      $couponType = $_GET['coupon_type'];
      $couponName = $_GET['coupon_name'];
      $couponTotal= $_GET['coupon_total'];
      $amount   = $_GET['amount'];
      $price    = $_GET['price'];
      $dateStart= $_GET['date_start'];
      $dateEnd  = $_GET['date_end'];
      $isLimit  = $_GET['is_limit'];
      $totalLimit = $isLimit ? $_GET['total_limit'] : 10000;
      $singleLimit= $isLimit ? $_GET['single_limit'] : 0;
      $isDistribute = $_GET['is_distribute'];
      $grade        = $_GET['grade'];
      $bonus        = $_GET['bonus'] ? $_GET['bonus'] : 0;
      $title = $price.'元抢购'.$couponName;
      if ($couponTotal > 1) {
        if ('groupon' == $couponType) {
          $title .= 'X'.$couponTotal.'张';
        } else {
          $title .= 'X'.$couponTotal.'次';
        }
      }

      $sql = "SELECT id FROM mch_groupons WHERE mch_id = $mchId AND coupon_id = $couponId AND coupon_total = $couponTotal AND (date_end > '$dateStart' AND date_start < '$dateEnd') AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
        exit();
      }
      $sql = "INSERT INTO mch_groupons (mch_id, title, coupon_id, coupon_type, coupon_name, coupon_total, amount, price, total_limit, date_start, date_end, single_limit, is_distribute, distribute_grade, distribute_bonus, created_at) VALUES ($mchId, '$title', $couponId, '$couponType', '$couponName', $couponTotal, $amount, $price, $totalLimit, '$dateStart', '$dateEnd', '$singleLimit', $isDistribute, $grade, $bonus, '$now')";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_sold_list':
      $grouponId = $_GET['groupon_id'];
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $sql = "SELECT COUNT(id) AS total FROM wechat_groupon_pays WHERE groupon_id = $grouponId  AND created_at > '$dateStart' AND created_at < '$dateEnd' ORDER BY created_at DESC";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT mch_id, openid, buy_total, created_at FROM wechat_groupon_pays WHERE groupon_id = $grouponId  AND created_at > '$dateStart' AND created_at < '$dateEnd' ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $sql = "SELECT nickname, headimgurl FROM members WHERE mch_id = $row[mch_id] AND sub_openid = '$row[openid]'";
        $ret = $db->fetch_row($sql);
        $row['nickname'] = $ret['nickname'];
        $row['headimgurl'] = $ret['headimgurl'];
      }
      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'get_coupon_list':
      $couponId = $_GET['coupon_id'];
      $type     = $_GET['type'];
      $getType  = $_GET['get_type'];
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $sql = "SELECT COUNT(id) AS total FROM member_coupons WHERE coupon_id = $couponId AND get_type = '$getType'";
      if ('consumed' == $type) {
        $sql .= " AND status = 0 AND updated_at >= '$dateStart' AND updated_at <= '$dateEnd' AND updated_at <= date_format(`date_end`,'%Y-%m-%d 23:59:59')";
      } else if ('expired' == $type) {
        $sql .= " AND status = 0 AND updated_at > DATE_FORMAT(date_end, '%Y-%m-%d 23:59:59')"; 
      }
      $sql .= " ORDER BY updated_at DESC";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT openid, updated_at FROM member_coupons WHERE coupon_id = $couponId AND get_type = '$getType'";
      if ('consumed' == $type) {
        $sql .= " AND status = 0 AND updated_at >= '$dateStart' AND updated_at <= '$dateEnd' AND updated_at <= date_format(`date_end`,'%Y-%m-%d 23:59:59')";
      } else if ('expired' == $type) {
        $sql .= " AND status = 0 AND updated_at > DATE_FORMAT(date_end, '%Y-%m-%d 23:59:59')";       
      }
      $sql .= " ORDER BY updated_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $sql = "SELECT nickname, headimgurl FROM members WHERE sub_openid = '$row[openid]'";
        $ret = $db->fetch_row($sql);
        $row['nickname'] = $ret['nickname'];
        $row['headimgurl'] = $ret['headimgurl'];
      }
      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    default:
      break;
  }
