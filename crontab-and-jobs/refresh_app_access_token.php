<?php
  /**
   * 刷新微信开放平台里创建的小程序的令牌
   * 2019-11-7
   */
  require_once 'const.php';
  require_once 'lib/function.php';

  echo date('Y-m-d H:i:s').PHP_EOL;
  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);

  $now = time();
  $componentAccessToken = $redis->hget('keyou_suishouhui_open', 'component_access_token');
  
  $data = $redis->hgetall('keyou_suishouhui_authorizer_refresh_token');
  foreach ($data as $authorizerAppId=>$refreshToken) {
    $url = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token='.$componentAccessToken;
    $data = array('component_appid' => COMPONENT_APPID, 
                  'authorizer_appid'=> $authorizerAppId, 
                  'authorizer_refresh_token' => $refreshToken);
    $ret = sendHttpRequest($url, $data);
    $data = json_decode($ret, true);
    if (!$data['authorizer_access_token']) {
      continue;
    }
    $authorizerAccessToken = $data['authorizer_access_token'];
    $authorizerRefreshToken= $data['authorizer_refresh_token'];

    $redis->hset('keyou_suishouhui_authorizer_access_token', $authorizerAppId, $authorizerAccessToken);
    $redis->hset('keyou_suishouhui_authorizer_refresh_token', $authorizerAppId, $authorizerRefreshToken);

    echo $authorizerAppId.' refreshed'.PHP_EOL;
  }
