<?php
  /*
   * 交易汇总报表，分日报、周报、月报
   */
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");
  
  $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');

  day_report();
  week_report();
  month_report();

  function day_report()
  {
    global $db, $accessToken;
    //day report
    $dateStart = date('Y-m-d', strtotime('-1 days'));
    $dateEnd   = date('Y-m-d');

    $sql = "SELECT mch_id, mp_openid FROM user_reminds WHERE is_day = 1";
    $ret = $db->fetch_array($sql);
    foreach ($ret as $r) {
      $mchId  = $r['mch_id'];
      $openId = $r['mp_openid'];

      $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $merchantName = $ret['merchant_name'];

      $data = array(
                  'members'     => 0,
                  'trade_total' => 0,
                  'consumes'         => 0,
                  'consumes_wechat'  => 0,
                  'consumes_alipay'  => 0,
                  'consumes_other'   => 0
                 );
      $sql   = "SELECT ";
      foreach ($data as $key=>$v) {
        $sql .= "SUM($key) AS $key,";
      }
      $sql .= "1=1 FROM wechat_pays_today WHERE mch_id = $mchId AND date_at >= '$dateStart' AND date_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      if ($row['trade_total']) {
        $row['consumes'] = $row['consumes']/100;
        $row['consumes_wechat'] = $row['consumes_wechat']/100;
        $row['consumes_alipay'] = $row['consumes_alipay']/100;
        $row['consumes_other'] = $row['consumes_other']/100;
        $data = $row;
      }

      //通知提醒
      $paramData  = array(
                          'first' => $merchantName.'日报通知',
                          'keyword1' => $dateStart,
                          'keyword2' => $data['trade_total'].'笔',
                          'keyword3' => $data['consumes'].'元',
                          'remark'   => '微信收款'.$data['consumes_wechat']."元,支付宝".$data['consumes_alipay'].'元,其它'.$data['consumes_other'].'元,新增会员'.$data['members'].'人'
                        );
      $remindData = array(
                      'mch_id' => $mchId,
                      'openid'          => $openId,
                      'wx_access_token' => $accessToken,
                      'template_id'     => 'HRWPWHtndjsu7koZJsCztFEg0m2LIZWl8SjpdVxIrLY',
                      'page_path'       => 'pages/trade/index',
                      'param_data'      => $paramData
                    );

      send_user_remind($remindData);
    }
  }

  //周报，每周一推送
  function week_report()
  {
    global $db, $accessToken;
    $day = date('w');
    if ('1' != $day) {
      return;
    }
    $dateStart = date('Y-m-d', strtotime('-7 days'));
    $dateEnd   = date('Y-m-d');
    $sql = "SELECT mch_id, mp_openid FROM user_reminds WHERE is_week = 1";
    $ret = $db->fetch_array($sql);
    foreach ($ret as $r) {
      $mchId  = $r['mch_id'];
      $openId = $r['mp_openid'];

      $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $merchantName = $ret['merchant_name'];

      $data = array(
                  'members'     => 0,
                  'trade_total' => 0,
                  'consumes'         => 0,
                  'consumes_wechat'  => 0,
                  'consumes_alipay'  => 0,
                  'consumes_other'   => 0
                 );
      $sql   = "SELECT ";
      foreach ($data as $key=>$v) {
        $sql .= "SUM($key) AS $key,";
      }
      $sql .= "1=1 FROM wechat_pays_today WHERE mch_id = $mchId AND date_at >= '$dateStart' AND date_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      if ($row['trade_total']) {
        $row['consumes'] = $row['consumes']/100;
        $row['consumes_wechat'] = $row['consumes_wechat']/100;
        $row['consumes_alipay'] = $row['consumes_alipay']/100;
        $row['consumes_other'] = $row['consumes_other']/100;
        $data = $row;
      }

      //通知提醒
      $paramData  = array(
                          'first' => $merchantName.'周报通知',
                          'keyword1' => $dateStart.'至'.date('d', strtotime('-1 days')).'日',
                          'keyword2' => $data['trade_total'].'笔',
                          'keyword3' => $data['consumes'].'元',
                          'remark'   => '微信收款'.$data['consumes_wechat']."元,支付宝".$data['consumes_alipay'].'元,其它'.$data['consumes_other'].'元,新增会员'.$data['members'].'人'
                        );
      $remindData = array(
                      'mch_id' => $mchId,
                      'openid'          => $openId,
                      'wx_access_token' => $accessToken,
                      'template_id'     => 'HRWPWHtndjsu7koZJsCztFEg0m2LIZWl8SjpdVxIrLY',
                      'page_path'       => 'pages/trade/index',
                      'param_data'      => $paramData
                    );

      send_user_remind($remindData);
    }
  }

  //月报，每月1日发送
  function month_report()
  {
    global $db, $accessToken;
    $day = date('j');
    if ('1' != $day) {
      return;
    }
    $dateStart = date('Y-m-01', strtotime('-1 month'));
    $dateEnd   = date('Y-m-d');
    $sql = "SELECT mch_id, mp_openid FROM user_reminds WHERE is_month = 1";
    $ret = $db->fetch_array($sql);
    foreach ($ret as $r) {
      $mchId  = $r['mch_id'];
      $openId = $r['mp_openid'];

      $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $merchantName = $ret['merchant_name'];

      $data = array(
                  'members'     => 0,
                  'trade_total' => 0,
                  'consumes'         => 0,
                  'consumes_wechat'  => 0,
                  'consumes_alipay'  => 0,
                  'consumes_other'   => 0
                 );
      $sql   = "SELECT ";
      foreach ($data as $key=>$v) {
        $sql .= "SUM($key) AS $key,";
      }
      $sql .= "1=1 FROM wechat_pays_today WHERE mch_id = $mchId AND date_at >= '$dateStart' AND date_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      if ($row['trade_total']) {
        $row['consumes'] = $row['consumes']/100;
        $row['consumes_wechat'] = $row['consumes_wechat']/100;
        $row['consumes_alipay'] = $row['consumes_alipay']/100;
        $row['consumes_other']  = $row['consumes_other']/100;
        $data = $row;
      }
      //通知提醒
      $paramData  = array(
                          'first' => $merchantName.'月周报通知',
                          'keyword1' => date('Y年n月', strtotime('-1 month')),
                          'keyword2' => $data['trade_total'].'笔',
                          'keyword3' => $data['consumes'].'元',
                          'remark'   => '微信收款'.$data['consumes_wechat']."元,支付宝".$data['consumes_alipay'].'元,其它'.$data['consumes_other'].'元,新增会员'.$data['members'].'人'
                        );
      $remindData = array(
                      'mch_id' => $mchId,
                      'openid'          => $openId,
                      'wx_access_token' => $accessToken,
                      'template_id'     => 'HRWPWHtndjsu7koZJsCztFEg0m2LIZWl8SjpdVxIrLY',
                      'page_path'       => 'pages/trade/index',
                      'param_data'      => $paramData
                    );

      send_user_remind($remindData);
    }
  }

  function send_user_remind($data)
  {
    $mchId = $data['mch_id'];
    $openId      = $data['openid'];
    $accessToken = $data['wx_access_token'];
    $templateId  = $data['template_id'];
    $pagePath    = $data['page_path'];
    $paramData   = $data['param_data'];

    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$accessToken;
    $postData = array(
                  'touser'       => $openId,
                  'template_id'  => $templateId
                );
    if ($pagePath) {
      $postData['miniprogram']  = array(
                                      'appid' => APP_ID,
                                      'pagepath'  => $pagePath 
                                    );
    }
    foreach ($paramData as $key=>$value) {
      $postData['data'][$key] = array('value'=>urlencode($value));
    }
    $r = httpPost($url, urldecode(json_encode($postData)));
    echo 'send template message '.$r.PHP_EOL;
  }



