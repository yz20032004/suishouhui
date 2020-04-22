<?php
  /*
   * 微信开放平台
   * 小程序商户人脸识别验证通过回调
   */
  require_once 'const.php';
  require_once 'lib/db.class.php';
  require_once 'lib/function.php';
  include_once "lib/wxBizMsgCrypt.php";
  require_once 'unit/log.php';
  //初始化日志
  $logHandler= new CLogFileHandler("/mnt/tmp/open_logs/".date('Ymd').'.log');
  $log = Log::Init($logHandler, 15);

  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(0);

  $postData = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
  Log::DEBUG($postData);
  echo 'success';

  $pc = new WXBizMsgCrypt(TOKEN, AES_KEY, APP_ID);
  $errCode = $pc->decryptMsg($_GET['msg_signature'], $_GET['timestamp'], $_GET['nonce'], $postData, $msg);
  if ($errCode == 0) {
    $xml = (array)simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
    Log::DEBUG(serialize($xml));

    $infoType = $xml['InfoType'];
    switch ($infoType) {
      case 'component_verify_ticket':
        $ticket = $xml['ComponentVerifyTicket'];
        $redis->hset('keyou_suishouhui_open', 'ComponentVerifyTicket', $ticket);

        $postTicketTimes = $redis->hget('keyou_suishouhui_open', 'posttickettimes');
        if ($postTicketTimes < 6) {
          //1个小时获取一次component_access_token
          $redis->hset('keyou_suishouhui_open', 'posttickettimes', 0);
          
          $ticket = $redis->hget('keyou_suishouhui_open', 'ComponentVerifyTicket');
          $url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
          $data = array('component_appid' => APP_ID,
                        'component_appsecret'=> APP_SECRET, 
                        'component_verify_ticket' => $ticket);
          
          $ret = sendHttpRequest($url, $data);
          $data = json_decode($ret, true);
          if ($data['component_access_token']) {
            $redis->hset('keyou_suishouhui_open', 'component_access_token', $data['component_access_token']);
            $expiredAt = date('Y-m-d H:i:s', strtotime('+ 2hour'));
            $redis->hset('keyou_suishouhui_open', 'component_access_token_expire_in', $expiredAt);
            echo 'component_access_token is '.$data['component_access_token'].PHP_EOL;

            $redis->select(1);
            $redis->hset('keyou_suishouhui_open', 'component_access_token', $data['component_access_token']); 
          }

        } else {
          $redis->hset('keyou_suishouhui_open', 'posttickettimes', $postTicketTimes + 1);
        }
        break;
      case 'notify_third_fasteregister':
        $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
        $db->query("SET NAMES utf8");

        $appId = $xml['appid'];
        $wechat= $xml['legal_persona_wechat'];
        $sql   = "UPDATE apps SET appid = '$appId' WHERE legal_persona_wechat = '$wechat'";
        $db->query($sql);

        $sql = "SELECT mch_id FROM apps WHERE legal_persona_wechat = '$wechat'";
        $row = $db->fetch_row($sql);
        if ($row['mch_id']) {
          $mchId = $row['mch_id'];
          $sql   = "UPDATE mchs SET appid = '$appId' WHERE mch_id = $mchId";
          $db->query($sql);
        }

        $accessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
        $data = array(
          'job' => 'init',
          'authorizer_appid' => $appId,
          'authorizer_access_token' => $accessToken,
        );
        $redis->rpush('keyou_mini_job_list', serialize($data));
        break;
      case 'authorized':
        $authorizationCode = $xml['AuthorizationCode'];
        $componentAccessToken = $redis->hget('keyou_suishouhui_open', 'component_access_token');
        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token='.$componentAccessToken;
        $data = array('component_appid'=>APP_ID, 'authorization_code'=>$authorizationCode);
        $ret = sendHttpRequest($url, $data);
        Log::DEBUG('api_query_auth :'.serialize($ret));
        $data = json_decode($ret, true);
        $appData = $data['authorization_info'];
        
        $appId = $appData['authorizer_appid'];
        $redis->hset('keyou_suishouhui_authorizer_access_token', $appId, $appData['authorizer_access_token']);
        $redis->hset('keyou_suishouhui_authorizer_refresh_token', $appId, $appData['authorizer_refresh_token']);

        $url = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token='.$componentAccessToken;
        $data = array('component_appid'=>APP_ID, 'authorizer_appid'=>$appId);
        $ret = sendHttpRequest($url, $data);
        $data = json_decode($ret, true);
        $userName = $data['user_name'];
        $principalName = $data['principal_name'];
        if (!$userName) {
          return;
        }

        $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
        $db->query("SET NAMES utf8");

        $now = date('Y-m-d H:i:s');
        $sql = "INSERT INTO apps (appid, user_name, principal_name, created_at) VALUES ('$appId', '$userName', '$principalName', '$now')";
        $db->query($sql);

        //初始化小程序
        $data = array(
          'job' => 'init',
          'authorizer_appid' => $appId,
          'authorizer_access_token' => $appData['authorizer_access_token']
        );
        $redis->rpush('keyou_mini_job_list', serialize($data));
        break;
      default:
        break;
    }
  }
