<?php
  /*
   * 支付完成后发送模板消息
   * prepay_id至少5秒后才有效
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

  while (true) {
    $payResultData = $redis->hgetall('keyou_pay_result');
    foreach ($payResultData as $redisKey=>$payResult) {
      echo 'del key '.$redisKey.PHP_EOL;
      $redis->hdel('keyou_pay_result', $redisKey);
      $redis->hdel('keyou_prepay_ids', $redisKey);
      continue;
      $ret = unserialize($payResult);
      if (!isset($ret['sub_openid'])) {
        continue;
      }
      $timeStamp = $ret['timestamp'];
      if (time() - $timeStamp < 30) {
        //10秒后再执行模板消息发送，微信prepay_id有延迟
        continue;
      }
      $action = $ret['action'];
      $mchId  = $ret['sub_mch_id'];
      $openId = $ret['sub_openid'];
      $key    = $ret['key'];
      $prepayId = $redis->hget('keyou_prepay_ids', $key);
      $trade    = $ret['trade'];
      $getPoint = $ret['get_point'];
      $consume  = $ret['consume'];
      $useCouponId = isset($ret['use_coupon_id'])?$ret['use_coupon_id']:0;
      $useCouponAmount = isset($ret['use_coupon_amount'])?$ret['use_coupon_amount']:0;
      $useCouponName   = isset($ret['use_coupon_name'])?$ret['use_coupon_name']:'无';
      $consumeRecharge = isset($ret['consume_recharge'])?$ret['consume_recharge']:0;
      $consumePoint    = isset($ret['consume_point'])?$ret['consume_point']:0;
      $pointAmount     = isset($ret['point_amount'])?$ret['point_amount']:0;
      $usePoint        = isset($ret['use_point'])?$ret['use_point']:0;
      $reduce          = $ret['reduce'];
      $save            = $ret['save'] + $ret['coupon_fee'];
      $discount        = $ret['discount'];
      $memberDiscount  = $ret['member_discount'];
      $useCouponTotal = $useCouponAmount?1:0;
      $awardCouponName = isset($ret['award_coupon_name'])?$ret['award_coupon_name']:'';
      $awardCouponTotal= isset($ret['award_coupon_total'])?$ret['award_coupon_total']:0;

      $isMember = false;
      $comment  = '';
      $sql = "SELECT id, grade_title, recharge, cardnum FROM members WHERE mch_id = $mchId AND sub_openid = '$openId'";
      $row = $db->fetch_row($sql);
      if (!$row['cardnum']) {
        $miniPagePath = 'pages/index/get_membercard?key=key&mch_id='.$mchId.'&get_point=0';
        $comment  = '加入会员，领取积分和开卡礼';
        $recharge = 0;
      } else {
        $isMember = true;
        $recharge = $row['recharge'];
        $gradeTitle = $row['grade_title'];

        //获取支付小尾巴推荐活动
        $sql = "SELECT campaign_type, campaign_id, title FROM mch_pay_result_recommends WHERE mch_id = $mchId";
        $row = $db->fetch_row($sql);
        if ($row) {
          if ('groupon' == $row['campaign_type']) {
            if ($row['campaign_id']) {
              $comment = $row['title'];
              $miniPagePath = 'pages/groupon/detail?id='.$row['campaign_id'];
            } else {
              //随机推荐
              $campaignIdData = array();
              $now = date('Y-m-d H:i:s');
              $sql = "SELECT id, title FROM mch_groupons WHERE mch_id = $mchId AND is_stop = 0 AND total_limit > 0 AND date_start < '$now' AND DATE_FORMAT(date_end, '%Y-%m-%d 23:59:59') > '$now'";
              $data = $db->fetch_array($sql);
              foreach ($data as $k=>$v) {
                $campaignIdData[$k] = array('id'=>$v['id'], 'title'=>$v['title']);
              }
              $tmpIndex = array_rand($campaignIdData);
              $campaignId = $campaignIdData[$tmpIndex]['id'];
              $comment = $campaignIdData[$tmpIndex]['title'];
              $miniPagePath = 'pages/groupon/detail?id='.$campaignId;
            }
          } else if ('point' == $row['campaign_type']) {
            if ($row['campaign_id']) {
              $comment = $row['title'];
              $miniPagePath = 'pages/point/detail?id='.$row['campaign_id'];
            } else {
              $sql = "SELECT id, point, coupon_name FROM app_point_exchange_rules WHERE mch_id = $mchId AND exchange_limit > 0 ORDER BY exchanged DESC";
              $data = $db->fetch_array($sql);
              foreach ($data as $k=>$v) {
                $campaignIdData[$k] = array('id'=>$v['id'], 'title'=>$v['point'].'积分兑换'.$v['coupon_name']);
              }
              $tmpIndex = array_rand($campaignIdData);
              $campaignId = $campaignIdData[$tmpIndex]['id'];
              $comment = $campaignIdData[$tmpIndex]['title'];
              $miniPagePath = 'pages/point/detail?id='.$campaignId;
            }
          } else if ('recharge' == $row['campaign_type']) {
            if ($row['campaign_id']) {
              $comment = $row['title'];
              $miniPagePath = 'pages/recharge/detail?id='.$row['campaign_id'];
            } else {
              $rechargeRules = $redis->hget('keyou_mch_recharge_rules', $mchId);
              $rechargeData = unserialize($rechargeRules);
              $tmpIndex = array_rand($rechargeData);
              $recommendRecharge = $rechargeData[$tmpIndex];
              $id     = $recommendRecharge['id'];
              $touch  = $recommendRecharge['touch'];
              $amount = $recommendRecharge['amount'];
              $percent = $recommendRecharge['percent'];
              if ('money_constant' == $recommendRecharge['award_type']) {
                $comment .= '储值'.$touch.'赠'.$amount.'元';
              } else if ('money_percent' == $recommendRecharge['award_type']) {
                $amount = ceil($touch * $percent);
                $comment .= '储值'.$touch.'赠'.$amount.'元';
              } else {
                foreach ($recommendRecharge['coupons'] as $v) {
                  $comment .= '储值'.$touch.'赠.'.$v['total'].'张'.$v['coupon_name']."\n";
                }
              }
              $miniPagePath = 'pages/recharge/detail?id='.$id;
            }
          }
        } else {
          $comment = '点此查看更多优惠';
          $miniPagePath = 'pages/index/index';
        }
      }

      $remarkData = array();
      if ($consumeRecharge) {
        $remarkData[] = '使用余额'.$consumeRecharge.'元';
      }
      if ($discount) {
        $remarkData[] = '参与消费折扣'.$discount.'元';
      }
      if ($memberDiscount) {
        $remarkData[] = $gradeTitle.'会员折扣'.$memberDiscount.'元';
      }
      if ($reduce) {
        $remarkData[] = '消费立减'.$reduce.'元';
      }
      if ($awardCouponName) {
        $remarkData[] = '返'.$awardCouponName.$awardCouponTotal.'张';
      }
      $remark = implode("\n", $remarkData);

      $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $merchantName = $row['merchant_name'];



      $miniAccessToken = $redis->hget('keyou_mini', 'access_token');
      $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.$miniAccessToken;
      if ($comment) {
        $comment = "********************\n".$comment."\n********************";
      }
      if ($trade > $consume) {
        //走全字段内容通知
        $data = array(
                  'touser' => $openId,
                  'template_id' => 'dGCaKXMAKbWticXlb2wgvrz1TDkkvOnYGCaW4Ql24kU',
                  'page'        => $miniPagePath,
                  'form_id'     => $prepayId,
                  'data'        => array(
                                      'keyword1' => array('value'=>$merchantName),
                                      'keyword2' => array('value'=>$trade.'元'),
                                      'keyword3' => array('value'=>'抵扣'.$pointAmount.'元'),
                                      'keyword4' => array('value'=>$useCouponName),
                                      'keyword5' => array('value'=>$save.'元'),
                                      'keyword6' => array('value'=>$consume.'元'),
                                      'keyword7' => array('value'=>$recharge.'元'),
                                      'keyword8' => array('value'=>$getPoint.'积分'),
                                      'keyword9' => array('value'=>$remark),
                                      'keyword10' => array('value'=>$comment)
                                   )
                );
      } else {
        //走简易版通知
        $data = array(
                  'touser' => $openId,
                  'template_id' => 'dGCaKXMAKbWticXlb2wgvgyZkthdqMWw9RKyJzeYNn4',
                  'page'        => $miniPagePath,
                  'form_id'     => $prepayId,
                  'data'        => array(
                                      'keyword1' => array('value'=>$merchantName),
                                      'keyword2' => array('value'=>$trade.'元'),
                                      'keyword3' => array('value'=>$getPoint.'积分'),
                                      'keyword4' => array('value'=>'无'),
                                      'keyword5' => array('value'=>$comment)
                                   )
                );

      }
      $r = sendHttpRequest($url, $data);
      $redis->hdel('keyou_pay_result', $redisKey);
      $redis->hdel('keyou_prepay_ids', $redisKey);
      echo 'SEND TEMP '.$r.PHP_EOL;
    }
    sleep(10);
  }
