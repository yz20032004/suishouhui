<?php
  require_once dirname(__FILE__).'/../common.php';
  require_once dirname(__FILE__).'/../lib/WxPay.Api.php';
  require_once dirname(__FILE__).'/../unit/WxPay.JsApiPay.php';
  require_once dirname(__FILE__).'/../unit/log.php';
  require_once dirname(__FILE__).'/pay.php';

  $now = date('Y-m-d H:i:s');
  $action = $_GET['action'];
  switch ($action) {
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, title, coupon_id, amount, price, total_limit, sold FROM mch_groupons WHERE mch_id = '$mchId' AND is_stop = 0 AND date_start < '$now' AND date_end > '$now' ORDER BY sold DESC";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $row[coupon_id] AND is_icon = 1";
        $ret = $db->fetch_row($sql);
        if ($ret) {
          $row['image_url'] = $ret['image_url'];
        } else {
          $sql = "SELECT logo_url FROM user_mch_submit WHERE sub_mch_id = '$mchId'";
          $ret = $db->fetch_row($sql);
          $row['image_url'] = $ret['logo_url'];
        }
      }
      echo json_encode($data);
      break;
    case 'get_top':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, title, coupon_id, amount, price FROM mch_groupons WHERE mch_id = '$mchId' AND is_stop = 0 AND date_start < '$now' AND date_end > '$now' ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $row[coupon_id] AND is_icon = 1";
        $ret = $db->fetch_row($sql);
        if ($ret) {
          $row['image_url'] = $ret['image_url'];
        } else {
          $sql = "SELECT logo_url FROM user_mch_submit WHERE sub_mch_id = '$mchId'";
          $ret = $db->fetch_row($sql);
          $row['image_url'] = $ret['logo_url'];
        }
      }
      echo json_encode($data);
      break;
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM mch_groupons WHERE id = $id";
      $row = $db->fetch_row($sql);

      $sql = "SELECT * FROM coupons WHERE id = $row[coupon_id]";
      $ret = $db->fetch_row($sql);
      $row['coupon_data'] = $ret;

      $detailImageList = array();
      $sql = "SELECT image_url, is_icon FROM coupon_images WHERE coupon_id = $row[coupon_id] AND image_url != ''";
      $data = $db->fetch_array($sql);
      if ($data) {
        foreach ($data as $v) {
          if ($v['is_icon']) {
            $iconImage = $v['image_url'];
          } else {
            $detailImageList[] = $v;
          }
        }
        $row['icon_image'] = $iconImage;
        $row['image_list'] = $detailImageList;
      } else {
        $sql = "SELECT logo_url FROM user_mch_submit WHERE sub_mch_id = $row[mch_id]";
        $ret = $db->fetch_row($sql);
        $row['icon_image'] = $ret['logo_url'];
        $row['image_list'] = array();
      }

      $sql = "SELECT logo_url, business_name, branch_name, avg_price, open_time, city, district, address,telephone FROM shops WHERE mch_id = $row[mch_id]";
      $ret = $db->fetch_row($sql);
      $row['shop'] = $ret;
      echo json_encode($row);
      break;
    case 'getPrepay':
      $appId       = $_GET['appid'];
      $subOpenId   = $_GET['openid'];
      $subMchId    = $_GET['mch_id'];
      $totalFee    = $_GET['consume'] * 100;
      $grouponId   = $_GET['groupon_id'];
      $grouponTitle = $_GET['title'];
      $couponId    = $_GET['coupon_id'];
      $couponTotal = $_GET['coupon_total'];
      $couponName  = $_GET['coupon_name'];
      $buyTotal    = $_GET['buy_total'];
      $singleLimit = $_GET['single_limit'];
      $distributeId= $_GET['distribute_id'];

      $sql = "SELECT total_limit, sold FROM mch_groupons WHERE id = $grouponId";
      $row = $db->fetch_row($sql);
      if ($buyTotal + $row['sold'] > $row['total_limit']) {
        $resultData = array('result'=>'fail', 'msg'=>'库存不足');
        echo json_encode($resultData);
        exit();
      }
      if ($singleLimit > 0) {
        $sql = "SELECT COUNT(buy_total) AS total FROM wechat_groupon_pays WHERE openid = '$subOpenId' AND groupon_id = $grouponId AND is_together = 0";
        $row = $db->fetch_row($sql);
        $total = $row['total'] + $buyTotal;
        if ($total > $singleLimit) {
          $resultData = array('result'=>'fail', 'msg'=>'每人最多只能购买'.$singleLimit.'份，加上您之前的购买份数您已超出');
          echo json_encode($resultData);
          exit();
        }
      }

      $outTradeNo = getOutTradeNo();
      $body = '购买'.$grouponTitle;
      if ($buyTotal > 1) {
        $body .= $buyTotal.'份';
      }

      $key = md5($subOpenId.$totalFee.time());

      $attach = 'groupon,'.implode(',', array($key, $grouponId, $buyTotal, $subMchId, $couponId, $couponTotal, $distributeId));
      $profitSharing = 'N';
      if ($distributeId) {
        //顾客团购分销
        $sql = "SELECT distribute_bonus FROM mch_groupon_distributes WHERE id = $distributeId";
        $row = $db->fetch_row($sql);
        if ($row['distribute_bonus']) {
          $profitSharing = 'Y';
        }
      }
      $basicFeeRate = $redis->hget('keyou_merchant_basic_fee_rate_list', $subMchId);
      $marketingFeeRate = $redis->hget('keyou_merchant_marketing_fee_rate_list', $subMchId);
      if ($marketingFeeRate - $basicFeeRate > 0) {
        //服务商团购分佣
        $profitSharing = 'Y';
      }

      $sql = "SELECT appid,  pay_platform FROM mchs WHERE mch_id = $subMchId";
      $row = $db->fetch_row($sql);
      $payPlatForm = $row['pay_platform'];
      if ('suishouhui' == $payPlatForm) {
        $jsApiArgs = postUnifiedorder($appId, $subOpenId, $subMchId, $body, $attach, $totalFee, $profitSharing);
      } else if ('tenpay' == $payPlatForm) {
        $sql = "SELECT * FROM mch_tenpay_configs WHERE mch_id = $subMchId";
        $tenpayConfig = $db->fetch_row($sql);
        $jsApiArgs = postTenpayUnifiedorder($appId, $tenpayConfig, $subOpenId, $subMchId, $body, $attach, $totalFee);
      }
      $redis->hset('keyou_prepay_ids', $key, $jsApiArgs['prepay_id']);
      $resultData = array('result'=>'success', 'payargs'=>$jsApiArgs);
      echo json_encode($resultData);
      break;
    case 'create_distribute':
      $appId     = $_GET['appid'];
      $grouponId = $_GET['id'];
      $mchId     = $_GET['mch_id'];
      $openId    = $_GET['openid'];
      $distributeBonus = $_GET['bonus'];
      $sql = "SELECT id FROM mch_groupon_distributes WHERE mch_id = $mchId AND groupon_id = $grouponId AND openid = '$openId' LIMIT 1";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO mch_groupon_distributes (mch_id, groupon_id, openid, distribute_bonus, created_at) VALUES ($mchId, $grouponId, '$openId', $distributeBonus, '$now')";
        $db->query($sql);
        $distributeId = $db->get_insert_id();

        if ($distributeBonus) {
          //添加分账
          profitsharingaddreceiver($appId, $mchId, $openId);
        }
      } else {
        $distributeId = $row['id'];
      }
      echo $distributeId;
      break;
    case 'get_share_image':
      $id = $_GET['id'];
      $appId    = $_GET['appid'];
      $openId   = $_GET['openid'];
      $couponId = $_GET['coupon_id'];
      $distributeId = $_GET['distribute_id'];

      $sql = "SELECT mch_id, price, title FROM mch_groupons WHERE id = $id";
      $row = $db->fetch_row($sql);
      $mchId = $row['mch_id'];
      $price = $row['price'];

      $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/groupon/detail?id='.$id.'&distribute_id='.$distributeId);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('distribute_'.$distributeId.time()), 8, 16);
      $shareQrCodeObject = 'groupon/'.date('Ymd').'/'.$filename.'.png';
      $shareQrCodeUrl = putOssObject($shareQrCodeObject, $buffer);

      $sql = "SELECT business_name, city, district, address, open_time FROM shops WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $merchantName = $ret['business_name'];
      $city         = $ret['city'];
      $district     = $ret['district'];
      $address      = $ret['address'];
      $openTime     = $ret['open_time'];
      $shopAdress   = '地址:'.$city.$district.$address;
      $shopOpenTime = '营业时间:'.$openTime;

      $title = '【'.$merchantName.'】'.$row['title'];
      if (mb_strlen($title) > 15) {
        $markTitleLineOne = mb_substr($title, 0, 16);
        $markTitleLineTwo = mb_substr($title, 16, 32);
      } else {
        $markTitleLineOne = $title;
        $markTitleLineTwo = '';
      }
      $sql = "SELECT image_url FROM coupon_images WHERE coupon_id = $couponId AND is_icon = 1";
      $row = $db->fetch_row($sql);
      if (!$row['image_url']) {
        $sql = "SELECT logo_url FROM shops WHERE mch_id = $mchId ORDER BY id LIMIT 1";
        $v   = $db->fetch_row($sql);
        $imageUrl = $v['logo_url'];
      } else {
        $imageUrl = $row['image_url'];
      }

      $headImgObject = 'groupon/'.date('Ymd').'/'.substr(md5('groupon_'.$id.'_'.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($headImgObject, file_get_contents($imageUrl));

      $markHeadImgUrl = $photoUrl.'?x-oss-process=image/resize,w_620,h_550,limit_0,m_fill';
      $markHeadObject = 'groupon/'.date('Ymd').'/'.substr(md5('groupon_mark'.$id.'_'.time()), 8, 8).'.jpg';
      $markedUrl = putOssObject($markHeadObject, file_get_contents($markHeadImgUrl));
      
      $newUrl = $photoUrl.'?x-oss-process=image/crop,w_500,h_1300';
      $url = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/groupon/background/grouponbackground.jpg?x-oss-process=image/resize,w_750/watermark,';
      $url .= 'image_'.urlencode(base64_encode($markHeadObject)).',t_90,g_nw,x_60,y_70/watermark,';
      $url .= 'image_'.urlencode(base64_encode($shareQrCodeObject.'?x-oss-process=image/resize,P_20')).',t_90,g_se,x_110,y_260/watermark,';

      $sql = "SELECT headimgurl, nickname FROM members WHERE sub_openid = '$openId'";
      $row = $db->fetch_row($sql);
      $headImgUrl = $row['headimgurl'];
      $nickname   = $row['nickname'];
      if ($headImgUrl) {
        $buffer = sendHttpGet($headImgUrl);
        $filename = substr(md5('head_'.$openId.time()), 8, 16);
        $headObject = 'groupon/'.date('Ymd').'/'.$filename.'.png';
        $headUrl    = putOssObject($headObject, $buffer);

        $circleHeadUrl = $headUrl.'?x-oss-process=image/circle,r_66';
        $filename = substr(md5('circle_head_'.$openId.time()), 8, 16);
        $circleHeadObject = 'groupon/'.date('Ymd').'/'.$filename.'.png';
        putOssObject($circleHeadObject, file_get_contents($circleHeadUrl));
        $url .= 'image_'.urlencode(base64_encode($circleHeadObject.'?x-oss-process=image/resize,P_13')).',t_90,g_sw,x_100,y_320/watermark,';
      }

      $lineOneObject = str_replace('/', '_', base64_encode($markTitleLineOne));
      $lineOneObject = str_replace('+', '-', $lineOneObject);
      $url .= 'text_'.$lineOneObject.',color_000000,size_42,g_sw,x_65,y_590/watermark,';
      if ($markTitleLineTwo) {
        $lineTwoObject = str_replace('/', '_', base64_encode($markTitleLineTwo));
        $lineTwoObject = str_replace('+', '-', $lineTwoObject);
        $url .= 'text_'.$lineTwoObject.',size_42,g_sw,x_65,y_530/watermark,';
      }
      $url .= 'text_'.urlencode(base64_encode($price)).',size_40,color_DC143C,g_sw,x_250,y_275';
      if ($nickname) {
        $url .= '/watermark,text_'.urlencode(base64_encode($nickname)).',color_000000,size_40,g_sw,x_220,y_350';
      }
      //$url .= 'text_'.urlencode(base64_encode($shopAdress)).',size_20,g_sw,x_150,y_150,';
      //$url .= 'text_'.urlencode(base64_encode($shopOpenTime)).',size_20,g_sw,x_150,y_120';


      $shareImgObject = 'groupon/'.date('Ymd').'/'.substr(md5('share_'.$id.'_'.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($shareImgObject, file_get_contents($url));
    
      echo $photoUrl;
      break;
    default:
      break;
  }

  //添加团购分销分账方
  function profitsharingaddreceiver($appId, $mchId, $openId)
  {
    $receiver =  array(
                        'type' => 'PERSONAL_SUB_OPENID',
                        'account' => $openId,
                        'relation_type' => 'DISTRIBUTOR'
                      );
    $data = array(
                'appid'  => KEYOU_MP_APP_ID,
                'mch_id'  => MCHID,
                'sub_appid'  => $appId,
                'sub_mch_id' => $mchId,
                'nonce_str'  => WxPayApi::getNonceStr(),
                'sign_type'  => 'HMAC-SHA256',
                'receiver'   => json_encode($receiver)
            );
     $sign = MakeSign($data, 'HMAC-SHA256');
     $data['sign'] = $sign;
     $xml = ToXml($data);
     //统一下单
     $url = 'https://api.mch.weixin.qq.com/pay/profitsharingaddreceiver';
     $response = postXmlCurl($xml, $url, false, 6);
  }
