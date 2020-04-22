<?php
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/WxPay.Config.php';
  require_once dirname(__FILE__).'/lib/function.php';
  require_once dirname(__FILE__) . '/lib/aliyun-sms/vendor/autoload.php';
  use Aliyun\Core\Config;
  use Aliyun\Core\Profile\DefaultProfile;
  use Aliyun\Core\DefaultAcsClient;
  use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
  use Aliyun\Api\Sms\Request\V20170525\SendBatchSmsRequest;
  use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  while (true) {
    $result = $redis->blpop('keyou_sms_job_list', 1800);
    if ($result) {
      $data = unserialize($result[1]);
      $templateCode = $data['template_code'];
      $mobile       = $data['mobile'];
      $params       = $data['sms_params'];
      send_mobile_message($mobile, $templateCode, $params);
    }
  }

  /*
   * 阿里大于平台发送短信
   * @mobiles array
   * @param json string
   * @sign string 短信签名
   * @templateCode string 短信模板id
   */
  function send_mobile_message($mobile, $templateCode, $params)
  {
    // 加载区域结点配置
    Config::load();
    //产品名称:云通信短信服务API产品,开发者无需替换
    $product = "Dysmsapi";
    //产品域名,开发者无需替换
    $domain = "dysmsapi.aliyuncs.com";
    // TODO 此处需要替换成开发者自己的AK (https://ak-console.aliyun.com/)
    $accessKeyId = ALIDAYU_KEY; // AccessKeyId
    $accessKeySecret = ALIDAYU_SECRET; // AccessKeySecret
    // 暂时不支持多Region
    $region = "cn-hangzhou";
    // 服务结点
    $endPointName = "cn-hangzhou";
    //初始化acsClient,暂不支持region化
    $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
    // 增加服务结点
    DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
    // 初始化AcsClient用于发起请求
    $acsClient = new DefaultAcsClient($profile);

    // 初始化SendSmsRequest实例用于设置发送短信的参数
    $request = new SendSmsRequest();
    //可选-启用https协议
    //$request->setProtocol("https");
    // 必填，设置短信接收号码
    $request->setPhoneNumbers($mobile);
    $request->setSignName(ALIDAYU_SIGN_NAME);
    // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
    $request->setTemplateCode($templateCode);
    // 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
    if ($params) {
      $request->setTemplateParam($params);
    }
    // 可选，设置流水号
    //$request->setOutId("yourOutId");
    // 选填，上行短信扩展码（扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段）
    //$request->setSmsUpExtendCode("1234567");
    // 发起访问请求
    $acsResponse = $acsClient->getAcsResponse($request);
    //return $acsResponse;
    echo 'send sms ';print_r($acsResponse).PHP_EOL;
  }

  /**
   * 批量发送短信
   * @return stdClass
   */
  function sendBatchSms($mobiles, $templateCode, $params) {
    // 加载区域结点配置
    Config::load();
    //产品名称:云通信短信服务API产品,开发者无需替换
    $product = "Dysmsapi";
    //产品域名,开发者无需替换
    $domain = "dysmsapi.aliyuncs.com";
    // TODO 此处需要替换成开发者自己的AK (https://ak-console.aliyun.com/)
    $accessKeyId = ALIDAYU_KEY; // AccessKeyId
    $accessKeySecret = ALIDAYU_SECRET; // AccessKeySecret
    // 暂时不支持多Region
    $region = "cn-hangzhou";
    // 服务结点
    $endPointName = "cn-hangzhou";
    //初始化acsClient,暂不支持region化
    $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
    // 增加服务结点
    DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
    // 初始化AcsClient用于发起请求
    $acsClient = new DefaultAcsClient($profile);

    $request = new SendBatchSmsRequest();
    $request->setPhoneNumberJson(json_encode($mobiles, JSON_UNESCAPED_UNICODE));
    foreach ($mobiles as $v) {
      $signNameData[] = ALIDAYU_SIGN_NAME;
    }
    $request->setSignNameJson(json_encode($signNameData, JSON_UNESCAPED_UNICODE));
    $request->setTemplateCode($templateCode);
    foreach ($mobiles as $v) {
      $paramData[] = $params;
    }
    $request->setTemplateParamJson(json_encode($paramData, JSON_UNESCAPED_UNICODE));
    $acsResponse = $acsClient->getAcsResponse($request);
    echo 'send batchsms ';print_r($acsResponse).PHP_EOL;
  }
