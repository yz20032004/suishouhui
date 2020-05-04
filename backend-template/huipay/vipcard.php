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
      $sql = "SELECT id, grade, grade_name, price, pic_url FROM mch_vipcards WHERE mch_id = $mchId AND is_stop = 0 ORDER BY sold DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM mch_vipcards WHERE id = $id";
      $row = $db->fetch_row($sql);

      $sql = "SELECT coupon_id, coupon_name, coupon_total FROM app_opengifts WHERE mch_id = $row[mch_id] AND grade=$row[grade]";
      $ret = $db->fetch_array($sql);
      $row['opengifts'] = $ret;

      $sql = "SELECT discount, point_speed, privilege FROM app_grades WHERE mch_id=$row[mch_id] AND grade=$row[grade]";
      $ret = $db->fetch_row($sql);
      $row['grade_data'] = $ret;
      echo json_encode($row);
      break;
    case 'getPrepay':
      $appId       = $_GET['appid'];
      $subOpenId   = $_GET['openid'];
      $mchId       = $_GET['mch_id'];
      $totalFee    = $_GET['consume'] * 100;
      $gradeName   = $_GET['grade_name'];
      $grade       = $_GET['grade'];

      $outTradeNo = getOutTradeNo();
      $body = '购买'.$gradeName;

      $key = md5($subOpenId.$totalFee.time());

      $attach = 'vipcard,'.implode(',', array($key, $grade, $gradeName));
      $sql = "SELECT appid,  pay_platform FROM mchs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $payPlatForm = $row['pay_platform'];
      if ('suishouhui' == $payPlatForm) {
        $jsApiArgs = postUnifiedorder($appId, $subOpenId, $mchId, $body, $attach, $totalFee);
      } else if ('tenpay' == $payPlatForm) {
        $sql = "SELECT * FROM mch_tenpay_configs WHERE mch_id = $mchId";
        $tenpayConfig = $db->fetch_row($sql);
        $jsApiArgs = postTenpayUnifiedorder($appId, $tenpayConfig, $subOpenId, $mchId, $body, $attach, $totalFee);
      }
      $redis->hset('keyou_prepay_ids', $key, $jsApiArgs['prepay_id']);
      echo json_encode($jsApiArgs);
      break;
    case 'get_share_image':
      $id = $_GET['id'];
      $appId    = $_GET['appid'];
      $openId   = $_GET['openid'];

      $sql = "SELECT mch_id,grade_name,valid_days,price,total_limit,pic_url,merchant_name FROM mch_vipcards WHERE id = $id";
      $row = $db->fetch_row($sql);
      $mchId = $row['mch_id'];
      $gradeName = $row['grade_name'];
      $validDays = $row['valid_days'];
      $price = $row['price'];
      $totalLimit = $row['total_limit'];
      $picUrl     = $row['pic_url'];
      $merchantName = $row['merchant_name'];

      $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/vipcard/detail?id='.$id);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('vipcard_'.$id.time()), 8, 16);
      $shareQrCodeObject = 'groupon/'.date('Ymd').'/'.$filename.'.png';
      $shareQrCodeUrl = putOssObject($shareQrCodeObject, $buffer);

      $title = '【'.$merchantName.'】'.$gradeName;
      if ('0' == $validDays) {
        $title .= ' 永久有效';
      } else {
        $title .= ' 有效期'.$validDays.'天';
      }
      if ($totalLimit) {
        $title .= '限量'.$totalLimit.'张';
      }
      if (mb_strlen($title) > 15) {
        $markTitleLineOne = mb_substr($title, 0, 16);
        $markTitleLineTwo = mb_substr($title, 16, 32);
      } else {
        $markTitleLineOne = $title;
        $markTitleLineTwo = '';
      }

      $headImgObject = 'groupon/'.date('Ymd').'/'.substr(md5('vipcard_'.$id.'_'.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($headImgObject, file_get_contents($picUrl));

      $markHeadImgUrl = $photoUrl.'?x-oss-process=image/resize,w_620,h_550,limit_0,m_fill';
      $markHeadObject = 'groupon/'.date('Ymd').'/'.substr(md5('groupon_mark'.$id.'_'.time()), 8, 8).'.jpg';
      $markedUrl = putOssObject($markHeadObject, file_get_contents($markHeadImgUrl));
      
      $newUrl = $photoUrl.'?x-oss-process=image/crop,w_500,h_1300';
      $url = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/groupon/background/grouponbackground.jpg?x-oss-process=image/resize,w_750/watermark,';
      $url .= 'image_'.urlencode(base64_encode($markHeadObject)).',t_90,g_nw,x_60,y_70/watermark,';
      $url .= 'image_'.urlencode(base64_encode($shareQrCodeObject.'?x-oss-process=image/resize,P_20')).',t_90,g_se,x_110,y_260/watermark,';

      $sql = "SELECT headimgurl, nickname FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
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
      $shareImgObject = 'groupon/'.date('Ymd').'/'.substr(md5('share_'.$id.'_'.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($shareImgObject, file_get_contents($url));
    
      echo $photoUrl;
      break;
    default:
      break;
  }
