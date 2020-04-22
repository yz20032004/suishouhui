<?php
  /*
   * 顾客端小程序消息推送配置
   * 获取门店微信群二维码
   */
  require_once 'const.php';
  require_once 'lib/function.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  $db = new DB(DBHOST, DBUSER_SUISHOUHUI, DBPASS_SUISHOUHUI, DBNAME_SUISHOUHUI);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);

  if (!checkSignature()) {
    return false;
    //echo $_GET['echostr'];
  }
  $post = file_get_contents('php://input');
  $postData = json_decode($post, true);
  $openId   = $postData['FromUserName'];
  $msgType  = $postData['MsgType'];
  
  $content  = $postData['Content'];
  if ('1' == trim($content)) {
    $sql = "SELECT mch_id FROM members WHERE sub_openid = '$openId'";
    $row = $db->fetch_row($sql);
    $mchId = $row['mch_id'];

    $sql = "SELECT media, guide FROM app_wechat_groups WHERE mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $mediaId = $row['media'];
    $guide   = $row['guide'];

    $miniAccessToken = $redis->hget('keyou_mini', 'access_token');
    $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token='.$miniAccessToken;

    $data = array(
              'touser' => $openId,
              'msgtype'=> 'text',
              'text'=> array('content'=>urlencode($guide))
                );
    httpPost($url, urldecode(json_encode($data)));

    $data = array(
              'touser' => $openId,
              'msgtype'=> 'image',
              'image'  => array('media_id' => $mediaId)
                );
    sendHttpRequest($url, $data);
  }
  echo 'success';

  function checkSignature()
  {
      $signature = $_GET["signature"];
      $timestamp = $_GET["timestamp"];
      $nonce = $_GET["nonce"];

      $token = SUISHOUHUI_TOKEN;
      $tmpArr = array($token, $timestamp, $nonce);
      sort($tmpArr, SORT_STRING);
      $tmpStr = implode($tmpArr);
      $tmpStr = sha1( $tmpStr );

      if ($tmpStr == $signature ) {
          return true;
      } else {
          return false;
      }
  }
