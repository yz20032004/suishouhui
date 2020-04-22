<?php
  //微信公众号、小程序管理员扫码授权
  require_once 'const.php';
  require_once 'lib/function.php';
  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);

  $componentAccessToken  = $redis->hget('keyou_suishouhui_open', 'component_access_token');
  $url = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token='.$componentAccessToken;
  $data = array('component_appid' => APP_ID);
  $ret = sendHttpRequest($url, $data); 
  $data = json_decode($ret, true);
  $preAuthCode = $data['pre_auth_code'];
  
  $redirectUri = 'http://open.keyouxinxi.com/getapp.php';
  //PC端扫码授权
  //$url = 'https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid='.APP_ID.'&pre_auth_code='.$preAuthCode.'&redirect_uri='.$redirectUri.'&auth_type=2';
  //header('location:'.$url);

  //移动端扫码授权
  $url = 'https://mp.weixin.qq.com/safe/bindcomponent?action=bindcomponent&auth_type=2&component_appid='.APP_ID.'&pre_auth_code='.$preAuthCode.'&redirect_uri='.$redirectUri.'&auth_type=2&no_scan=1#wechat_redirect';
  header('location:'.$url);
