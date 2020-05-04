<?php
  require_once 'common.php';

  $action = $_GET['action'];
  switch ($action) {
    case 'create_channel':
      $channel = $_GET['channel'];
      $url = 'http://sinaapp.keyouxinxi.com/create_channel.php?channel='.$channel;
      $ret = httpPost($url);

      $data = json_decode($ret, true);
      $wssUrl = $data['data'];
      $redis->set('keyou_wssurl_'.$channel, $wssUrl);
      $redis->expire('keyou_wssurl_'.$channel, 1800);
      echo $ret;
      break;
    case 'send_message':
      $channel = $_GET['channel'];
      $message = $_GET['message'];
      $url     = 'http://sinaapp.keyouxinxi.com/send_message.php?channel='.$channel.'&message='.$message;
      $ret     = httpPost($url);
      echo $ret;
      break;
    default:
      break;
  }
  
