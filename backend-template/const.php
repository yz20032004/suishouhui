<?php
  //服务后台数据库配置
  define('DBHOST', '');
  define('DBUSER_SUISHOUHUI', '');
  define('DBPASS_SUISHOUHUI', '');
  define('DBNAME_SUISHOUHUI', '');

  //服务商的微信公众号APPID和SECRET
  define('KEYOU_MP_APP_ID','');
  define('KEYOU_MP_APP_SECRET','');

  //微信公众号的TOKEN
  define('MP_TOKEN', '');

  //特约商户的微信支付商户号和支付的KEY，此商户号用于商户在商户端小程序中购买短信，佣金提现等操作，若无此商户号则需关闭相关功能
  define('KEYOU_MCHID', '');
  define('KEYOU_KEY', '');

  //服务商的微信支付商户号，用于代子商户发起支付等操作
  define('MCHID', '');
  define('SERIAL_NO', '');//微信支付平台证书序列号

  //REDIS配置信息
  define('REDIS_HOST', '127.0.0.1');
  define('REDIS_PORT', 6379);
  define('REDIS_PASSWORD', '');
  define('REDIS_DB', 0);

  //服务器后台访问URL
  define('SITE_URL', 'https://xxxx.xxx.com');

  //阿里云OSS
  define('ALIYUN_ACCESS_KEY_ID', '');
  define('ALIYUN_ACCESS_KEY_SECRET', '');

  //表单大师 JSFORM.COM,用于生成外卖和点餐表单,获取提交数据
  define('JSFORM_APP_KEY', '');
  define('JSFORM_APP_SECRET', '');

  //微信开放平台APPID open.weixin.qq.com,用于代商户创建小程序
  define('COMPONENT_APP_ID', '');
