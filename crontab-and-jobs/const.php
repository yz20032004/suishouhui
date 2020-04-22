<?php
  define('DBHOST', '');
  define('DBUSER', '');
  define('DBPASS', '');
  define('DBNAME', '');

  define('SERIAL_NO', '');//微信支付服务平台证书序列号,用于申请小微商户（目前微信已关闭小微商户的API接口申请，此处可不填）
  define('COMPONENT_APPID', ''); //微信开放平台的appid,用于刷新商户小程序的accesstoken
  define('COMPONENT_TOKEN', '');//微信开放平台的token

  //随手惠管家微信小程序的APPID, APPSECRET,商户端小程序
  define('APP_ID', '');
  define('APP_SECRET', '');

  //随手惠推推微信小程序的APPID, APPSECRET,代理商端小程序
  define('SSHTT_APP_ID', '');
  define('SSHTT_APP_SECRET', '');

  //服务商的微信公众号APPID和SECRET
  define('KEYOU_MP_APP_ID','');
  define('KEYOU_MP_SECRET', '');

  //随手惠生活小程序，也就是顾客端小程序
  define('SUISHOUHUI_APP_ID', '');
  define('SUISHOUHUI_APP_SECRET', '');

  //REDIS配置信息
  define('REDIS_HOST', '127.0.0.1');
  define('REDIS_PORT', 6379);
  define('REDIS_PASSWORD', '');
  define('REDIS_DB', 0);

  //存储，阿里云OSS
  define('ALIYUN_ACCESS_KEY_ID', '');
  define('ALIYUN_ACCESS_KEY_SECRET', '');

  //服务商的微信支付商户号，用于代子商户发起支付等操作
  define('MCH_ID', '');

  //特约商户的微信支付商户号和支付的KEY，此商户号用于微信支付的分账功能，获得商户交易佣金的入账，若无此商户号则需关闭相关功能
  define('KEYOU_MCHID', '');
  //客友信息微信支付KEY
  define('KEYOU_KEY', '');

  //飞鱼云喇叭语音播报参数,对接飞鱼后会拿到API接口及以下两个参数
  define('FEIYU_APPKEY', '');
  define('FEIYU_APPSECRET', '');

  //佳博云打印机参数,用于外卖、点单和商城的订单打印，https://cp.poscom.cn/，自助注册获得以下两个参数
  define('JIABO_MEMBER_CODE', '');
  define('JIABO_API_KEY', '');
