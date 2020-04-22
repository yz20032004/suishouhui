<?php
  /**
   * 商户初始化操作
   */
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/WxPay.Config.php';
  require_once dirname(__FILE__).'/lib/function.php';

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $now = date('Y-m-d H:i:s');

  $sql = "SELECT * FROM user_mch_submit WHERE applyment_state NOT IN ('FINISH','FROZEN', 'REJECTED')";
  $data = $db->fetch_array($sql);
  if ($data) {
    foreach ($data as $row) {
      $submitId = $row['id'];
      $applymentId = $row['applyment_id'];
      $mobile = $row['mobile'];
      $uid    = $row['uid'];
      $formId = $row['formId'];
      $merchantName = $row['merchant_shortname'];
      $mchType      = $row['mch_type'];
      $marketingType= $row['marketing_type'];

      if ('general' == $mchType) {
        //未对接支付商户，随机生成一个商户号
        $subMchId = rand(80000000, 99999999);
        $sql = "UPDATE user_mch_submit SET sub_mch_id = $subMchId, applyment_state = 'FINISH', updated_at = '$now' WHERE id = '$submitId'";
        $db->query($sql);

        $appId = SUISHOUHUI_APP_ID;
        $sql = "INSERT INTO mchs(appid, mch_id, merchant_name, mch_type, marketing_type, created_at) VALUES ('$appId', $subMchId, '$merchantName', 'general', 'marketing', '$now')";
        $db->query($sql);

        updateShop($merchantName, $subMchId);
        //创建会员卡
        createMemberCard($subMchId);
        //创建加会员小程序码
        createMchQrcode($subMchId);
        //初始化商户营销规则
        initMchRules($subMchId, $merchantName);
        //发出使用通知
        //$state = '审核通过';
        //$comment = '请点此去设置会员基本权益和营销活动';
        //sendNotice($state, $comment);
        continue;
      } else if ('getihu' == $mchType) {
        $subMchId = $row['sub_mch_id'];
        if (!$subMchId) {
          //商户号正在申请中，先随机生成一个商户号
          $subMchId = rand(80000000, 99999999);
        }
        $sql = "UPDATE user_mch_submit SET sub_mch_id = $subMchId, applyment_state = 'FINISH', updated_at = '$now' WHERE id = '$submitId'";
        $db->query($sql);

        $sql = "SELECT id, appid FROM apps WHERE user_mch_submit_id = $submitId";
        $r   = $db->fetch_row($sql);
        if ($r['appid']) {
          $appId = $r['appid'];
          $sql = "UPDATE apps SET mch_id = $subMchId WHERE id = $r[id]";
          $db->query($sql);
        } else {
          $appId = SUISHOUHUI_APP_ID;
        }
        $expiredAt = date('Y-m-d', strtotime('+1 years'));
        $sql = "INSERT INTO mchs(appid, mch_id, merchant_name, mch_type, marketing_type, expired_at, created_at) VALUES ('$appId', $subMchId, '$merchantName', 'getihu', '$marketingType', '$expiredAt', '$now')";
        $db->query($sql);
        if ('groupon' == $marketingType) {
          $sql = "UPDATE mchs SET is_groupon = 1, is_timing = 1, is_distribute = 1, is_together = 1 WHERE mch_id = $subMchId";
          $db->query($sql);
        }
        echo 'insert mchs '.PHP_EOL;
        //创建门店
        updateShop($merchantName, $subMchId);
        //创建会员卡
        //createMemberCard($subMchId);
        //创建加会员小程序码
        createMchQrcode($subMchId);
        //初始化商户营销规则
        initMchRules($subMchId, $merchantName);
        //创建收款码
        createGetihuPayCounter($subMchId, $merchantName);
        //发出使用通知
        //$state = '审核通过';
        //$comment = '请点此去设置会员基本权益和营销活动';
        //sendNotice($state, $comment);
        continue;
      }

      $url = 'https://api.mch.weixin.qq.com/applyment/micro/getstate';
      $nonceStr = getNonceStr();
      $data = array('version'=>'1.0', 'mch_id'=>MCH_ID, 'nonce_str'=>$nonceStr, 'sign_type'=>'HMAC-SHA256', 'applyment_id'=>$applymentId);
      //签名
      $sign = MakeSign($data, 'HMAC-SHA256');
      $data['sign'] = $sign;
      $xml = ToXml($data);
      $response = postXmlCurl($xml, $url, true, 6);
      $retData = FromXml($response);
      if ($retData['return_code'] == 'SUCCESS' && $retData['return_msg'] == 'OK') {
        $sql = "UPDATE user_mch_submit SET ";

        $applymentState = $retData['applyment_state'];
        $applymentStateDesc = $retData['applyment_state_desc'];

        $sql .= " applyment_state = '$applymentState', applyment_state_desc = '$applymentStateDesc',";
        if ('TO_BE_SIGNED' == $applymentState) {
          $subMchId = $retData['sub_mch_id'];
          $signUrl  = $retData['sign_url'];

          $sql .= " sub_mch_id = '$subMchId', sign_url = '$signUrl',";
        } else if ('REJECTED' == $applymentState) {
          $array = json_decode($retData['audit_detail'], true);
          $auditDetail = $array['audit_detail'][0]['reject_reason'];
          $sql .= " audit_detail = '$auditDetail',";
        }
        $sql .= " updated_at = '$now' WHERE applyment_id = '$applymentId'";
        $db->query($sql);

        if ('TO_BE_SIGNED' == $applymentState) {
          $sql = "INSERT INTO mchs(mch_id, merchant_name, mch_type, marketing_type, created_at) VALUES ($subMchId, '$merchantName', 'xiaowei', 'pay', '$now')";
          $db->query($sql);
        } else if ('REJECTED' == $applymentState) {
          $state   = '申请失败';
          $comment = $auditDetail."\n请点此重新申请";
          sendNotice($state, $comment);
        } else if ('FINISH' == $applymentState) {
          $subMchId = $retData['sub_mch_id'];
          //完成与小程序随手惠生活的绑定
          $response = addSubDevConfig($subMchId);
          echo 'bind mchid '.$response.PHP_EOL;

          updateShop($merchantName, $subMchId);

          //创建会员卡
          createMemberCard($subMchId);
          //创建加会员小程序码
          createMchQrcode($subMchId);
          //初始化商户营销规则
          initMchRules($subMchId, $merchantName);
          //创建收款码
          createPayCounter($subMchId, $merchantName);
          //发出使用通知
          $state = '审核通过';
          $comment = '请点此去设置会员基本权益和营销活动';
          sendNotice($state, $comment);
        }
        echo $applymentId . ' ' . $applymentState.PHP_EOL;
      }
    }
  }

  function addSubDevConfig($subMchId)
  {
    $url = 'https://api.mch.weixin.qq.com/secapi/mch/addsubdevconfig';
    $data = array('appid'=>KEYOU_MP_APP_ID, 'mch_id'=>MCH_ID, 'sub_mch_id'=>$subMchId, 'sub_appid'=>SUISHOUHUI_APP_ID);
    //签名
    $sign = MakeSign($data);
    $data['sign'] = $sign;
    $xml = ToXml($data);
    $response = postXmlCurl($xml, $url, true, 6);
    return $response;
  }
  
  function updateShop($merchantName, $mchId)
  {
    global $db;
    $sql = "UPDATE shops SET mch_id = $mchId WHERE business_name = '$merchantName' LIMIT 1";
    $db->query($sql);
  }

  //@todo cardid创建成功后，须要写入redis的keyou_mch_card里
  function createMemberCard($mchId) 
  {
    global $redis, $db;

    $now = date('Y-m-d H:i:s');
    $sql = "SELECT * FROM shops WHERE mch_id = '$mchId'";
    $row = $db->fetch_row($sql);
    $brandName    = $row['business_name'];
    $servicePhone = $row['telephone'];
    $logoUrl      = $row['logo_url'];
    $wechatShopId = $row['poi_id'];
    //@todo prerogative 写死在这儿
    $cardData['card'] = array(
      'card_type'   => 'MEMBER_CARD',
      'member_card' => array(
                        'prerogative' => urlencode('享受会员积分换好礼'), //会员卡特权说明
                        'wx_activate' => true,
                        'wx_activate_after_submit' => true,
                        'activate_app_brand_user_name' => 'gh_086721edf255@app',
                        'activate_app_brand_pass' => 'pages/index/get_membercard',
                        'supply_bonus' => true,
                        'supply_balance' => false,
                        'bonus_url' => 'https://coupons.keyouxinx.com/myPoint.php',
                        'bonus_app_brand_user_name' => 'gh_086721edf255@app',
                        'bonus_app_brand_pass' => 'pages/vip/point_history',
                        'custom_field1' => array(
                                              'name_type'=>'FIELD_NAME_TYPE_COUPON',
                                              'url' => 'https://coupons.keyouxinxi.com/myCoupon.php',
                                              'app_brand_user_name' => 'gh_086721edf255@app',
                                              'app_brand_pass' => 'pages/coupon/list'
                                            ),
                        'custom_field2' => array(
                                              'name_type'=>'FIELD_NAME_TYPE_LEVEL',
                                              'url' => 'https://coupons.keyouxinxi.com/myLevel.php',
                                              'app_brand_user_name' => 'gh_086721edf255@app',
                                              'app_brand_pass' => 'pages/vip/grade'
                                            ),
                        'base_info'     => array(
                                                  'logo_url' => $logoUrl,
                                                  'brand_name' => urlencode($brandName),
                                                  'code_type' => 'CODE_TYPE_QRCODE',
                                                  'title'     => urlencode('会员卡'),
                                                  'color'     => getCardColor(),
                                                  'notice'    => urlencode('出示会员卡'),
                                                  'service_phone' => $servicePhone,
                                                  'description'   => urlencode('在门店消费享受返积分。具体详情请咨询门店。'),
                                                  'date_info'     => array('type' => 'DATE_TYPE_PERMANENT'),
                                                  'sku'           => array('quantity' => 100000),
                                                  'get_limit'     => 1,
                                                  'use_custom_code' => false,
                                                  'can_give_friend' => false,
                                                  'can_share'       => false,
                                                  'location_id_list' => array($wechatShopId),
                                                  'custom_url_name' => urlencode('储值余额'),
                                                  'custom_url'      => 'https://coupons.keyouxinxi.com/myBalance.php',
                                                  'custom_url_sub_title' => urlencode('在线充值'),
                                                  'custom_app_brand_user_name' => 'gh_086721edf255@app',
                                                  'custom_app_brand_pass'      => 'pages/recharge/list',
                                                  'promotion_url_name' => urlencode('积分兑换'),
                                                  'promotion_url'      => 'https://coupons.keyouxinxi.com/exchange.php',
                                                  'promotion_app_brand_user_name' => 'gh_086721edf255@app',
                                                  'promotion_app_brand_pass'      => 'pages/point/list',
                                                  'pay_info' => array(
                                                     'swipe_card'=> array('is_swipe_card'=>true)
                                                  )
                                                )
                       )
    );

    $wxAccessToken = $redis->hget('keyouxinxi', 'wx_access_token');
    $url = 'https://api.weixin.qq.com/card/create?access_token='.$wxAccessToken;
    $ret = httpPost($url, urldecode(json_encode($cardData)));
    $r   = json_decode($ret, true);
    $cardId = $r['card_id'];
    $redis->hset('keyou_card_mch', $cardId, $mchId);
    $redis->hset('keyou_mch_card', $mchId, $cardId);
    echo 'create membercard result '.$ret.PHP_EOL;

    $sql = "UPDATE user_mch_submit SET member_cardid = '$cardId' WHERE sub_mch_id = $mchId";
    $db->query($sql);

    //激活会员卡
    $url = 'https://api.weixin.qq.com/card/membercard/activateuserform/set?access_token='.$wxAccessToken;
    $data = array(
                'card_id' => $cardId,
                'required_form' => array(
                                    'can_modify' => false,
                                    'common_field_id_list' => array(
                                                                'USER_FORM_INFO_FLAG_NAME',
                                                                'USER_FORM_INFO_FLAG_MOBILE'
                                                              )
                                  ),
               'optional_form' => array(
                                   'can_modify' => false,
                                   'common_field_id_list' => array('USER_FORM_INFO_FLAG_BIRTHDAY')
                                  )
              );
    $ret = httpPost($url, json_encode($data));
    echo 'activateuser form result '.$ret.PHP_EOL;
  }

  function createMchQrcode($mchId)
  {
    global $db, $redis;

    $sql = "SELECT appid FROM apps WHERE mch_id = $mchId";
    $r   = $db->fetch_row($sql);
    if ($r['appid']) {
      $appId = $r['appid'];
      $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId); //独立小程序ACCESSTOKEN
    } else {
      $appId = SUISHOUHUI_APP_ID;
      $miniAccessToken = $redis->hget('keyou_mini', 'access_token');//随手惠生活ACCESSTOKEN
    }

    $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
    $data = array('path'=>'pages/index/get_membercard?mch_id='.$mchId);
    $buffer = sendHttpRequest($url, $data);

    $filename = substr(md5('membercard_'.$mchId.time()), 8, 16);
    $object   = 'xiaowei/'.date('Ymd').'/'.$filename.'.png';
    $cardUrl  = putOssObject($object, $buffer);
    $sql = "UPDATE shops SET card_url = '$cardUrl' WHERE mch_id = $mchId";
    $db->query($sql);
  }

  function initMchRules($mchId, $merchantName)
  {
    global $db, $redis;
    $now = date('Y-m-d H:i:s');
    //创建初始会员卡等级信息
    $sql = "INSERT INTO app_grades (mch_id, name, `condition`, catch_type, catch_value, privilege, grade, created_at) VALUES ($mchId, '普通卡','扫码完善资料即可加入', 'scan', 0, '', 1, '$now')";
    $db->query($sql);

    //创建初始积分规则
    $sql = "INSERT INTO app_point_rules(mch_id, award_need_consume, can_used_for_money, exchange_need_points, recharge_point_speed, created_at) VALUES ($mchId, 1, 0, 0, 0, '$now')";
    $db->query($sql);
    $pointData = array('mch_id'=>$mchId, 'award_need_consume'=>1, 'can_cash'=>false,'exchange_need_points'=>0, 'recharge_point_speed'=>0);
    $redis->hset('keyou_mch_point_rules', $mchId, serialize($pointData));
  }

  function createPayCounter($mchId, $merchantName)
  {
    global $db, $redis;
    $now = date('Y-m-d H:i:s');
    //创建自助买单、收款码
    $counterData = array('self'=>'自助买单', 'scan'=>$merchantName);
    foreach ($counterData as $payType=>$payName) {
      $counter = getPayCounter();
      $sql = "INSERT INTO app_counters (mch_id, merchant_name, counter, counter_type, name, created_at) VALUES ($mchId, '$merchantName', $counter, '$payType', '$payName', '$now')";
      $db->query($sql);
    }

    //收款码制作成普通二维码，方便支付宝也可以扫一扫支付
    $payUrl = 'https://coupons.keyouxinxi.com/h5pay.php?counter='.$counter;
    $qrCodeUrl = createQrCode($payUrl);

    $sql = "UPDATE app_counters SET qrcode_url = '$qrCodeUrl' WHERE counter = $counter";
    $db->query($sql);
  }

  function createGetihuPayCounter($mchId, $merchantName)
  {
    global $db, $redis;
    $now = date('Y-m-d H:i:s');
    //创建自助买单、收款码
    $counterData = array('self'=>'自助买单', 'scan'=>$merchantName);
    foreach ($counterData as $payType=>$payName) {
      $counter = getPayCounter();
      $sql = "INSERT INTO app_counters (mch_id, merchant_name, counter, counter_type, name, created_at) VALUES ($mchId, '$merchantName', $counter, '$payType', '$payName', '$now')";
      $db->query($sql);
    }

    //收款码制作成普通二维码，方便支付宝也可以扫一扫支付
    $payUrl = 'http://template.keyouxinxi.com/mini/h5pay.php?counter='.$counter;
    $qrCodeUrl = createQrCode($payUrl);

    $sql = "UPDATE app_counters SET qrcode_url = '$qrCodeUrl' WHERE counter = $counter";
    $db->query($sql);
  }

  function sendNotice($state, $comment)
  {
    global $db, $redis, $uid, $formId, $merchantName;
  
    $sql = "SELECT openid FROM tuitui_users WHERE id = $uid";
    $row = $db->fetch_row($sql);
    $openId = $row['openid'];

    $miniAccessToken = $redis->hget('keyou_mini', 'tuitui_access_token');
    $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$miniAccessToken;
    $data = array(
              'touser' => $openId,
              'template_id' => 'hcHLeVY4s2_BkiwKEVXjbapbXK6D9hjZiPwkziWYXAk',
              'page'        => 'pages/index/index',
              'form_id'     => $formId,
              'data'        => array(
                                  'keyword1' => array('value'=>$merchantName),
                                  'keyword2' => array('value'=>'收款+会员营销'),
                                  'keyword3' => array('value'=>$state),
                                  'keyword4' => array('value'=>$comment),
                               )
            );

    $ret = httpPost($url, json_encode($data));
    echo 'send mini notice result '.$ret.PHP_EOL;
  }

  //随机返回卡券的颜色值
  function getCardColor()
  {
    $colorData = array('Color010', 'Color020', 'Color030', 'Color040', 'Color050', 'Color060', 'Color070', 'Color080', 'Color090', 'Color100');
    return $colorData[array_rand($colorData, 1)];
  }

  function createQrCode($url)
  {
    include('./lib/phpqrcode/phpqrcode.php'); 
    // 二维码数据 
    $errorCorrectionLevel = 'L';  
    // 点的大小：1到10 
    $matrixPointSize = 8;
    // 生成的文件名 
    $fileName = substr(md5(uniqid(microtime(true),true)), 8, 16);
    $buffer = '/mnt/tmp/qrcodes/'.$fileName.'.png'; 
    QRcode::png($url, $buffer, $errorCorrectionLevel, $matrixPointSize, 2); 

    $object = 'selfpayqrcode/'.date('Ymd').'/'.$fileName.'.png';
    $qrCodeUrl = putOssObject($object, file_get_contents($buffer));
    unlink($buffer);

    return $qrCodeUrl;
  }

  function getPayCounter()
  {
    global $db;
    $counter  = rand(10000000, 99999999);
    $sql = "SELECT id FROM app_counters WHERE counter = $counter";
    $row = $db->fetch_row($sql);
    if ($row['id']) {
      getPayCounter();
    } else {
      return $counter;
    }
  }
