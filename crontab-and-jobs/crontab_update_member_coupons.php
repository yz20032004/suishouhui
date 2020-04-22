<?php
  //每天凌晨重新统计会员优惠券数量，有些优惠券会过期，所以保持会员卡包优惠券数与小程序优惠券数量一致
  //同时将过期的优惠券status设为0
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';
  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);

  $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
  $expireTotal = 0;
  $now = date('Y-m-d H:i:s');

  $expireDate = date('Y-m-d', strtotime('-1 days'));
  $sql  = "SELECT id, mch_id, openid, coupon_id, in_wechat, get_type FROM member_coupons WHERE date_end = '$expireDate' AND  status = 1";
  $data = $db->fetch_array($sql);
  foreach ($data as $row) {
    $id     = $row['id'];
    $mchId  = $row['mch_id'];
    $openId = $row['openid'];
    $couponId = $row['coupon_id'];
    $inWechat = $row['in_wechat'];
    $getType  = $row['get_type'];

    $sql = "UPDATE member_coupons SET status = 0, updated_at = '$now' WHERE id = $id";
    $db->query($sql);
    if ($inWechat) {
      $jobData = array('job'=>'update_member_card', 'access_token'=>$accessToken, 'point'=>0, 'detail'=>'', 'mch_id'=>$mchId, 'openid'=>$openId);
      $redis->rpush('member_job_list', serialize($jobData));
    }
    $sql = "UPDATE members SET coupons = coupons - 1 WHERE mch_id = $mchId AND sub_openid = '$openId'";
    $db->query($sql);

    if ('buy' == $getType || 'together_buy' == $getType) {
      $sql = "SELECT openid, out_trade_no, coupon_total, buy_total, cash_fee, groupon_id FROM wechat_groupon_pays WHERE mch_id = $mchId AND openid = '$openId' AND coupon_id = $couponId ORDER BY created_at DESC LIMIT 1";
      $ret = $db->fetch_row($sql);
      
      $refundFee = round($ret['cash_fee']/$ret['coupon_total']/$ret['buy_total'],0);
      $jobData = array('job'=>'groupon_refund', 'mch_id'=>$mchId, 'out_trade_no'=>$ret['out_trade_no'], 'total_fee'=>$ret['cash_fee'], 'refund_fee'=>$refundFee, 'openid'=>$ret['openid']);
      print_r($jobData);
      //$redis->rpush('member_job_list', serialize($jobData));

      $sql = "UPDATE mch_groupons SET expired = expired + 1 WHERE mch_id = $mchId AND groupon_id = $ret[groupon_id]";
      $db->query($sql);
    }
    $expireTotal++;
  }
  echo 'Member Coupons Expired '.$expireTotal.', executed on '.date('Y-m-d H:i:s').PHP_EOL;
