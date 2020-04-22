<?php
  //服务后台数据库配置
  define('DBHOST', '');
  define('DBUSER_SUISHOUHUI', '');
  define('DBPASS_SUISHOUHUI', '');
  define('DBNAME_SUISHOUHUI', '');

  //服务商的微信公众号APPID和SECRET
  define('KEYOU_MP_APP_ID','');
  define('KEYOU_MP_APP_SECRET','');

  //随手惠生活小程序，也就是顾客端小程序
  define('SUISHOUHUI_APP_ID', '');
  define('SUISHOUHUI_APP_SECRET', '');
  define('SUISHOUHUI_TOKEN', '');
  define('SUISHOUHUI_AES_KEY', '');

  //随手惠管家微信小程序的APPID, APPSECRET，也就是商户端小程序
  define('SSHGJ_APP_ID', '');
  define('SSHGJ_APP_SECRET', '');

  //随手惠推推微信小程序的APPID, APPSECRET，也就是代理商端小程序
  define('SSHTT_APP_ID', '');
  define('SSHTT_APP_SECRET', '');

  //特约商户的微信支付商户号和支付的KEY，此商户号用于商户在商户端小程序中购买短信，佣金提现等操作，若无此商户号则需关闭相关功能
  define('KEYOU_MCHID', '');
  define('KEYOU_KEY', '');

  //服务商的微信支付商户号，用于代子商户发起支付等操作
  define('MCHID', '');

  //REDIS配置信息
  define('REDIS_HOST', '127.0.0.1');
  define('REDIS_PORT', 6379);
  define('REDIS_PASSWORD', '');
  define('REDIS_DB', 0);

  //服务器后台访问URL
  define('SITE_URL', 'https://xxxx.xxxx.com');

  //阿里云OSS存储服务配置的KEY
  define('ALIYUN_ACCESS_KEY_ID', '');
  define('ALIYUN_ACCESS_KEY_SECRET', '');

  //表单大师 JSFORM.COM,用于生成外卖和点餐表单,获取提交数据
  define('JSFORM_APP_KEY', '');
  define('JSFORM_APP_SECRET', '');

  //微信开放平台APPID open.weixin.qq.com,用于代商户创建小程序
  define('COMPONENT_APP_ID', '');

  //新浪云websocket访问地址,用于商户扫码核销优惠券后通知顾客端小程序做出下一步反馈操作
  define('SAE_URL', 'http://sinaapp.keyouxinxi.com');

  //阿里云短信模板ID，代理商注册验证码,短信模板为：您的验证码为${code},请在30分钟内完成登录。
  define('SMS_TEMPLATE_VALIDATE_CODE', '');
  //阿里云短信模板ID，商户给顾客发货短信通知,模板内容为：会员您好，你在${merchant}购买的商品已发货，快递单号是${express_no}，请注意查收。
  define('SMS_TEMPLATE_CODE_MALL_DELIVER', '');
