<?php
  require_once dirname(__FILE__).'/../common.php';
  require_once dirname(__FILE__).'/../lib/WxPay.Api.php';
  require_once dirname(__FILE__).'/../unit/WxPay.JsApiPay.php';
  require_once dirname(__FILE__).'/../unit/log.php';

  $now = date('Y-m-d H:i:s');
  $action = $_GET['action'];
  switch ($action) {
    case 'get_config':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT delivery_cost, delivery_free_atleast, delivery_tip FROM mch_mall_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, title, icon_url, amount, price, total_limit, sold FROM mch_mall_products WHERE mch_id = '$mchId' AND is_selling = 1 AND total_limit > 0 ORDER BY sold DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_top':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, title, icon_url,amount, price, total_limit, sold FROM mch_mall_products WHERE mch_id = '$mchId' AND is_selling = 1 AND total_limit > 0 ORDER BY sold DESC LIMIT 3";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM mch_mall_products WHERE id = $id";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'create_distribute':
      $appId     = $_GET['appid'];
      $productId = $_GET['id'];
      $mchId     = $_GET['mch_id'];
      $openId    = $_GET['openid'];
      $distributeBonus = $_GET['bonus'];
      $sql = "SELECT id FROM mch_product_distributes WHERE mch_id = $mchId AND product_id = $productId AND openid = '$openId' LIMIT 1";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "INSERT INTO mch_product_distributes (mch_id, product_id, openid, distribute_bonus, created_at) VALUES ($mchId, $productId, '$openId', $distributeBonus, '$now')";
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
      $appId    = $_GET['appid'];
      $openId   = $_GET['openid'];
      $productId = $_GET['product_id'];
      $distributeId = $_GET['distribute_id'];

      $sql = "SELECT mch_id, price, title, icon_url FROM mch_mall_products WHERE id = $productId";
      $row = $db->fetch_row($sql);
      $mchId = $row['mch_id'];
      $price = $row['price'];
      $imageUrl = $row['icon_url'];

      $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/mall/detail?id='.$productId.'&distribute_id='.$distributeId);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('distribute_'.$distributeId.time()), 8, 16);
      $shareQrCodeObject = 'product/'.date('Ymd').'/'.$filename.'.png';
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

      $headImgObject = 'product/'.date('Ymd').'/'.substr(md5('product_'.$productId.'_'.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($headImgObject, file_get_contents($imageUrl));

      $markHeadImgUrl = $photoUrl.'?x-oss-process=image/resize,w_620,h_550,limit_0,m_fill';
      $markHeadObject = 'product/'.date('Ymd').'/'.substr(md5('product_mark'.$productId.'_'.time()), 8, 8).'.jpg';
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
        $headObject = 'product/'.date('Ymd').'/'.$filename.'.png';
        $headUrl    = putOssObject($headObject, $buffer);

        $circleHeadUrl = $headUrl.'?x-oss-process=image/circle,r_66';
        $filename = substr(md5('circle_head_'.$openId.time()), 8, 16);
        $circleHeadObject = 'product/'.date('Ymd').'/'.$filename.'.png';
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


      $shareImgObject = 'product/'.date('Ymd').'/'.substr(md5('share_'.$productId.'_'.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($shareImgObject, file_get_contents($url));
    
      echo $photoUrl;
      break;
    default:
      break;
  }

  //添加商城分销分账方
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
