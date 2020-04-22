<?php
  require_once 'const.php';

  echo date('Y-m-d H:i:s').' ';
  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);

  $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.KEYOU_MP_APP_ID.'&secret='.KEYOU_MP_SECRET;
  $content = file_get_contents($url);
  $obj = json_decode($content);
  $wxAccessToken = $obj->access_token;
  echo 'MP access token '.$wxAccessToken.PHP_EOL;
  $redis->hset('keyouxinxi', 'wx_access_token', $wxAccessToken);

  $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.SUISHOUHUI_APP_ID.'&secret='.SUISHOUHUI_APP_SECRET;
  $content = file_get_contents($url);
  $obj = json_decode($content);
  $suishouhuiAccessToken = $obj->access_token;
  $redis->hset('keyou_mini', 'access_token', $suishouhuiAccessToken);

  $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.APP_ID.'&secret='.APP_SECRET;
  $content = file_get_contents($url);
  $obj = json_decode($content);
  $sshgjAccessToken = $obj->access_token;
  $redis->hset('keyou_mini', 'guanjia_access_token', $sshgjAccessToken);

  $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.SSHTT_APP_ID.'&secret='.SSHTT_APP_SECRET;
  $content = file_get_contents($url);
  $obj = json_decode($content);
  $tuituiAccessToken = $obj->access_token;
  $redis->hset('keyou_mini', 'tuitui_access_token', $tuituiAccessToken);
