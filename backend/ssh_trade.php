<?php
  //商户操作
  require_once 'common.php';
  require_once dirname(__FILE__).'/lib/WxPay.Api.php';
  require_once dirname(__FILE__).'/unit/WxPay.JsApiPay.php';

  $action     = $_GET['action'];
  $now        = date('Y-m-d H:i:s');

  switch ($action) {
    case 'validate_coupon':
      $subMchId = $_GET['sub_mch_id'];
      $openId = $_GET['openid'];
      $code   = $_GET['code'];

      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $url = 'https://api.weixin.qq.com/card/code/consume?access_token='.$accessToken;
      $data = array('code'=>$code);
      $ret  = httpPost($url, json_encode($data));
      echo $ret;
      break;
    case 'create_qrcode':
      $subMchId = $_GET['sub_mch_id'];
      $openId = $_GET['openid'];
      $userId = $_GET['userid'];
      $username = $_GET['username'];
      $amount = $_GET['amount'];
      $qrData = $subMchId.'#'.$amount.'#'.$userId.'#'.$username.'#0';
      $scene = substr(md5($openId.$now), 8, 16);
      //test
      //$scene = 'be34b94379acc4e5';
      $redis->hset('keyou_pay_qrcodes', $scene, $qrData);
      $redis->expire('keyou_pay_qrcodes', 600);

      include('./lib/phpqrcode/phpqrcode.php'); 
      // 二维码数据 
      $data = 'http://pay.keyouxinxi.com/mini.php?action=scan&key='.$scene; 
      // 纠错级别：L、M、Q、H 
      $errorCorrectionLevel = 'L';  
      // 点的大小：1到10 
      $matrixPointSize = 8;
      // 生成的文件名 
      $filename = '/tmp/'.$scene.'.png'; 
      QRcode::png($data, $filename, $errorCorrectionLevel, $matrixPointSize, 2); 
      
      $object = 'paycode/'.date('Ymd').'/'.$subMchId.'/'.$scene.'.png';
      $wxUrl = putOssObject($object, file_get_contents($filename));
      unlink($filename);
      echo json_encode(array('path'=>$wxUrl, 'key'=>$scene));
      break;
    case 'micropay_wechat':
      $mchId    = $_GET['mch_id'];
      $merchantName = $_GET['merchant_name'];
      $userId   = $_GET['userid'];
      $username = $_GET['username'];
      $totalFee = $_GET['trade'] * 100;
      $code     = $_GET['code'];
      $shopId   = isset($_GET['shop_id'])?$_GET['shop_id']:0;
      $qrData = $mchId.'#'.$_GET['trade'].'#'.$userId.'#'.$username.'#'.$shopId;
      $key    = substr(md5($userId.$now), 8, 16);
      $redis->hset('keyou_pay_qrcodes', $key, $qrData);
      $redis->expire('keyou_pay_qrcodes', 600);

      $body = $merchantName;
      $outTradeNo = getOutTradeNo();
      $attach = 'pay,'.$key;
      $data = array(
                 'appid'   => KEYOU_MP_APP_ID,
                 'sub_appid' => SUISHOUHUI_APP_ID,
                 'mch_id'  => MCHID,
                 'sub_mch_id' => $mchId,
                 'nonce_str'  => getNonceStr(),
                 'sign_type'  => 'MD5',
                 'body'       => $body,
                 'attach'     => $attach,
                 'out_trade_no' => $outTradeNo,
                 'total_fee'    => $totalFee,
                 'auth_code'       => $code,
                 'spbill_create_ip' => $_SERVER['REMOTE_ADDR']
                 );
      $sign = MakeSign($data);
      $data['sign'] = $sign;
      $xml = ToXml($data);
      //统一下单
      $url = "https://api.mch.weixin.qq.com/pay/micropay";
      $response = postXmlCurl($xml, $url, false, 6);
      $ret = FromXml($response);
      if ($ret['return_code'] == 'SUCCESS' && isset($ret['transaction_id'])) {
        $subOpenId = $ret['sub_openid'];
        $getPoint = 0;
        $isMember = false;
        $sql = "SELECT grade FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
        $row = $db->fetch_row($sql);
        if ($row['grade'] > 0) {
          $isMember = true;
          $pointRules = unserialize($redis->hget('keyou_mch_point_rules', $mchId));
          $sql = "SELECT point_speed FROM app_grades WHERE mch_id = $mchId AND grade = $row[grade]";
          $r = $db->fetch_row($sql);
          if ($r['point_speed']) {
            $getPoint = floor($r['point_speed'] * $_GET['trade'] / $pointRules['award_need_consume']);
          }
        }
        $message= array(
              'trade'        => $_GET['trade'],
              'get_point'    => $getPoint,
              'consume'      => round($ret['cash_fee']/100,2),
              'use_point'         => 0,
              'reduce'            => 0,
              'save'              => 0,
              'discount'          => 0,
              'member_discount'   => 0,
              'prepay_id'         => 0,
              'is_member'         => $isMember
            );
        $redis->hset('keyou_pay_result', $key, serialize($message));

        $ret['pay_info'] = $redis->hget('keyou_pay_qrcodes', $key);
        $data = array_merge($ret, $message);
        //分离入库操作
        $redis->rpush('keyou_trade_list', serialize($data));
        //分享会员操作
        $data['job'] = 'update_member';
        $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
        $data['wx_access_token']= $accessToken;
        $redis->rpush('member_job_list', serialize($data));
      }
      $ret['out_trade_no'] = $outTradeNo;
      $ret['key'] = $key;
      echo json_encode($ret);
      break;
    case 'get_alipay_trade_no':
      require_once 'lib/alipay/f2fpay/model/builder/AlipayTradePayContentBuilder.php';
      require_once 'lib/alipay/f2fpay/service/AlipayTradeService.php';

      $mchId = $_GET['mch_id'];
      $trade = $_GET['trade'];
      $outTradeNo = getOutTradeNo();
      $totalAmount = $_GET['trade'];
      $body = '随手惠线下体验店';
      //账户中心-合作伙伴管理-PID
      $pId = "2088811889755859"; 
      // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，详情请咨询支付宝技术支持
      $extendParams = new ExtendParams();
      $extendParams->setSysServiceProviderId($pId);
      $extendParamsArr = $extendParams->getExtendParams();
      $timeExpress = "5m";
      //第三方应用授权令牌,商户授权系统商开发模式下使用
      $appAuthToken = '201908BB2b077cbce82645de8ce0f8066e005X56';
      // 创建请求builder，设置请求参数
      $barPayRequestBuilder = new AlipayTradePayContentBuilder();
      $barPayRequestBuilder->setOutTradeNo($outTradeNo);
      $barPayRequestBuilder->setTotalAmount($totalAmount);
      $barPayRequestBuilder->setTimeExpress($timeExpress);
      $barPayRequestBuilder->setSubject($body);
      $barPayRequestBuilder->setExtendParams($extendParamsArr);
      $barPayRequestBuilder->setAppAuthToken($appAuthToken);
      // 调用barPay方法获取当面付应答
      $service = new AlipayTradeService($config);
      $barPayResult = $service->qrPay($barPayRequestBuilder);
      $response= json_encode($barPayResult->getResponse());
      $responseData = json_decode($response, true);
      echo $responseData['a'];
      break;
    case 'micropay_alipay':
      $mchId    = $_GET['mch_id'];
      $merchantName = $_GET['merchant_name'];
      $userId   = $_GET['userid'];
      $username = $_GET['username'];
      $totalFee = $_GET['trade'] * 100;
      $code     = $_GET['code'];
      $shopId   = isset($_GET['shop_id'])?$_GET['shop_id']:0;

      $qrData = $mchId.'#'.$_GET['trade'].'#'.$userId.'#'.$username.'#'.$shopId;
      $key    = substr(md5($userId.$now), 8, 16);
      $redis->hset('keyou_pay_qrcodes', $key, $qrData);
      $redis->expire('keyou_pay_qrcodes', 600);

      $body = $merchantName;
      $outTradeNo = getOutTradeNo();

      //支付宝刷卡消费
      require_once 'lib/alipay/f2fpay/model/builder/AlipayTradePayContentBuilder.php';
      require_once 'lib/alipay/f2fpay/service/AlipayTradeService.php';
      $totalAmount = $_GET['trade'];
      //$sellerId = 'qcm_work@126.com';
      //账户中心-合作伙伴管理-PID
      $pId = "2088811889755859"; 

      // 业务扩展参数，目前可添加由支付宝分配的系统商编号(通过setSysServiceProviderId方法)，详情请咨询支付宝技术支持
      $providerId = ""; //系统商pid,作为系统商返佣数据提取的依据
      $extendParams = new ExtendParams();
      $extendParams->setSysServiceProviderId($pId);
      $extendParamsArr = $extendParams->getExtendParams();
      //商户操作员编号，添加此参数可以为商户操作员做销售统计
      // 支付超时，线下扫码交易定义为5分钟
      $timeExpress = "5m";
      //第三方应用授权令牌,商户授权系统商开发模式下使用
      $appAuthToken = '201908BB2b077cbce82645de8ce0f8066e005X56';
      // 创建请求builder，设置请求参数
      $barPayRequestBuilder = new AlipayTradePayContentBuilder();
      $barPayRequestBuilder->setOutTradeNo($outTradeNo);
      $barPayRequestBuilder->setTotalAmount($totalAmount);
      $barPayRequestBuilder->setAuthCode($code);
      $barPayRequestBuilder->setTimeExpress($timeExpress);
      $barPayRequestBuilder->setSubject($body);
      //$barPayRequestBuilder->setSellerId($sellerId);
      $barPayRequestBuilder->setExtendParams($extendParamsArr);
      $barPayRequestBuilder->setAppAuthToken($appAuthToken);
      // 调用barPay方法获取当面付应答
      $barPay = new AlipayTradeService($config);
      $barPayResult = $barPay->barPay($barPayRequestBuilder);
      $result  = $barPayResult->getTradeStatus();
      $response= json_encode($barPayResult->getResponse());
      $responseData = json_decode($response, true);
      if ('SUCCESS' == $result) {
        $message= array(
              'trade'        => $_GET['trade'],
              'get_point'    => 0,
              'consume'      => $_GET['trade'],
              'use_coupon_id'=> 0,
              'use_coupon_amount' => 0,
              'use_coupon_name'   => '',
              'consume_recharge'  => 0,
              'consume_point'     => 0,
              'point_amount'      => 0,
              'use_point'         => 0,
              'reduce'            => 0,
              'save'              => 0,
              'discount'          => 0,
              'member_discount'   => 0,
              'prepay_id'         => 0,
            );
        $redis->hset('keyou_pay_result', $key, serialize($message));
        $alipayData = array(
                        'sub_appid' => 'wxaa02c1c97542b1e4',
                        'sub_mch_id'=> $mchId,
                        'pay_type'  => 2,
                        'total_fee' => $totalFee,
                        'cash_fee'  => $responseData['buyer_pay_amount'] * 100,
                        'attach'    => implode(',', array('pay', $key)),
                        'pay_info'  => $redis->hget('keyou_pay_qrcodes', $key),
                        'is_member' => false,
                        'openid'       => $responseData['buyer_user_id'],
                        'sub_openid'   => $responseData['buyer_logon_id'],
                        'out_trade_no' => $outTradeNo,
                        'transaction_id' => $responseData['trade_no'],
                        'bank_type'      => $responseData['fund_bill_list'][0]['fund_channel'],
                        'time_end'       => date('YmdHis', strtotime($responseData['gmt_payment'])),
                      );
        $data = array_merge($alipayData, $message);
        //分离入库操作
        $redis->rpush('keyou_trade_list', serialize($data));
      }
      $responseData['key'] = $key;
      $responseData['out_trade_no'] = $outTradeNo;
      echo json_encode($responseData);
      break;
    case 'micropay_query':
      //微信订单查询
      $mchId = $_GET['mch_id'];
      $key   = $_GET['key'];
      $trade = $_GET['trade'];
      $outTradeNo = $_GET['out_trade_no'];
      $data = array(
                 'appid'   => KEYOU_MP_APP_ID,
                 'sub_appid' => SUISHOUHUI_APP_ID,
                 'mch_id'  => MCHID,
                 'sub_mch_id' => $mchId,
                 'out_trade_no' => $outTradeNo,
                 'nonce_str'  => getNonceStr()
                 );
      $sign = MakeSign($data);
      $data['sign'] = $sign;
      $xml = ToXml($data);
      //查询
      $url = "https://api.mch.weixin.qq.com/pay/orderquery";
      $response = postXmlCurl($xml, $url, false, 6);
      $ret = FromXml($response);

      if ($ret['return_code'] == 'SUCCESS' && isset($ret['transaction_id'])) {
        $message= array(
              'trade'        => $_GET['trade'],
              'get_point'    => 0,
              'consume'      => round($ret['cash_fee']/100,2),
              'use_coupon_id'=> isset($ret['use_coupon_id'])?$ret['use_coupon_id']:0,
              'use_coupon_amount' => isset($ret['use_coupon_amount'])?$ret['use_coupon_amount']:0,
              'use_coupon_name'   => isset($ret['use_coupon_name'])?$ret['use_coupon_name']:'',
              'consume_recharge'  => isset($ret['consume_recharge'])?$ret['consume_recharge']:0,
              'consume_point'     => isset($ret['consume_point'])?$ret['consume_point']:0,
              'point_amount'      => isset($ret['point_amount'])?$ret['point_amount']:0,
              'use_point'         => isset($ret['use_point'])?$ret['use_point']:0,
              'reduce'            => 0,
              'save'              => 0,
              'discount'          => 0,
              'member_discount'   => 0,
              'prepay_id'         => 0
            );
        $redis->hset('keyou_pay_result', $key, serialize($message));

        $ret['pay_info'] = $redis->hget('keyou_pay_qrcodes', $key);
        $data = array_merge($ret, $message);
        //分离入库操作
        $redis->rpush('keyou_trade_list', serialize($data));
      }
      echo json_encode($ret);
      break;
    case 'micropay_alipay_query':
      require_once 'lib/alipay/f2fpay/service/AlipayTradeService.php';
      $mchId = $_GET['mch_id'];
      $key   = $_GET['key'];
      $trade = $_GET['trade'];
      $outTradeNo = $_GET['out_trade_no'];

      //构造查询业务请求参数对象
      $queryContentBuilder = new AlipayTradeQueryContentBuilder();
      $queryContentBuilder->setOutTradeNo($out_trade_no);
      $queryContentBuilder->setAppAuthToken($appAuthToken);
      //初始化类对象，调用queryTradeResult方法获取查询应答
      $queryResponse = new AlipayTradeService($config);
      $queryResult = $queryResponse->queryTradeResult($queryContentBuilder);
      //根据查询返回结果状态进行业务处理
      switch ($queryResult->getTradeStatus()){
          case "SUCCESS":
              echo "支付宝查询交易成功:"."<br>--------------------------<br>";
              print_r($queryResult->getResponse());
              break;
          case "FAILED":
              echo "支付宝查询交易失败或者交易已关闭!!!"."<br>--------------------------<br>";
              if(!empty($queryResult->getResponse())){
                  print_r($queryResult->getResponse());
              }
              break;
          case "UNKNOWN":
              echo "系统异常，订单状态未知!!!"."<br>--------------------------<br>";
              if(!empty($queryResult->getResponse())){
                  print_r($queryResult->getResponse());
              }
              break;
          default:
              echo "不支持的查询状态，交易返回异常!!!";
              break;
      }

      if ($ret['return_code'] == 'SUCCESS' && isset($ret['transaction_id'])) {
        $message= array(
              'trade'        => $_GET['trade'],
              'get_point'    => 0,
              'consume'      => round($ret['cash_fee']/100,2),
              'use_coupon_id'=> isset($ret['use_coupon_id'])?$ret['use_coupon_id']:0,
              'use_coupon_amount' => isset($ret['use_coupon_amount'])?$ret['use_coupon_amount']:0,
              'use_coupon_name'   => isset($ret['use_coupon_name'])?$ret['use_coupon_name']:'',
              'consume_recharge'  => isset($ret['consume_recharge'])?$ret['consume_recharge']:0,
              'consume_point'     => isset($ret['consume_point'])?$ret['consume_point']:0,
              'point_amount'      => isset($ret['point_amount'])?$ret['point_amount']:0,
              'use_point'         => isset($ret['use_point'])?$ret['use_point']:0,
              'reduce'            => 0,
              'save'              => 0,
              'discount'          => 0,
              'member_discount'   => 0,
              'prepay_id'         => 0
            );
        $redis->hset('keyou_pay_result', $key, serialize($message));

        $ret['pay_info'] = $redis->hget('keyou_pay_qrcodes', $key);
        $data = array_merge($ret, $message);
        //分离入库操作
        $redis->rpush('keyou_trade_list', serialize($data));
      }
      echo json_encode($ret);
      break;
    case 'alipay_precreate':
      require_once 'lib/alipay/AopSdk.php';
      require_once 'lib/alipay/f2fpay/config/config.php';

      $mchId = $_GET['mch_id'];
      $totalAmount = $_GET['trade'];
      $merchantName= $_GET['m_name'];
      $counter     = $_GET['counter'];
      $counterName = $_GET['counter_name'];
      $outTradeNo  = getOutTradeNo();

      $qrData = $mchId.'#'.$totalAmount.'#'.$counter.'#'.$counterName.'#0';
      $scene = substr(md5($mchId.$outTradeNo), 8, 16);
      $redis->hset('keyou_pay_qrcodes', $scene, $qrData);
      $redis->expire('keyou_pay_qrcodes', 600);

      $alipayToken  = $redis->hget('alipay_code', $mchId);
      $alipayTokenData = json_decode($alipayToken, true);
      $appAuthToken    = $alipayTokenData['app_auth_token'];
      //$appAuthToken = '201908BB2b077cbce82645de8ce0f8066e005X56';
      $pId = "2088811889755859"; 
      $subject = $merchantName;
      $bizContent = array(
                'out_trade_no'=>$outTradeNo,
                'total_amount'=>$totalAmount,
                'subject'     => $subject,
                'extend_params' => array(
                                    'sys_service_provider_id' => $pId
                                  )
            );
      $aop = new AopClient();
      $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
      $aop->appId = $config['app_id'];
      $aop->rsaPrivateKey = $config['merchant_private_key'];
      $aop->alipayrsaPublicKey= $config['alipay_public_key'];
      $aop->apiVersion = '1.0';
      $aop->signType = 'RSA2';
      $aop->postCharset='UTF-8';
      $aop->format='json';
      $request = new AlipayTradePrecreateRequest();
      $request->setNotifyUrl($config['notify_url']);
      $request->setBizContent(json_encode($bizContent));
      $result = $aop->execute($request, null, $appAuthToken);
      echo json_encode($result);
      break;
    case 'get_pay_result':
      $key = $_GET['key'];
      $ret = unserialize($redis->hget('keyou_pay_result', $key));

      $message= array(
              'trade'        => $ret['trade'],
              'get_point'    => $ret['get_point'],
              'consume'      => $ret['consume'],
              'use_coupon_id'=> isset($ret['use_coupon_id'])?$ret['use_coupon_id']:0,
              'use_coupon_amount' => isset($ret['use_coupon_amount'])?$ret['use_coupon_amount']:0,
              'use_coupon_name'   => isset($ret['use_coupon_name'])?$ret['use_coupon_name']:'',
              'consume_recharge'  => isset($ret['consume_recharge'])?$ret['consume_recharge']:0,
              'consume_point'     => isset($ret['consume_point'])?$ret['consume_point']:0,
              'point_amount'      => isset($ret['point_amount'])?$ret['point_amount']:0,
              'use_point'         => isset($ret['use_point'])?$ret['use_point']:0,
              'reduce'            => $ret['reduce'],
              'save'              => $ret['save'],
              'discount'          => $ret['discount'],
              'member_discount'   => $ret['member_discount']
            );
      echo json_encode($message);
      break;
    case 'get_detail':
      $outTradeNo = $_GET['out_trade_no'];
      $sql = "SELECT * FROM wechat_pays WHERE out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);
      $row['use_coupon_name'] = $row['award_coupon_name'] = '无';
      if ($row['use_coupon_id']) {
        $sql = "SELECT name FROM coupons WHERE id = $row[use_coupon_id]";
        $ret = $db->fetch_row($sql);
        $row['use_coupon_name'] = $ret['name'];
      }
      $sql = "SELECT name, nickname, mobile FROM members WHERE mch_id = $row[mch_id] AND sub_openid = '$row[sub_openid]'";
      $ret = $db->fetch_row($sql);
      if ($ret) {
        $row = array_merge($row, $ret);
      }
      echo json_encode($row);
      break;
    case 'get_ordering_detail':
      $mchId      = $_GET['mch_id'];
      $outTradeNo = $_GET['out_trade_no'];

      $sql = "SELECT * FROM member_ordering_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);

      $data = array('order'=>$row, 'dishes'=>unserialize($row['detail']));
      echo json_encode($data);
      break;
    case 'get_waimai_detail':
      $mchId      = $_GET['mch_id'];
      $outTradeNo = $_GET['out_trade_no'];

      $sql = "SELECT * FROM member_waimai_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);

      $data = array('order'=>$row, 'dishes'=>unserialize($row['detail']));
      echo json_encode($data);
      break;
    case 'update_waimai_status':
      $mchId      = $_GET['mch_id'];
      $outTradeNo = $_GET['out_trade_no'];
      $status     = $_GET['change_status'];

      $sql = "UPDATE member_waimai_orders SET ";
      if ('accept' == $status) {
        $sql .= " accept_at = '$now'";
      } else if ('delivery' == $status) {
        $sql .= " delivery_at = '$now'";
      } else if ('close' == $status) {
        $sql .= " closed_at = '$now'";
      }
      $sql .= " WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo' LIMIT 1";
      $db->query($sql);

      //给顾客发送小程序订阅通知
      $_GET['mini_access_token'] = $redis->hget('keyou_mini', 'access_token');
      $_GET['job'] = 'waimai_notice';
      $redis->rpush('keyou_mch_job_list', serialize($_GET));
      echo 'success';
      break;
    case 'get_list':
      $mchId = $_GET['mch_id'];
      $shopId= isset($_GET['shop_id'])?$_GET['shop_id']:0;
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $sql = "SELECT COUNT(id) AS total FROM wechat_pays WHERE mch_id = $mchId  AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      if ($shopId) {
        //$sql .= " AND shop_id = $shopId";
      }
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT id, out_trade_no, trade, cash_fee, detail, date_format(created_at, '%m-%e %H:%i') AS create_time FROM wechat_pays WHERE mch_id = $mchId  AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      if ($shopId) {
        //$sql .= " AND shop_id = $shopId";
      }
      $sql .= " ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);

      $return = array('total'=>$total, 'page_total'=>$pageTotal, 'list'=>$data);
      echo json_encode($return);
      break;
    case 'get_ordering_list':
      $mchId = $_GET['mch_id'];
      $tableId   = $_GET['table_id'];
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));

      $sql = "SELECT id, out_trade_no, total_amount, get_no, table_name, contact_name, date_format(created_at, '%m-%e %H:%i') AS create_time FROM member_ordering_orders WHERE mch_id = $mchId  AND is_pay = 1 AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      if ($tableId) {
        $sql .= " AND table_id = $tableId";
      }
      $sql .= " ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);

      echo json_encode($data);
      break;
    case 'get_mall_list':
      $mchId = $_GET['mch_id'];
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));

      $sql = "SELECT id, out_trade_no, total_amount, accept_at, delivery_at, closed_at, contact_name, contact_mobile, date_format(created_at, '%m-%e %H:%i') AS create_time FROM member_mall_orders WHERE mch_id = $mchId AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      $sql .= " ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);

      echo json_encode($data);
      break;
    case 'get_mall_detail':
      $mchId      = $_GET['mch_id'];
      $outTradeNo = $_GET['out_trade_no'];

      $sql = "SELECT * FROM member_mall_orders WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $row = $db->fetch_row($sql);

      $data = array('order'=>$row, 'products'=>unserialize($row['detail']));
      echo json_encode($data);
      break;
    case 'get_waimai_list':
      $mchId = $_GET['mch_id'];
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));

      $sql = "SELECT id, out_trade_no, total_amount, delivery_time, accept_at, delivery_at, closed_at, contact_name, contact_mobile, date_format(created_at, '%m-%e %H:%i') AS create_time FROM member_waimai_orders WHERE mch_id = $mchId  AND is_pay = 1 AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      $sql .= " ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);

      echo json_encode($data);
      break;
    case 'refund_alipay':
      $mchId       = $_GET['mch_id'];
      $outTradeNo  = $_GET['out_trade_no'];
      $sql = "SELECT appid, mch_id, sub_openid, trade, save, cash_fee, use_coupon_id, use_point, get_point, use_recharge FROM wechat_pays WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $tradeData = $db->fetch_row($sql);
      $appId       = $tradeData['appid'];
      $openId      = $tradeData['sub_openid'];
      $totalFee    = $tradeData['cash_fee'];
      $trade       = $tradeData['trade'];
      $refundDesc  = '门店退款';
	    $outRefundNo = getOutTradeNo();
      require_once 'lib/alipay/f2fpay/model/builder/AlipayTradeRefundContentBuilder.php';
      require_once 'lib/alipay/f2fpay/service/AlipayTradeService.php';
      //第三方应用授权令牌,商户授权系统商开发模式下使用
      $appAuthToken = '201908BB2b077cbce82645de8ce0f8066e005X56';
      //创建退款请求builder,设置参数
      $refundRequestBuilder = new AlipayTradeRefundContentBuilder();
      $refundRequestBuilder->setOutTradeNo($outTradeNo);
      $refundRequestBuilder->setRefundAmount($totalFee/100);
      $refundRequestBuilder->setOutRequestNo($outRefundNo);
      $refundRequestBuilder->setAppAuthToken($appAuthToken);
      //初始化类对象,调用refund获取退款应答
      $refundResponse = new AlipayTradeService($config);
      $refundResult =	$refundResponse->refund($refundRequestBuilder);
      $result = $refundResult->getTradeStatus();
      if ('SUCCESS' == $result) {
        $tradeNo = '';
        $sql = "INSERT INTO wechat_refunds (appid, mch_id, openid, out_trade_no, out_refund_no, total_fee, refund_fee, cash_refund_fee, refund_desc, created_by_uid, created_at) VALUES ('$appId', $mchId, '$openId', '$outTradeNo', '$outRefundNo', '$tradeNo', $totalFee, $totalFee, $totalFee, '$refundDesc', 0, '$now')";
        $db->query($sql);

        $sql = "UPDATE wechat_pays SET cash_fee = cash_fee - $refundFee, refund_fee = $refundFee WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo' LIMIT 1";
        $db->query($sql);

        $dateAt = date('Y-m-d');
        $sql = "UPDATE wechat_pays_today SET consumes = consumes - $refundFee, consumes_wechat = consumes_wechat - $refundFee, refund_fee = refund_fee + $refundFee, use_point = use_point - $usePoint, get_point = get_point - $getPoint, recharges = recharges - $useRecharge WHERE mch_id = $mchId AND date_at = '$dateAt'";
        $db->query($sql);
        echo 'success';
      } else {
        echo 'fail';
      }
      break;   
    case 'refund_wechat':
      $mchId       = $_GET['mch_id'];
      $outTradeNo  = $_GET['out_trade_no'];
      $sql = "SELECT mch_id, sub_openid, trade, save, cash_fee, use_coupon_id, use_point, get_point, use_recharge FROM wechat_pays WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo'";
      $tradeData = $db->fetch_row($sql);

      $openId      = $tradeData['sub_openid'];
      $outRefundNo = getOutTradeNo();
      $totalFee    = $tradeData['cash_fee'];
      $usePoint    = $tradeData['use_point'];
      $getPoint    = $tradeData['get_point'];
      $useRecharge = $tradeData['use_recharge'];
      $trade       = $tradeData['trade'];
      $refundFee   = $totalFee;
      $refundDesc  = '门店退款';
      $postData = array(
                    'appid'   => KEYOU_MP_APP_ID,
                    'mch_id'=> MCHID,
                    'sub_appid'  => SUISHOUHUI_APP_ID,
                    'sub_mch_id' => $mchId,
                    'nonce_str' => getNonceStr(),
                    'out_trade_no' => $outTradeNo,
                    'out_refund_no'=> $outRefundNo,
                    'total_fee'    => $totalFee,
                    'refund_fee'   => $refundFee,
                    'refund_desc'  => $refundDesc
      );
      $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
      $sign = MakeSign($postData);
      $postData['sign'] = $sign;
      $xml = ToXml($postData);
      $response = postXmlCurl($xml, $url, true, 6);
      $retData = FromXml($response);
      if ($retData['return_code'] == 'SUCCESS' && $retData['result_code'] == 'SUCCESS') {
        $refundId = $retData['refund_id'];
        $refundFee= $retData['refund_fee'];
        $cashFee  = $retData['cash_fee'];
        $couponRefundFee = $retData['coupon_refund_fee'];
        $cashRefundFee   = $retData['cash_refund_fee'];
        $sql = "INSERT INTO wechat_refunds (appid, mch_id, openid, out_trade_no, out_refund_no, refund_id, total_fee, refund_fee, cash_refund_fee, coupon_refund_fee, refund_desc, created_by_uid, created_at) VALUES ('$retData[appid]', $mchId, '$openId', '$outTradeNo', '$outRefundNo', '$refundId', $totalFee, $refundFee, $cashRefundFee, $couponRefundFee, '$refundDesc', 0, '$now')";
        $db->query($sql);

        $sql = "UPDATE wechat_pays SET cash_fee = cash_fee - $cashRefundFee, refund_fee = $refundFee WHERE mch_id = $mchId AND out_trade_no = '$outTradeNo' LIMIT 1";
        $db->query($sql);

        $dateAt = date('Y-m-d');
        $sql = "UPDATE wechat_pays_today SET consumes = consumes - $cashRefundFee, consumes_wechat = consumes_wechat - $cashRefundFee, refund_fee_wechat = refund_fee_wechat + $cashRefundFee, use_point = use_point - $usePoint, get_point = get_point - $getPoint, recharges = recharges - $useRecharge WHERE mch_id = $mchId AND date_at = '$dateAt' LIMIT 1";
        $db->query($sql);

        //退掉会员积分、券等
        $tradeData['job'] = 'trade_refund';
        $tradeData['access_token'] = $redis->hget('keyouxinxi', 'wx_access_token');
        $redis->rpush('member_job_list', serialize($tradeData));
        echo 'success';
      } else {
        echo 'fail';
      }
      break;
    case 'get_groupon_sold_list':
      $mchId     = $_GET['mch_id'];
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' +1 days'));
      $page      = $_GET['page'];
      $pageCount = $_GET['page_count'];

      $sql = "SELECT COUNT(id) AS total FROM wechat_groupon_pays WHERE mch_id = $mchId  AND created_at > '$dateStart' AND created_at < '$dateEnd' ORDER BY created_at DESC";
      $row = $db->fetch_row($sql);
      $total = $row['total'];
      $pageTotal = ceil($total/$pageCount);

      $startRow = ($page-1) * $pageCount;
      $sql = "SELECT mch_id, openid, coupon_name, buy_total, cash_fee, created_at FROM wechat_groupon_pays WHERE mch_id = $mchId  AND created_at > '$dateStart' AND created_at < '$dateEnd' ORDER BY created_at DESC LIMIT $startRow, $pageCount";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $sql = "SELECT nickname, headimgurl FROM members WHERE mch_id = $mchId AND sub_openid = '$row[openid]'";
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

  function authcodetoopenid($code, $mchId)
  {
    $data = array(
             'appid'   => KEYOU_MP_APP_ID,
             'sub_appid' => SUISHOUHUI_APP_ID,
             'mch_id'  => MCHID,
             'sub_mch_id' => $mchId,
             'nonce_str'  => getNonceStr(),
             'auth_code'  => $code,
             'sign_type'  => 'MD5',
             );
    $sign = MakeSign($data);
    $data['sign'] = $sign;
    $xml = ToXml($data);

    $url = 'https://api.mch.weixin.qq.com/tools/authcodetoopenid';
    $response = postXmlCurl($xml, $url, false, 6);
    $ret = FromXml($response);
    if ($ret['return_code'] == 'SUCCESS' && $ret['result_code'] == 'SUCCESS') {
      $openId = $ret['openid'];
      $subOpenId = $ret['sub_openid'];

      $sql = "SELECT * FROM members WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        return false;
      } else {
        return true;
      }
    }
  }
