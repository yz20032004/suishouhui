<?php
  /*****
   * 营销大全小程序
   *
   * @date 2019-01-04
   *
   */
  require_once 'const.php';
  require_once 'lib/function.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  $db = new DB(DBHOST, DBUSER, DBPASS, CAMPAIGN_DBNAME);
  $db->query("SET NAMES utf8");

  $now = date('Y-m-d H:i:s');
  $action  = $_GET['action'];

  switch ($action) {
    case 'get_index':
      $data = array();
      $refreshs = isset($_GET['refresh'])?$_GET['refresh']:0;
      $cate     = isset($_GET['cate'])?$_GET['cate']:0;
      $sql = "SELECT * FROM images";
      if ($cate) {
        $sql .= " WHERE cate_id = $cate";
      }
      $limit = 4;
      $offset = $refreshs * $limit;
      $sql .= " ORDER BY ID DESC LIMIT $offset, $limit";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_detail':
      $id = $_GET['id'];
      $sql = "SELECT * FROM images WHERE id = $id";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'login':
      $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.CAMPAIGN_APP_ID.'&secret='.CAMPAIGN_APP_SECRET.'&grant_type=authorization_code&js_code='.$_GET['js_code'];
      $ret    = json_decode(httpPost($url), true);

      $openId = $ret['openid'];

      $sql = "SELECT * FROM users WHERE openid = '$openId'";
      $row = $db->fetch_row($sql);

      if ($row['id']) {
        $sql = "SELECT is_member FROM users WHERE openid = '$openId'";
        $ret = $db->fetch_row($sql);
        $row['is_member'] = $ret['is_member'];
      }
      //若未注册，只返回当前用户openid
      $row['openid'] = $openId;
      echo json_encode($row);
      break;
    case 'open_share_qrcode':
      $redis = new Redis();
      $redis->connect(REDIS_HOST, REDIS_PORT);
      $redis->auth(REDIS_PASSWORD);

      $invite = $_GET['invite'];
      $openid  = $_GET['openid'];
      if ('0' == $redis->hget('keyoucampaigns', 'invite_'.$invite)) {
        $redis->hset('keyoucampaigns', 'invite_'.$invite, 1);

        $sql = "INSERT INTO users (openid, is_member, nickname, created_at) VALUES ('$openid', 1, '', '$now')";
        $db->query($sql);
        echo 'success';
      }  else {
        echo 'fail';
      }
      break;
    case 'get_share_qrcode':
      $redis = new Redis();
      $redis->connect(REDIS_HOST, REDIS_PORT);
      $redis->auth(REDIS_PASSWORD);

      $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.CAMPAIGN_APP_ID.'&secret='.CAMPAIGN_APP_SECRET;
      $ret    = json_decode(httpPost($url), true);
      $accessToken = $ret['access_token'];

      $invite = rand(100000, 999999);
      $redis->hset('keyoucampaigns', 'invite_'.$invite, 0);
      $path = 'pages/index/share?invite='.$invite;

      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$accessToken;
      $data = array('path'=>$path);
      $ret = sendHttpRequest($url, $data);

      $object = 'share/'.date('Ymd').'/'.md5($invite).'.jpg';
      $wxUrl = putOssObject($object, $ret);
      echo $wxUrl;
      break;
    default:
      break;
  }
