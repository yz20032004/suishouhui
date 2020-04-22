<?php
  //管理员扫码授权后的跳转URL
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';
  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");
  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);

  $authCode = $_GET['auth_code'];
  $expiresIn = $_GET['expires_in'];
  if (!$authCode) {
    exit();
  }
  $componentAccessToken  = $redis->hget('keyou_suishouhui_open', 'component_access_token');

  //获取（刷新）授权公众号的接口调用凭据（令牌）
  $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$componentAccessToken;
  $data = array('component_appid'=>APP_ID, 'authorization_code'=>$authCode);
  $ret = sendHttpRequest($url, $data);
  $data = json_decode($ret, true);
  $appData = $data['authorization_info'];
  
  $appId = $appData['authorizer_appid'];
  $redis->hset('keyou_suishouhui_authorizer_access_token', $appId, $appData['authorizer_access_token']);
  $redis->hset('keyou_suishouhui_authorizer_refresh_token', $appId, $appData['authorizer_refresh_token']);

  //获取授权方的公众号帐号基本信息
  $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.$componentAccessToken;
  $data = array('component_appid'=>APP_ID, 'authorizer_appid'=>$appId);
  $ret = sendHttpRequest($url, $data);
  file_put_contents('/tmp/suishouhui', $ret.PHP_EOL, FILE_APPEND);
  $data = json_decode($ret, true);

  foreach ($data['authorization_info']['func_info'] as $row) {
    $funcData[] = $row['funcscope_category']['id'];
  }
  $func = implode(',', $funcData);

  $info = $data['authorizer_info'];
  $sql = "SELECT id FROM apps WHERE appid = '$appId'";
  $ret = $db->fetch_row($sql);
  if (!$ret['id']) {
    $now = date('Y-m-d H:i:s');
    $verifyType = $info['verify_type_info']['id'];
    $business = $info['business_info'];
    $sql = "INSERT INTO apps (appid, appname, headimg, verify_type_info, alias, qrcode_url, open_pay, open_shake, open_scan, open_card, open_store, func_info, member_right, discount, created_at) VALUES ('$appId', '$info[nick_name]', '$info[head_img]', $verifyType, '$info[alias]', '$info[qrcode_url]', $business[open_pay], $business[open_shake], $business[open_scan], $business[open_card], $business[open_store], '$func', '', '', '$now')";
    file_put_contents('/tmp/sql', $sql.PHP_EOL, FILE_APPEND);
    $db->query($sql);
  }
  echo 'SUCCESS';
