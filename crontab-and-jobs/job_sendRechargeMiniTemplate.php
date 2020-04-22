<?php
  /*
   * 充值完成后发送模板消息
   * prepay_id至少5秒后才有效
   */
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  while (true) {
    $data = $redis->hgetall('keyou_recharge_result');
    foreach ($data as $key=>$row) {
      $ret = unserialize($row);
      $timeStamp = strtotime(getDateAt($ret['time_end']));
      if (time() - $timeStamp < 10) {
        //10秒后再执行模板消息发送，微信prepay_id有延迟
        continue;
      }
      $transactionId  = $ret['transaction_id'];
      $mchId  = $ret['sub_mch_id'];
      $openId = $ret['sub_openid'];
      $prepayId = $ret['prepay_id'];

      $sql = "SELECT * FROM member_recharges WHERE mch_id = $mchId AND transaction_id = '$transactionId'";
      $row = $db->fetch_row($sql);
      $recharge = $row['recharge'];
      $award    = $row['award_money']?$row['award_money']:'0元';
      $remark   = $row['award_coupon']?'赠送'.$row['award_coupon']:'';

      $sql = "SELECT merchant_name FROM users WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $merchantName = $row['merchant_name'];

      $sql = "SELECT recharge FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
      $row = $db->fetch_row($sql);
      $recharges = $row['recharge'];

      $miniAccessToken = $redis->hget('keyou_mini', 'access_token');
      $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$miniAccessToken;
      $data = array(
                'touser' => $openId,
                'template_id' => 'JYVgtXXH4pn59EdAU9C9_ekouCr8gx_9jqyIvKzid4I',
                'page'        => 'pages/index/index',
                'form_id'     => $prepayId,
                'data'        => array(
                                    'keyword1' => array('value'=>$recharge.'元'),
                                    'keyword2' => array('value'=>$award),
                                    'keyword3' => array('value'=>$merchantName),
                                    'keyword4' => array('value'=>$recharges),
                                    'keyword5' => array('value'=>$remark)
                                 )
              );
      $r = sendHttpRequest($url, $data);
      $redis->hdel('keyou_recharge_result', $key);
      echo 'SEND TEMP '.$r.PHP_EOL;
    }
    sleep(10);
  }
