<?php
  //优惠券操作
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
    case 'get_detail':
      $id  = $_GET['id'];
      $sql = "SELECT * FROM coupons WHERE id = '$id'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_icon_url':
      $id  = $_GET['id'];
      $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = '$id' AND is_icon = 1";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_image_list':
      $id  = $_GET['id'];
      $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = '$id'";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $getType = isset($_GET['get_type']) ? $_GET['get_type'] : '';
      $data = array('enable'=>array(), 'unenable'=>array(),'expire'=>array());
      $sql = "SELECT * FROM coupons WHERE mch_id = '$mchId'";
      if (!$getType) {
        $sql .= " AND coupon_type NOT IN ('groupon', 'timing')";
      }  else if ('all' != $getType){
        $sql .= " AND coupon_type = '$getType'";
      }
      $sql .= " ORDER BY created_at DESC";
      $ret = $db->fetch_array($sql);
      foreach ($ret as $row) {
        if ('hard' == $row['validity_type']) {
          if (time() - strtotime($row['date_end']) > 0) {
            $data['expire'][] = $row;
            continue;
          } else if (time() - strtotime($row['date_start']) < 0) {
            $data['unenable'][] = $row;
            continue;
          }
        }
        if ($row['balance'] > 0) {
          $data['enable'][] = $row;
        } else {
          $data['empty'][] = $row;
        }
      }
      echo json_encode($data);
      break;
    case 'create':
      $mchId = $_GET['mch_id'];

      $isUsefullySendday = $_GET['is_usefully_sendday'];
      $isSingle     = $_GET['is_single'];
      $description  = $_GET['description'];
      $balance      = $_GET['balance'];
      $consumeLimit = $_GET['consume_limit'];
      $type = $_GET['type'];
      $dealDetail = isset($_GET['deal_detail']) ? $_GET['deal_detail'] : '';
      if ('cash' == $type) {
        $amount = $_GET['amount'];
        $name = '满'.$consumeLimit.'减'.$amount.'元券';
        $discount = 0;
      } else if ('waimai' == $type) {
        $amount = $_GET['amount'];
        $name = '外送满'.$consumeLimit.'减'.$amount.'元券';
        $discount = 0;
      } else if ('mall' == $type) {
        $amount = $_GET['amount'];
        $name = '商城满'.$consumeLimit.'减'.$amount.'元券';
        $discount = 0;
      } else if ('discount' == $type) {
        $name = $_GET['name'];
        $discount = $_GET['discount'];
        $amount = 0;
      } else {
        $amount = $_GET['amount'];
        $name = $_GET['name'];
        $discount = 0;
      }
      $validityType = $_GET['validity_type'];
      if ('relative' == $validityType) {
        $dateStart = null;
        $dateEnd   = null;
        $totalDays    = $_GET['total_days'];
      } else {
        $dateStart    = $_GET['date_start'];
        $dateEnd      = $_GET['date_end'];
        $totalDays    = 0;
      }
      
      $sql = "INSERT INTO coupons(mch_id, coupon_type, name, discount, amount, validity_type, date_start, date_end, total_days, is_usefully_sendday, is_single, consume_limit, balance, deal_detail, description, created_at) VALUES ('$mchId', '$type', '$name', '$discount', '$amount', '$validityType', '$dateStart', '$dateEnd', '$totalDays', '$isUsefullySendday', '$isSingle', '$consumeLimit', $balance, '$dealDetail', '$description', '$now')";
      $db->query($sql);
      $couponId = $db->get_insert_id();

      $sql = "SELECT appid FROM mchs WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      if (SUISHOUHUI_APP_ID == $ret['appid']) {
        $miniAccessToken = $redis->hget('keyou_mini', 'access_token');//随手惠生活ACCESSTOKEN
      } else {
        $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $ret['appid']);
      }
      $wxAccessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $newParam = array(
                        'job'               => 'create_coupon',
                        'access_token'      => $wxAccessToken,
                        'mini_access_token' => $miniAccessToken,
                        'mch_id'            => $mchId,
                        'appid'             => $ret['appid'],
                        'coupon_id'         => $couponId,
                        'coupon_type'       => $type
                       );
      $redis->rpush('keyou_mch_job_list', serialize($newParam));

      echo $couponId;
      break;
    case 'update_balance':
      $couponId = $_GET['id'];
      $balance  = $_GET['balance'];
      $sql = "UPDATE coupons SET balance = balance + $balance WHERE id = $couponId";
      $db->query($sql);

      $sql = "SELECT * FROM coupons WHERE id = $couponId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'disable':
      $couponId = $_GET['id'];
      $mchId    = $_GET['mch_id'];
      $sql = "SELECT id FROM app_opengifts WHERE mch_id = $mchId AND coupon_id = $couponId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $data = array('code'=>'fail', 'campaign'=>'开卡礼');
        echo json_encode($data);
        exit();
      }
      $sql = "SELECT id FROM app_point_exchange_rules WHERE mch_id = $mchId AND coupon_id = $couponId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $data = array('code'=>'fail', 'campaign'=>'积分兑换');
        echo json_encode($data);
        exit();
      }
      $sql = "SELECT id FROM app_recharge_coupon_rules WHERE mch_id = $mchId AND coupon_id = $couponId";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $data = array('code'=>'fail', 'campaign'=>'储值优惠');
        echo json_encode($data);
        exit();
      }
      $sql = "SELECT id, title FROM campaigns WHERE mch_id = $mchId AND coupon_id = $couponId AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $data = array('code'=>'fail', 'campaign'=>$row['title']);
        echo json_encode($data);
        exit();
      }
      $sql = "SELECT id FROM mch_groupons WHERE mch_id = $mchId AND coupon_id = $couponId AND is_stop = 0";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $data = array('code'=>'fail', 'campaign'=>'超值抢购');
        echo json_encode($data);
        exit();
      }
      $sql = "UPDATE coupons SET balance = 0 WHERE id = $couponId";
      $db->query($sql);
      $data = array('code'=>'success');
      echo json_encode($data);
      break;
    case 'get_code_detail':
      $code = $_GET['code'];
      $mchId = $_GET['mch_id'];

      $sql = "SELECT id, openid, code, coupon_id, coupon_name, amount, discount, consume_limit, detail, date_start, date_end FROM member_coupons WHERE mch_id = $mchId AND code = '$code' AND status = 1";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $date = date('Y-m-d');
        $sql = "SELECT COUNT(id) AS total FROM member_coupons WHERE mch_id = $mchId AND coupon_id = $row[coupon_id] AND status = 1 AND date_start <= '$date' AND date_end >= '$date' AND openid = '$row[openid]' AND code != ''";
        $ret = $db->fetch_row($sql);
        $row['total'] = $ret['total'];
      }
      echo json_encode($row);
      break;
    //核销券码
    case 'get_used_list':
      $mchId = $_GET['mch_id'];
      $shopId= isset($_GET['shop_id'])?$_GET['shop_id']:0;
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $sql = "SELECT COUNT(id) AS total FROM coupons_used WHERE mch_id = $mchId  AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      if ($shopId) {
        $sql .= " AND shop_id = $shopId";
      }
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT mch_id, openid, coupon_name, amount, date_format(created_at, '%m-%d %H:%i') AS created_at, created_by_uname FROM coupons_used WHERE mch_id = $mchId AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      if ($shopId) {
        $sql .= " AND shop_id = $shopId";
      }
      $sql .= " ORDER BY created_at DESC LIMIT $startRow, $pageCount";
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
    case 'consume_code':
      $code = $_GET['code'];
      $mchId  = $_GET['mch_id'];
      $couponAmount = $_GET['coupon_amount'];
      $createdBy    = $_GET['created'];
      $shopId       = isset($_GET['shop_id'])?$_GET['shop_id']:0;
      $consumeTotal = isset($_GET['consume_total'])?$_GET['consume_total']:1;

      $sql = "SELECT id FROM member_coupons WHERE mch_id = $mchId AND code = '$code' AND status = 1";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        echo 'fail';
        exit();
      }
      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $data = array('job'=>'consume_coupon', 'access_token'=>$accessToken, 'mch_id'=>$mchId, 'code'=>$code, 'coupon_amount'=>$couponAmount, 'shop_id'=>$shopId, 'created_by'=>$createdBy, 'consume_total'=>$consumeTotal);
      $redis->rpush('member_job_list', serialize($data));

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
    case 'update_abstract':
      $couponId = $_GET['coupon_id'];
      $iconUrl   = $_GET['icon_url'];
      $imageUrlStr  = $_GET['image_url'];

      $sql = "DELETE FROM coupon_images WHERE coupon_id = $couponId";
      $db->query($sql);

      $sql = "SELECT mch_id, coupon_type, wechat_cardid FROM coupons WHERE id = $couponId";
      $row = $db->fetch_row($sql);
      $cardId = $row['wechat_cardid'];
      $couponType = $row['coupon_type'];
      $mchId      = $row['mch_id'];

      $sql = "INSERT INTO coupon_images (mch_id, coupon_id, image_url, is_icon, created_at) VALUES ($mchId, $couponId, '$iconUrl', 1, '$now')";
      $db->query($sql);

      $imageUrlList = explode(',', $imageUrlStr);
      foreach ($imageUrlList as $url) {
        if ($url) {
          $sql = "INSERT INTO coupon_images (mch_id, coupon_id, image_url, is_icon, created_at) VALUES ($mchId, $couponId, '$url', 0, '$now')";
          $db->query($sql);
        }
      }
      $data = array(
                'card_id'   => $cardId,               
                $couponType => array(
                     'advanced_info' => array(
                                          'abstract' => array('icon_url_list' => $iconUrl)
                                        )
                  )
               );
      foreach ($imageUrlList as $url) {
        $data[$couponType]['advanced_info']['text_image_list'][] = array('image_url'=>$url, 'text'=>'');
      }
      $wxAccessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $url = 'https://api.weixin.qq.com/card/update?access_token='.$wxAccessToken;
      $ret = httpPost($url, json_encode($data));
      echo 'success';
      break;
    default:
      break;
  }
