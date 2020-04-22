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
    case 'getPrepay':
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
                  'sub_appid'  => SUISHOUHUI_APP_ID,
                  'sub_mch_id' => $subMchId,
                  'nonce_str'  => WxPayApi::getNonceStr(),
                  'sign_type'  => 'MD5',
                  'body'       => $body,
                  'attach'     => $attach,
                  'out_trade_no' => $outTradeNo,
                  'total_fee'    => $totalFee,
                  'openid'       => $subOpenId,
                  'spbill_create_ip' => $_SERVER['REMOTE_ADDR'],
                  'notify_url'       => SITE_URL.'/payNotify.php',
                  'trade_type'       => 'JSAPI',
                  'profit_sharing'   => 'Y'
                  );
       //profit_sharing 服务商分账，用于冻结资金，当拼团未成团时，先解冻该笔资金，再退款,实际并未分账
       $sign = MakePaySign($data);
       $data['sign'] = $sign;
       $xml = ToXml($data);
       //统一下单
       $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
       $response = postXmlCurl($xml, $url, false, 6);
       $responseData = FromXml($response);
       $redis->hset('keyou_prepay_ids', $key, $responseData['prepay_id']);

       $data = array(
                     'appId' => SUISHOUHUI_APP_ID,
                     'timeStamp' => (string)time(),
                     'nonceStr'  => $responseData['nonce_str'],
                     'package'   => 'prepay_id='.$responseData['prepay_id'],
                     'signType'  => 'MD5'
                    );
       $sign = MakePaySign($data);
       $data['paySign'] = $sign;
       $resultData = array('result'=>'success', 'payargs'=>$data, 'out_trade_no'=>$outTradeNo);
       echo json_encode($resultData);
       break;
    default:
      break;
  }

  function MakePaySign($values, $type='MD5')
  {
    //签名步骤一：按字典序排序参数
    ksort($values);
    $string = ToUrlParams($values);
    //签名步骤二：在string后加入KEY
    $string = $string . "&key=".KEYOU_KEY;
    //签名步骤三：MD5加密
    if ('MD5' == $type) {
      $string = md5($string);
    } else {
      $string = hash_hmac('sha256', $string, KEYOU_KEY);
    }
    //签名步骤四：所有字符转为大写
    $result = strtoupper($string);
    return $result;
  }
