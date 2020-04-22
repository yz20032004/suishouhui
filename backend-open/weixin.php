<?php
  /*
   * 微信开放平台消息与事件接收URL
   */
  require_once 'const.php';
  require_once 'lib/function.php';
  require_once 'lib/db.class.php';
  require_once 'lib/weixin.class.php';
  include_once "lib/wxBizMsgCrypt.php";
  require_once 'unit/log.php';
  //初始化日志
  $logHandler= new CLogFileHandler("/mnt/tmp/openevent_logs/".date('Ymd').'.log');
  $log = Log::Init($logHandler, 15);


  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);

  $postData = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
  $pc = new WXBizMsgCrypt(TOKEN, AES_KEY, APP_ID);
  $errCode = $pc->decryptMsg($_GET['msg_signature'], $_GET['timestamp'], $_GET['nonce'], $postData, $msg);
  $appId   = substr($_GET['appid'], 1);
  $now     = date('Y-m-d H:i:s');

  if ($errCode == 0) {
    $xml = (array)simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
    Log::DEBUG($msg);

    $msgType     = $xml['MsgType'];
    switch ($msgType) {
      case 'text':
        break;
      case 'event':
        $event = $xml['Event'];
        if ('weapp_audit_success' == $event) {
          $userName = $xml['ToUserName'];
          $sql = "SELECT appid FROM apps WHERE user_name = '$userName'";
          $row = $db->fetch_row($sql);
          $appId = $row['appid'];

          $accessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
          $postData = array(
            'job'          => 'release',
            'authorizer_appid' => $appId,
            'authorizer_access_token' => $accessToken,
          );
          $redis->rpush('keyou_mini_job_list', serialize($postData));
        } else if ('weapp_audit_fail' == $event) {

        } else if ('wxa_nickname_audit' == $event) {
          $ret = $xml['ret'];
          if ('3' == $ret) {
            $accessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
            $postData = array(
              'job'          => 'submit_audit',
              'authorizer_appid' => $appId,
              'authorizer_access_token' => $accessToken,
            );
            $redis->rpush('keyou_mini_job_list', serialize($postData));
          } else if ('2' == $ret) {
            $smsTempId = 'SMS_185845273';
            $smsParam = json_encode(array('name'=>$xml['nickname'], 'reason'=>$xml['reason']));
            $smsData = array('template_code'=>$smsTempId, 'sms_params'=>$smsParam, 'mobile'=>'13917486084');
            $redis->rpush('keyou_sms_job_list', serialize($smsData));
          }
        }
        break;
      default:
        break;
    }
    echo 'success';
  }
