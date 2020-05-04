<?php
  require_once dirname(__FILE__).'/../common.php';
  require_once dirname(__FILE__).'/../lib/WxPay.Api.php';
  require_once dirname(__FILE__).'/../unit/WxPay.JsApiPay.php';
  require_once dirname(__FILE__).'/../unit/log.php';

  $now = date('Y-m-d H:i:s');
  $action = $_GET['action'];
  switch ($action) {
    case 'get_top':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, title, coupon_id, amount, price,people FROM mch_togethers WHERE mch_id = '$mchId' AND is_stop = 0 AND date_start < '$now' AND date_end > '$now' ORDER BY created_at DESC";
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
    case 'get_detail_by_no':
      $togetherNo = $_GET['together_no'];
      $sql = "SELECT * FROM wechat_groupon_pays WHERE together_no = '$togetherNo'";
      $row = $db->fetch_row($sql);

      $sql = "SELECT * FROM coupons WHERE id = $row[coupon_id]";
      $ret = $db->fetch_row($sql);
      $row['coupon_data'] = $ret;

      $sql = "SELECT image_url, is_icon FROM coupon_images WHERE coupon_id = $row[coupon_id]";
      $data = $db->fetch_array($sql);
      if ($data) {
        foreach ($data as $v) {
          if ($v['is_icon']) {
            $iconImage = $v['image_url'];
          }
        }
        $row['icon_image'] = $iconImage;
        $row['image_list'] = $data;
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
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM mch_togethers WHERE id = $id";
      $row = $db->fetch_row($sql);

      $sql = "SELECT * FROM coupons WHERE id = $row[coupon_id]";
      $ret = $db->fetch_row($sql);
      $row['coupon_data'] = $ret;

      $sql = "SELECT image_url, is_icon FROM coupon_images WHERE coupon_id = $row[coupon_id] AND image_url !=''";
      $data = $db->fetch_array($sql);
      if ($data) {
        foreach ($data as $v) {
          if ($v['is_icon']) {
            $iconImage = $v['image_url'];
          }
        }
        $row['icon_image'] = $iconImage;
        $row['image_list'] = $data;
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
    case 'get_list':
      $togetherNo = $_GET['together_no'];
      $sql = "SELECT openid FROM wechat_groupon_pays WHERE is_together = 1 AND together_no = '$togetherNo' ORDER BY created_at DESC LIMIT 5";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $sql = "SELECT headimgurl FROM members WHERE sub_openid = '$row[openid]'";
        $ret = $db->fetch_row($sql);
        $row['headimgurl'] = $ret['headimgurl'];
      }
      echo json_encode($data);
      break;
    case 'get_share_image':
      $id = $_GET['id'];
      $appId    = $_GET['appid'];
      $openId   = $_GET['openid'];
      $couponId = $_GET['coupon_id'];

      $sql = "SELECT mch_id, price, title FROM mch_togethers WHERE id = $id";
      $row = $db->fetch_row($sql);
      $mchId = $row['mch_id'];
      $price = $row['price'];

      $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/together/detail?id='.$id);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('together_'.$id.time()), 8, 16);
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
      $imageUrl = $row['image_url'];

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
    case 'getPrepay':
      $appId       = $_GET['appid'];
      $subOpenId   = $_GET['openid'];
      $subMchId    = $_GET['mch_id'];
      $totalFee    = $_GET['consume'] * 100;
      $togetherId   = $_GET['together_id'];
      $togetherTitle = $_GET['title'];
      $couponId    = $_GET['coupon_id'];
      $couponName  = $_GET['coupon_name'];
      $buyTotal    = $_GET['buy_total'];
      $isHead      = $_GET['is_head'];
      $togetherNo  = $_GET['together_no'];

      $sql = "SELECT is_limit, total_limit, sold, single_limit, expire_times FROM mch_togethers WHERE id = $togetherId";
      $row = $db->fetch_row($sql);
      $expireTimes = $row['expire_times'];
      if ($row['is_limit'] && $buyTotal + $row['sold'] > $row['total_limit']) {
        $resultData = array('result'=>'fail', 'msg'=>'库存不足');
        echo json_encode($resultData);
        exit();
      }
      if ($row['is_limit'] && $row['single_limit'] > 0) {
        $sql = "SELECT COUNT(buy_total) AS total FROM wechat_groupon_pays WHERE openid = '$subOpenId' AND groupon_id = $togetherId AND is_together = 1";
        $ret = $db->fetch_row($sql);
        $total = $ret['total'] + $buyTotal;
        if ($total > $row['single_limit']) {
          $resultData = array('result'=>'fail', 'msg'=>'每人最多只能购买'.$row['single_limit'].'份，加上您之前的购买份数您已超出');
          echo json_encode($resultData);
          exit();
        }
      }
      $sql = "SELECT id FROM wechat_groupon_pays WHERE openid = '$subOpenId' AND groupon_id = $togetherId AND is_together = 1 AND together_no = '$togetherNo'";
      $ret = $db->fetch_row($sql);
      if ($ret['id']) {
        $resultData = array('result'=>'fail', 'msg'=>'您已经参与过此拼团了');
        echo json_encode($resultData);
        exit();
      }

      $outTradeNo = getOutTradeNo();
      $body = '购买'.$togetherTitle;
      if ($buyTotal > 1) {
        $body .= $buyTotal.'份';
      }

      $key = md5($subOpenId.$togetherId.time());
      if (!$togetherNo) {
        $togetherNo = date('ndHi').rand(1000,9999);
      }
      $attach = 'together,'.implode(',', array($key, $togetherId, $buyTotal, $subMchId, $couponId, $isHead, $togetherNo));
      $data = array(
                  'appid'  => KEYOU_MP_APP_ID,
                  'mch_id'  => MCHID,
                  'sub_appid'  => $appId,
                  'sub_mch_id' => $subMchId,
                  'nonce_str'  => WxPayApi::getNonceStr(),
                  'sign_type'  => 'MD5',
                  'body'       => $body,
                  'attach'     => $attach,
                  'out_trade_no' => $outTradeNo,
                  'total_fee'    => $totalFee,
                  'sub_openid'       => $subOpenId,
                  'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
                  'notify_url'       => SITE_URL.'/payNotify.php',
                  'trade_type'       => 'JSAPI',
                  'goods_tag'        => 'hascoupon',
                  'profit_sharing'   => 'Y');
      //profit_sharing 服务商分账，用于冻结资金，当拼团未成团时，先解冻该笔资金，再退款,实际并未分账
      $sign = MakeSign($data);
      $data['sign'] = $sign;
      $xml = ToXml($data);
      //统一下单
      $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
      $response = postXmlCurl($xml, $url, false, 6);
      $responseData = FromXml($response);
      $redis->hset('keyou_prepay_ids', $key, $responseData['prepay_id']);

       $data = array(
                     'appId' => $appId,
                     'timeStamp' => (string)time(),
                     'nonceStr'  => $responseData['nonce_str'],
                     'package'   => 'prepay_id='.$responseData['prepay_id'],
                     'signType'  => 'MD5'
                    );
       $sign = MakeSign($data);
       $data['paySign'] = $sign;
       $resultData = array('result'=>'success', 'payargs'=>$data, 'out_trade_no'=>$outTradeNo);
       echo json_encode($resultData);
       break;
    default:
      break;
  }
