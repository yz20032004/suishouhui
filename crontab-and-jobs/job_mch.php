<?php
  /*
   * 商户业务操作
   */
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/WxPay.Config.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  while (true) {
    $result = $redis->blpop('keyou_mch_job_list', 1800);
    if ($result) {
      $message = unserialize($result[1]);
      $job  = $message['job'];
      switch ($job) {
        case 'micro_apply_submit':
          micro_apply_sumit($message);
          break;
        case 'send_sound':
          send_sound($message);
          break;
        case 'send_sound_rechargenopay':
          send_sound_rechargenopay($message);
          break;
        case 'create_coupon':
          create_coupon($message);
          break;
        case 'update_mch_function':
          update_mch_function($message);
          break;
        case 'waimai_notice':
          waimai_notice($message);
          break;
        case 'init_general_merchant':
          createMemberCard($message);
          createMchQrcode($message);
          createScanCounter($message);
          initMchRules($message);
          break;
        case 'createMemberCard':
          createMemberCard($message);
          break;
        case 'init_qywork_external_tags':
          init_qywork_external_tags($message);
          break;
        default:
          break;
      }
    }
  }

  function update_mch_function($data)
  {
    global $db;
    $appId     = $data['appid'];
    $mchId     = $data['mch_id'];
    $openId    = $data['openid'];
    $totalFee  = $data['total_fee'];
    $outTradeNo  = $data['out_trade_no'];
    $transactionId  = $data['transaction_id'];
    $bankType       = $data['bank_type'];
    $createdAt      = getDateAt($data['time_end']);
    $prepayId = $data['prepay_id'];

    $attach    = $data['attach'];
    $attachData = explode(',', $attach);
    $subOpenId  = $attachData[1];
    $functionName = $attachData[2];
    $subMchId     = $attachData[3];

    $detail    = '购买新功能'.$functionName;
    $trade     = $totalFee/100;
    $serviceFee = $totalFee * 0.006 >= 1 ? $totalFee * 0.006 : 0;
    $sql = "INSERT INTO app_wechat_pays (appid, mch_id, openid, out_trade_no, transaction_id, prepay_id, bank, trade, total_fee, service_fee, detail, created_at) VALUES ('$appId', '$subMchId', '$subOpenId', '$outTradeNo', '$transactionId', '$prepayId', '$bankType', '$trade', '$totalFee', $serviceFee, '$detail', '$createdAt')";
    $db->query($sql);

    $sql = "UPDATE mchs SET $functionName = 1 WHERE mch_id = $subMchId";
    $db->query($sql);

    $sql = "INSERT INTO logs (mch_id, openid, message, created_at) VALUES ($subMchId, '$subOpenId', '$detail', '$createdAt')";
    $db->query($sql);
  }

  function createMchQrcode($data)
  {
    global $db;
    $mchId = $data['mch_id'];
    $miniAccessToken = $data['mini_access_token'];//随手惠生活ACCESSTOKEN
    $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
    $data = array('path'=>'pages/index/get_membercard?mch_id='.$mchId);
    $buffer = sendHttpRequest($url, $data);

    $filename = substr(md5('membercard_'.$mchId.time()), 8, 16);
    $object   = 'xiaowei/'.date('Ymd').'/'.$filename.'.png';
    $cardUrl  = putOssObject($object, $buffer);
    $sql = "UPDATE shops SET card_url = '$cardUrl' WHERE mch_id = $mchId";
    $db->query($sql);
  }

  function createScanCounter($data)
  {
    global $db;
    $now = date('Y-m-d H:i:s');
    $mchId = $data['mch_id'];
    $counter  = rand(10000000, 99999999);

    $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
    $ret = $db->fetch_row($sql);
    $merchantName = $ret['merchant_name'];

    $sql = "INSERT INTO app_counters (mch_id, merchant_name, counter, counter_type, name, created_at) VALUES ($mchId, '$merchantName', $counter, 'scan', '$merchantName', '$now')";
    $db->query($sql);
  }

  function initMchRules($data)
  {
    global $db;
    $mchId = $data['mch_id'];
    $now = date('Y-m-d H:i:s');
    //创建初始会员卡等级信息
    $sql = "INSERT INTO app_grades (mch_id, name, `condition`, catch_type, catch_value, privilege, grade, created_at) VALUES ($mchId, '普通卡','扫码完善资料即可加入', 'scan', 0, '', 1, '$now')";
    $db->query($sql);

    //创建初始积分规则
    $sql = "INSERT INTO app_point_rules(mch_id, award_need_consume, can_used_for_money, exchange_need_points, recharge_point_speed, created_at) VALUES ($mchId, 1, 0, 0, 0, '$now')";
    $db->query($sql);
  }


  //@todo cardid创建成功后，须要写入redis的keyou_mch_card里
  function createMemberCard($data) 
  {
    global $db;
    $mchId = $data['mch_id'];
    $wxAccessToken = $data['wx_access_token'];

    $now = date('Y-m-d H:i:s');
    $sql = "SELECT * FROM shops WHERE mch_id = '$mchId'";
    $row = $db->fetch_row($sql);
    $brandName    = $row['business_name'];
    $servicePhone = $row['telephone'];
    $logoUrl      = $row['logo_url'];
    $wechatShopId = '';
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

    $url = 'https://api.weixin.qq.com/card/create?access_token='.$wxAccessToken;
    $ret = httpPost($url, urldecode(json_encode($cardData)));
    $r   = json_decode($ret, true);
    $cardId = $r['card_id'];
    echo 'create membercard result '.$ret.PHP_EOL;

    $sql = "UPDATE user_mch_submit SET member_cardid = '$cardId' WHERE sub_mch_id = $mchId";
    $db->query($sql);
    exec('/usr/local/php/bin/php update_mch_card.php '.$mchId.' '.$cardId, $output);
    print_r($output);

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

  function init_qywork_external_tags($data)
  {
    global $db;
    $mchId = $data['mch_id'];
    $gradeData = array();
    $sql   = "SELECT name FROM app_grades WHERE mch_id = $mchId ORDER BY grade DESC";
    $r = $db->fetch_array($sql);
    foreach ($r as $i=>$row) {
      $gradeData[] = array('name' => $row['name'], 'order'=>$i);
    }
    $gradeData[] = array('name'=>'粉丝', 'order'=>$i+1);
    $accessToken = $data['access_token'];
    $url = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_corp_tag?access_token='.$accessToken;
    if ($gradeData) {
      $tagData = array(
                    'group_name' => '会员等级',
                    'tag'        => $gradeData
                  );
      $ret = sendHttpRequest($url, $tagData);
      echo $ret.PHP_EOL;
    }
    $tagData = array(
                  'group_name' => '最近消费',
                  'tag'        => array(
                                    array('name' => '一周内', 'order'=>8),
                                    array('name' => '两周内', 'order'=>7),
                                    array('name' => '一个月内', 'order'=>6),
                                    array('name' => '两个月内', 'order'=>5),
                                    array('name' => '三个月内', 'order'=>4),
                                    array('name' => '半年以内', 'order'=>3),
                                    array('name' => '一年以内', 'order'=>2),
                                    array('name' => '一年以上', 'order'=>1)
                                  )
                );
    $ret = sendHttpRequest($url, $tagData);
    echo $ret.PHP_EOL;
    $tagData = array(
                  'group_name' => '累积消费金额',
                  'tag'        => array(
                                    array('name' => '5百元以内', 'order'=>8),
                                    array('name' => '2千元以内', 'order'=>7),
                                    array('name' => '5千元以内', 'order'=>6),
                                    array('name' => '1万元以内', 'order'=>5),
                                    array('name' => '2万元以内', 'order'=>4),
                                    array('name' => '5万元以内', 'order'=>3),
                                    array('name' => '10万元以内', 'order'=>2),
                                    array('name' => '10万元以上', 'order'=>1)
                                  )
                );
    $ret = sendHttpRequest($url, $tagData);
    echo $ret.PHP_EOL;
    $tagData = array(
                  'group_name' => '累积消费次数',
                  'tag'        => array(
                                    array('name' => '0次', 'order'=>7),
                                    array('name' => '1次', 'order'=>6),
                                    array('name' => '2~4次', 'order'=>5),
                                    array('name' => '5~10次', 'order'=>4),
                                    array('name' => '11~20次', 'order'=>3),
                                    array('name' => '20次~50次', 'order'=>2),
                                    array('name' => '50次以上', 'order'=>1),
                                  )
                );
    $ret = sendHttpRequest($url, $tagData);
    echo $ret.PHP_EOL;
    $tagData = array(
                  'group_name' => '年龄',
                  'tag'        => array(
                                    array('name' => '00后', 'order'=>6),
                                    array('name' => '90后', 'order'=>5),
                                    array('name' => '80后', 'order'=>4),
                                    array('name' => '70后', 'order'=>3),
                                    array('name' => '60后', 'order'=>2),
                                    array('name' => '50后', 'order'=>1),
                                  )
                );
    $ret = sendHttpRequest($url, $tagData);
    echo $ret.PHP_EOL;

  }

  function micro_apply_submit($data)
  {
    global $db;
    $openId = $data['openid'];
    $formId = $data['formId'];
    $mobile = $data['mobile'];
    $businessId = $data['business_id'];
    $logoPhotoMedia = $data['logo_photo_media'];
    $insidePhotoMedia = $data['inside_photo_media'];
    $headPhotoMedia = $data['head_photo_media'];
    $countryPhotoMedia = $data['country_photo_media'];
    $accountNumber = $data['account_number'];
    //小微商户申请银行名称没有中国前缀
    $accountBank   = str_replace('中国', '', $data['account_bank']);
    $idCardName    = $data['id_card_name'];
    $idCardNumber  = $data['id_card_number'];
    $idCardValidTime = $data['id_card_valid_time'];
    $shop            = $data['shop'];
    $bankAddressCode = $data['bank_address_code'];
    $logoUrl         = $data['logo_url'];
    $insideUrl       = $data['inside_url'];
    $headUrl         = $data['head_url'];
    $countryUrl      = $data['country_url'];
    $submitAt        = $data['submit_at'];
    $businessCode    = $data['business_code'];
    $now = date('Y-m-d H:i:s');

    $postData = array(
                  'version' => '3.0',
                  'cert_sn' => SERIAL_NO,
                  'mch_id'  => MCH_ID,
                  'nonce_str' => getNonceStr(),
                  'sign_type' => 'HMAC-SHA256',
                  'business_code' => $businessCode,
                  'id_card_copy'  => $headPhotoMedia,
                  'id_card_national' => $countryPhotoMedia,
                  'id_card_name'     => getEncrypt($idCardName),
                  'id_card_number'   => getEncrypt($idCardNumber),
                  'id_card_valid_time' => $idCardValidTime,
                  'account_name'       => getEncrypt($idCardName),
                  'account_bank'       => $accountBank,
                  'bank_address_code'  => $bankAddressCode,
                  'account_number'     => getEncrypt($accountNumber),
                  'store_name'         => $shop['name'],
                  'store_street'       => $shop['address'],
                  'merchant_shortname' => $shop['name'],
                  'service_phone'      => $shop['telephone']?$shop['telephone']:$mobile,
                  'store_entrance_pic' => $logoPhotoMedia,
                  'indoor_pic'         => $insidePhotoMedia,
                  'product_desc'       => '餐饮',
                  'rate'               => '0.38%',
                  'store_address_code' => $bankAddressCode,
                  'contact'            => getEncrypt($idCardName),
                  'contact_phone'      => getEncrypt($mobile)
                );
    $url = 'https://api.mch.weixin.qq.com/applyment/micro/submit';
    $sign = MakeSign($postData, 'HMAC-SHA256');
    $postData['sign'] = $sign;
    $xml = ToXml($postData);
    $response = postXmlCurl($xml, $url, true, 6);
    print_r($response);
    $retData = FromXml($response);
    if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
      $applymentId = $retData['applyment_id'];
      $sql = "INSERT INTO user_mch_submit (openid, mobile, dianping_business_id, id_card_name, id_card_number, id_card_valid_time, account_name, account_bank, bank_address_code, account_number, store_name, store_street, merchant_shortname, id_card_copy, id_card_national, store_entrance_pic, indoor_pic, logo_url, inside_url, head_url, country_url, applyment_id, business_code, formId, created_at) VALUES ('$openId', '$mobile', '$businessId', '$idCardName', '$idCardNumber', '$idCardValidTime', '$idCardName', '$accountBank', '$bankAddressCode', '$accountNumber', '$shop[name]', '$shop[address]', '$shop[name]', '$headPhotoMedia', '$countryPhotoMedia', '$logoPhotoMedia', '$insidePhotoMedia', '$logoUrl', '$insideUrl', '$headUrl', '$countryUrl', '$applymentId','$businessCode', '$formId', '$now')";
      $db->query($sql);

      $sql = "UPDATE users SET merchant_name = '$shop[name]' WHERE openid = '$openId'";
      $db->query($sql);
    }
  }

  function send_sound_rechargenopay($message)
  {
    global $db;
    $attach  = explode(',', $message['attach']);
    $trade   = $attach[2];
    $originTrade = $attach[3];
    $rechargeDiscount = $attach[4];
    $counter     = $attach[5];
    $outTradeNo = $message['out_trade_no'];

    $sql = "SELECT cloud_device FROM app_counters WHERE counter = $counter";
    $row = $db->fetch_row($sql);
    if (!$row['cloud_device']) {
      return;
    }
    $cloudDevice = $row['cloud_device'];

    $context = '微信收款'.$trade.'元';
    $rechargeTimes = $trade / $originTrade;
    if ($rechargeDiscount > 0) {
      $context .= ',储值'.$rechargeTimes.'倍该笔享'.$rechargeDiscount.'折';
    } else {
      $context .= ',储值'.$rechargeTimes.'倍该笔免单';
    }
    $param=array(
        'str'=> md5(uniqid(microtime(true),true)),
        'appKey'=> FEIYU_APPKEY,//替换成自己的appKey
        'type'=>"10",
        'device'=>$cloudDevice,//替换成相应的设备号
        'context' => $context,
      );
      //排序
      $ascstr = sort_asc($param);
      //md5运算并转成大写
      $finalsign = strtoupper(md5($ascstr.FEIYU_APPSECRET));//替换成自己的appSecret
      //把得到的签名放回到参数数组
      $param['sign'] = $finalsign;

      $data = json_encode($param);
      $url = "https://open.gzfyit.com/iot-cloud/v1/third/send";
      $return_info = http_post_data($url, $data);
      echo $outTradeNo.' '.$return_info.PHP_EOL;
      echo $context.PHP_EOL;
  }
     
  function send_sound($message)
  {
    global $db; 
    $trade   = $message['trade'];
    $getPoint= $message['get_point'];
    $outTradeNo = $message['trade_no'];
    $counter = $message['counter'];
    $save    = $message['save'];
    $consumeRecharge = $message['consume_recharge'];
    $payType = $message['pay_type'];

    $sql = "SELECT cloud_device FROM app_counters WHERE counter = $counter";
    $row = $db->fetch_row($sql);
    if (!$row['cloud_device']) {
      return;
    }
    $cloudDevice = $row['cloud_device'];

    $context = '';
    if ('wechat' == $payType) {
      $context .= '微信';
    } else if ('alipay' == $payType) {
      $context .= '支付宝';
    }
    $context .= '收款'.$trade.'元';
    if ($save) {
      $context .= ',会员优惠'.$save.'元';
    }
    if ($consumeRecharge) {
      $context .= ',储值支付'.$consumeRecharge.'元';
    }
    if ($getPoint > 0) {
      $context .= ',返'.$getPoint.'积分';
    }
    $param=array(
        'str'=> md5(uniqid(microtime(true),true)),
        'appKey'=> FEIYU_APPKEY,//替换成自己的appKey
        'type'=>"10",
        'device'=>$cloudDevice,//替换成相应的设备号
        'context' => $context,
      );
      //排序
      $ascstr = sort_asc($param);
      //md5运算并转成大写
      $finalsign = strtoupper(md5($ascstr.FEIYU_APPSECRET));//替换成自己的appSecret
      //把得到的签名放回到参数数组
      $param['sign'] = $finalsign;

      $data = json_encode($param);
      $url = "https://open.gzfyit.com/iot-cloud/v1/third/send";
      $return_info = http_post_data($url, $data);
      echo $outTradeNo.' '.$return_info.PHP_EOL;
      echo $context.PHP_EOL;
  }

  function create_coupon($data) {
    global $db;
    $couponId    = $data['coupon_id'];
    $accessToken = $data['access_token'];
    $miniAccessToken = $data['mini_access_token'];
    $mchId = $data['mch_id'];
    $appId = $data['appid'];
    $couponType = $data['coupon_type'];

    if ('groupon' == $couponType) {
      return false;
    }
    if (SUISHOUHUI_APP_ID != $appId) {
      //随手惠生活小程序才创建微信卡券，其它小程序不创建
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/coupon/get?coupon_id='.$couponId.'&total=1');
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('coupon_'.$mchId.$couponId.time()), 8, 16);
      $object = 'coupon/'.date('Ymd').'/'.$filename.'.png';
      $couponCodeUrl = putOssObject($object, $buffer);
  
      $sql = "UPDATE coupons SET wechat_qrcode_url = '$couponCodeUrl' WHERE id = $couponId";
      $db->query($sql);

      return false;
    }

    $sql = "SELECT name, description, discount, consume_limit, amount, validity_type, date_start, date_end, total_days, coupon_type, is_usefully_sendday, deal_detail FROM coupons WHERE id = $couponId";
    $row = $db->fetch_row($sql);

    $couponTitle = $row['name'];
    $description = $row['description'];
    $discount    = $row['discount'] * 10;
    $leastCost   = $row['consume_limit'] * 100;
    $reduceCost  = $row['amount'] * 100;
    $validityType= $row['validity_type'];
    $dateStart   = $row['date_start'];
    $dateEnd     = $row['date_end'];
    $dateTotal   = $row['total_days'];
    $couponType  = $row['coupon_type'];
    $dealDetail  = $row['deal_detail'];
    switch ($couponType) {
      case 'cash':
        $cardType = 'CASH';
        break;
      case 'waimai':
        $cardType = 'CASH';
        $couponType = 'cash';
        break;
      case 'mall':
        $cardType = 'CASH';
        $couponType = 'cash';
        break;
      case 'discount':
        $cardType = 'DISCOUNT';
        break;
      case 'gift':
        $cardType = 'GIFT';
        break;
      case 'groupon':
        $cardType = 'GROUPON';
        break;
      default:
        break;
    }
    if ($row['validity_type'] == 'hard') {
      $dateInfo = array(
                      'type' => 'DATE_TYPE_FIX_TIME_RANGE',
                      'begin_timestamp' => strtotime($row['date_start']),
                      'end_timestamp'   => strtotime($row['date_end'])
                       );
    } else {
      $dateInfo = array(
                      'type' => 'DATE_TYPE_FIX_TERM',
                      'fixed_term' => $row['total_days'],
                      'fixed_begin_term' => 0 
                      //'fixed_begin_term' => $row['is_usefully_sendday'] ? 0 : 1
                      //这里防止顾客因为充值或消费返券，当时没有领券，第二天后再领券造成当天不可用的bug
                       );
    }

    $sql = "SELECT poi_id, business_name, logo_url FROM shops WHERE mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $brandName = $row['business_name'];
    $logoUrl   = $row['logo_url'];
    $poiId     = $row['poi_id'];

    $couponData['card'] = array(
      'card_type'   => $cardType,
      $couponType   => array(
                        'base_info'          => array(
                                                  'logo_url' => $logoUrl,
                                                  'brand_name' => urlencode($brandName),
                                                  'code_type' => 'CODE_TYPE_QRCODE',
                                                  'title'     => urlencode($couponTitle),
                                                  'color'     => getCardColor(),
                                                  'notice'    => urlencode('使用时请向店员出示此券'),
                                                  'description'   => urlencode($description),
                                                  'date_info'     => $dateInfo,
                                                  'sku'           => array('quantity' => 10000),
                                                  'use_limit'     => 100,
                                                  'get_limit'     => 100,
                                                  'use_custom_code' => false,
                                                  'can_give_friend' => false,
                                                  'can_share'       => false,
                                                )
                       )

    );
    if ('cash' == $couponType) {
      $couponData['card']['cash']['reduce_cost'] = $reduceCost;
      $couponData['card']['cash']['least_cost'] = $leastCost;
    } else if ('discount' == $couponType) {
      $percentOff = 100 - $discount;
      $couponData['card']['discount']['discount'] = $percentOff;
    } else if ('gift' == $couponType) {
      $couponData['card']['gift']['gift'] = urlencode($couponTitle);
    } else if ('groupon' == $couponType) {
      $couponData['card']['groupon']['deal_detail'] = urlencode($dealDetail);
    }
    //设置券适用门店

    $couponData['card'][$couponType]['base_info']['location_id_list'] = array($poiId);
    //设置券使用限制
    if ($leastCost) {
      $couponData['card'][$couponType]['advanced_info']['use_condition'] = array('least_cost'=>$leastCost);
    }
    $url = 'https://api.weixin.qq.com/card/create?access_token='.$accessToken;
    $ret = httpPost($url, urldecode(json_encode($couponData)));
    echo 'create card '.$ret.PHP_EOL;

    $data = json_decode($ret, true);
    if ('0' == $data['errcode']) {
      $cardId = $data['card_id'];
      $sql = "UPDATE coupons SET wechat_cardid = '$cardId'";

      if ('groupon' != $couponType) {
        //团购券不生成免费领取二维码
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
        $data = array('path'=>'pages/coupon/get?coupon_id='.$couponId.'&total=1');
        $buffer = sendHttpRequest($url, $data);

        $filename = substr(md5('coupon_'.$mchId.$couponId.time()), 8, 16);
        $object = 'coupon/'.date('Ymd').'/'.$filename.'.png';
        $couponCodeUrl = putOssObject($object, $buffer);
    
        $sql .= ", wechat_qrcode_url = '$couponCodeUrl'";
      }
      $sql .= " WHERE id = $couponId";
      echo $sql.PHP_EOL;
      $db->query($sql);
    }
  }

  function waimai_notice($data)
  {
    global $db;
    $outTradeNo = $data['out_trade_no'];
    $status     = $data['change_status'];
    $mchId      = $data['mch_id'];
    $accessToken = $data['mini_access_token'];

    $sql = "SELECT openid, is_self, accept_at, delivery_at, is_accept_remind, is_delivery_remind FROM member_waimai_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    $openId = $row['openid'];
    if ('accept' == $status) {
      if ($row['is_accept_remind']) {
        $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
        $ret = $db->fetch_row($sql);
        $merchantName = $ret['merchant_name'];

        $templateId = 'AjhkjC7EskqQZ1fYH-qoefHbOP-Qiswpygx4rQrImuY';
        $templateData= array(
                        'thing1' => array('value'=>$merchantName),
                        'character_string2' => array('value'=>$outTradeNo),
                        'time3'  => array('value'=>$row['accept_at'])
                      );
      } else {
        return;
      }
    } else if ('delivery' == $status) {
      if ($row['is_delivery_remind']) {
        if ($row['is_self']) {
          //客户自提
          $sql = "SELECT business_name,address,telephone FROM shops WHERE mch_id = $mchId";
          $ret = $db->fetch_row($sql);
          $merchantName = $ret['business_name'];
          $shopAddress  = $ret['address'];
          $shopTel      = $ret['telephone'];

          $templateId = 'c-e8evLEn0UsxLKalPigrP3yMPyez0qEmnBOo2zDPIk';
          $templateData= array(
                          'thing3' => array('value'=>$merchantName),
                          'character_string4' => array('value'=>$outTradeNo),
                          'thing5' => array('value'=>mb_substr($shopAddress, 0, 8).'..'),
                          'thing2' => array('value'=>'您的商品已准备好，请您到店自提')
                        );

        } else {
          $templateId = 'EVe5Em40ho0JzCuJAhtgjnch52ZiE84zvovrGpoxcpA';
          $templateData= array(
                          'character_string1' => array('value'=>$outTradeNo),
                          'date2'  => array('value'=>$row['delivery_at']),
                          'thing3' => array('value'=>'请耐心等待')
                        );
        }
      } else {
        return;
      }
    } else {
      return;
    }

    $sql = "SELECT id FROM wechat_pays WHERE mch_id = $mchId AND pay_from = 'waimai' AND out_trade_no = '$outTradeNo'";
    $row = $db->fetch_row($sql);
    $payId = $row['id'];

    $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$accessToken;
    $postData = array(
                  'touser'       => $openId,
                  'template_id'  => $templateId,
                  'page'         => 'pages/waimai/detail?mch_id='.$mchId.'&out_trade_no='.$outTradeNo,
                  'data'         => $templateData
                );
    print_r($postData);
    $r = sendHttpRequest($url, $postData);
    echo 'send subscribe message '.$r.PHP_EOL;
  }

  //自定义ascii排序
  function sort_asc($params = array()){
      if(!empty($params)){
         $p =  ksort($params);
         if($p){
             $str = '';
             foreach ($params as $k=>$val){
                 $str .= $k .'=' . $val . '&';
             }
             $strs = rtrim($str, '&');
             return $strs;
         }
      }
      return '参数错误';
  }

  //curl请求
  function http_post_data($url, $data_string) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
      curl_setopt($ch, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
      curl_setopt($ch, CURLOPT_POST, 1); // 发送一个常规的Post请求
      curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
      curl_setopt($ch, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Content-Type: application/json; charset=utf-8",
          "Content-Length: " . strlen($data_string))
      );
      ob_start();
      $return_content = curl_exec($ch);
      // $return_content = ob_get_contents();
      ob_end_clean();
      $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      return $return_content;
  }

  //随机返回卡券的颜色值
  function getCardColor()
  {
    $colorData = array('Color010', 'Color020', 'Color030', 'Color040', 'Color050', 'Color060', 'Color070', 'Color080', 'Color090', 'Color100');
    return $colorData[array_rand($colorData, 1)];
  }

  function getEncrypt($str){
     $url = 'https://suishouh.applinzi.com/encrypt.php';
     $data = array('string'=>$str);
     $sign = httpSSLPost($url, $data);
     return $sign;
  }
