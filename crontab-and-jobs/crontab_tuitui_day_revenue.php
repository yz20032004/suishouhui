<?php
  /*
   * 每天凌晨执行推手分润
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

  $yesterday = date('Y-m-d', strtotime('-1 days'));
  $today     = date('Y-m-d');
  $yesterdayTotalRevenue = 0;

  //先算推手收入
  $sql = "SELECT id, openid, leader_id, wait_cash_out, total_revenue, profit_ratio FROM tuitui_users WHERE merchants > 0 AND status = 1";
  $data = $db->fetch_array($sql);
  foreach ($data as $row) {
    $uid = $row['id'];
    $openId = $row['openid'];
    $waitCashOut = $row['wait_cash_out'];
    $totalRevenue= $row['total_revenue'];
    $leaderId    = $row['leader_id'];
    $profitRatio = $row['profit_ratio'];

    $revenueFeeWechat = $revenueFeeAlipay = $revenueFeeMarketing = 0;

    $sql = "SELECT sub_mch_id FROM user_mch_submit WHERE uid = $uid";
    $ret = $db->fetch_array($sql);
    $mchData = array();
    foreach ($ret as $v) {
      $mchId = $v['sub_mch_id'];
      $sql = "SELECT wechat_fee_rate, ali_fee_rate, marketing_fee_rate FROM mchs WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $mchWechatFeeRate = $ret['wechat_fee_rate'];
      $mchAliFeeRate    = $ret['ali_fee_rate'];
      $mchMarketingFeeRate = $ret['marketing_fee_rate'];

      $sql = "SELECT SUM(cash_fee) AS cash_fee FROM wechat_pays WHERE created_at > '$yesterday' AND created_at < '$today' AND pay_type = 1 AND pay_from = 'general' AND mch_id = $mchId";
      $feeData = $db->fetch_row($sql);
      if ($feeData['cash_fee']) {
        $revenueFeeWechat += $feeData['cash_fee'] * ($mchWechatFeeRate - 0.002);
      }

      $sql = "SELECT SUM(cash_fee) AS cash_fee FROM wechat_pays WHERE created_at > '$yesterday' AND created_at < '$today' AND pay_type = 2 AND pay_from = 'general' AND mch_id = $mchId";
      $feeData = $db->fetch_row($sql);
      if ($feeData['cash_fee']) {
        $revenueFeeAlipay += $feeData['cash_fee'] * ($mchAliFeeRate - 0.002);
      }

      $sql = "SELECT SUM(recharge) AS recharge FROM member_recharges WHERE created_at > '$yesterday' AND created_at < '$today' AND mch_id = $mchId";
      $feeData = $db->fetch_row($sql);
      if ($feeData['recharge']) {
        $revenueFeeMarketing += $feeData['recharge'] * 100 * ($mchMarketingFeeRate - $mchWechatFeeRate);
      }

      $sql = "SELECT SUM(total_amount) AS total_amount FROM member_waimai_orders WHERE created_at > '$yesterday' AND created_at < '$today' AND mch_id = $mchId";
      $feeData = $db->fetch_row($sql);
      if ($feeData['total_amount']) {
        $revenueFeeMarketing += $feeData['total_amount'] * 100 * ($mchMarketingFeeRate - $mchWechatFeeRate);
      }

      $sql = "SELECT SUM(cash_fee) AS cash_fee FROM wechat_vipcard_pays WHERE created_at > '$yesterday' AND created_at < '$today' AND mch_id = $mchId";
      $feeData = $db->fetch_row($sql);
      if ($feeData['cash_fee']) {
        $revenueFeeMarketing += $feeData['cash_fee'] * ($mchMarketingFeeRate - $mchWechatFeeRate);
      }

      $sql = "SELECT SUM(cash_fee) AS cash_fee FROM wechat_groupon_pays WHERE created_at > '$yesterday' AND created_at < '$today' AND mch_id = $mchId";
      $feeData = $db->fetch_row($sql);
      if ($feeData['cash_fee']) {
        $revenueFeeMarketing += $feeData['cash_fee'] * ($mchMarketingFeeRate - $mchWechatFeeRate);
      }
    }
    if (!$leaderId) {
      $wechatPayRevenue = round($revenueFeeWechat * $profitRatio / 100, 2);
      $alipayRevenue    = round($revenueFeeAlipay * $profitRatio / 100, 2);
      $marketingRevenue    = round($revenueFeeMarketing * $profitRatio / 100, 2);
      $payRevenue = $wechatPayRevenue + $alipayRevenue;
      $dayRevenue = $payRevenue + $marketingRevenue;
      if (!$dayRevenue) {
        continue;
      }
    } else {
      $sql = "SELECT openid, profit_ratio, wait_cash_out, total_revenue FROM tuitui_users WHERE id = $leaderId";
      $ret = $db->fetch_row($sql);
      $leaderOpenId = $ret['openid'];
      $leaderProfitRatio = $ret['profit_ratio'];
      $waitCashOutLeader = $ret['wait_cash_out'];
      $totalRevenueLeader= $ret['total_revenue'];

      $userProfitRatio  = $profitRatio * $leaderProfitRatio;
      $wechatPayRevenue = round($revenueFeeWechat * $userProfitRatio / 100, 2);
      $alipayRevenue    = round($revenueFeeAlipay * $userProfitRatio / 100, 2);
      $marketingRevenue = round($revenueFeeMarketing * $userProfitRatio /100, 2);
      $payRevenue = $wechatPayRevenue + $alipayRevenue;
      $dayRevenue = $payRevenue + $marketingRevenue;
      if (!$dayRevenue) {
        continue;
      }
      
      $wechatPayRevenueLeader = round($revenueFeeWechat * ($leaderProfitRatio - $userProfitRatio), 2);
      $alipayRevenueLeader    = round($revenueFeeAlipay * ($leaderProfitRatio - $userProfitRatio), 2);
      $marketingRevenueLeader = round($revenueFeeMarketing * ($leaderProfitRatio - $userProfitRatio), 2);
      $payRevenueLeader       = $wechatPayRevenueLeader + $alipayRevenueLeader;
      $dayRevenueLeader       = $payRevenueLeader + $marketingRevenueLeader;
      $waitCashOutLeader     += $dayRevenueLeader;
      $totalRevenueLeader    += $dayRevenueLeader;
      $sql = "SELECT id FROM tuitui_user_revenue_days WHERE uid = $leaderId AND date_at = '$yesterday'";
      $ret = $db->fetch_row($sql);
      if ($ret['id']) {
        $sql = "UPDATE tuitui_user_revenue_days SET team_pay_revenue = team_pay_revenue + $payRevenueLeader, wait_cash_out = wait_cash_out + $dayRevenueLeader, day_revenue = day_revenue + $dayRevenueLeader, team_groupon_revenue = team_groupon_revenue + $marketingRevenueLeader, total_revenues = total_revenues + $dayRevenueLeader WHERE id  = $ret[id]";
      } else {
        $sql = "INSERT INTO tuitui_user_revenue_days (uid, openid, team_pay_revenue, wait_cash_out, day_revenue, team_groupon_revenue, total_revenues,date_at) VALUES ($leaderId, '$leaderOpenId', $payRevenueLeader, $waitCashOutLeader, $dayRevenueLeader, $marketingRevenueLeader, $totalRevenueLeader, '$yesterday')";
      }
      $db->query($sql);
    }
    $waitCashOut     += $dayRevenue;
    $totalRevenue    += $dayRevenue;

    $sql = "SELECT id FROM tuitui_user_revenue_days WHERE uid = $uid AND date_at = '$yesterday'";
    $ret = $db->fetch_row($sql);
    if ($ret['id']) {
      $sql = "UPDATE tuitui_user_revenue_days SET wechat_pay_revenue = wechat_pay_revenue + $wechatPayRevenue, alipay_revenue = alipay_revenue + $alipayRevenue, pay_revenue = pay_revenue + $payRevenue, groupon_revenue = groupon_revenue + $marketingRevenue, wait_cash_out = wait_cash_out + $dayRevenue, day_revenue = day_revenue + $dayRevenue, total_revenues = total_revenues + $dayRevenue WHERE id  = $ret[id]";
    } else {
      $sql = "INSERT INTO tuitui_user_revenue_days (uid, openid, pay_revenue, wechat_pay_revenue, alipay_revenue, groupon_revenue, wait_cash_out, day_revenue, total_revenues, date_at) VALUES ($uid, '$openId', $payRevenue, $wechatPayRevenue, $alipayRevenue, $marketingRevenue, $waitCashOut, $dayRevenue, $totalRevenue, '$yesterday')";
    }
    $db->query($sql);

    $sql = "UPDATE tuitui_users SET wait_cash_out = wait_cash_out + $dayRevenue, total_revenue = total_revenue + $dayRevenue WHERE id = $uid";
    $db->query($sql);

    $yesterdayTotalRevenue += $dayRevenue;
  }

  echo $yesterday . ' imported, tuishou totally revenues is: '.$yesterdayTotalRevenue.PHP_EOL;
