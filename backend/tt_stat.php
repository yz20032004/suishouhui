<?php
  //随手惠推推小程序操作

  require_once 'common.php';
  
  $action = $_GET['action'];
  switch ($action) {
    case 'stat_date_myshop':
      $uid = $_GET['uid'];
      $sql = "SELECT sub_mch_id AS mch_id FROM user_mch_submit WHERE uid = $uid";
      $data = $db->fetch_array($sql);
      $mchData = array();
      foreach ($data as $row) {
        $mchData[] = $row['mch_id'];
      }
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' + 1days'));
      $data = array(
                  'trade_total' => 0,
                  'trade_amount'=> 0,
                  'recharges_total' => 0,
                  'recharges'   => 0,
                  'service_fee' => 0,
                  'use_recharge'=> 0, 
                  'consumes'         => 0,
                  'coupon_fee'       => 0,
                  'consumes_wechat'  => 0,
                  'consumes_alipay'  => 0,
                  'groupon_revenue'  => 0,
                  'waimai_revenue'   => 0
                 );
      $today = date('Y-m-d');
      $sql   = "SELECT ";
      foreach ($data as $key=>$v) {
        $sql .= "SUM($key) AS $key,";
      }
      $sql .= "1=1 FROM wechat_pays_today WHERE date_at >= '$dateStart' AND date_at < '$dateEnd'";
      if ($mchData) {
        $sql .= " AND mch_id IN (".implode(',', $mchData).")";
      } else {
        $sql .= " AND mch_id = -1";
      }
      file_put_contents('/tmp/mchsql', $sql.PHP_EOL, FILE_APPEND);
      $row = $db->fetch_row($sql);
      if ($row['trade_total']) {
        $row['consumes'] = number_format($row['consumes']/100,2);
        $row['coupon_fee'] = number_format($row['coupon_fee']/100,2);
        $row['service_fee'] = number_format($row['service_fee']/100,2);
        $row['consumes_wechat'] = number_format($row['consumes_wechat']/100,2);
        $row['consumes_alipay'] = number_format($row['consumes_alipay']/100,2);
        $row['groupon_revenue'] = number_format($row['groupon_revenue']/100,2);
        $row['waimai_revenue'] = number_format($row['waimai_revenue']/100,2);
        $data = $row;
      }

      $sql = "SELECT COUNT(id) AS total FROM user_mch_submit WHERE uid = $uid AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      $data['apply_shops'] = $row['total'];
      echo json_encode($data);
      break;
    case 'stat_date_teamshop':
      $uid = $_GET['uid'];
      $sql = "SELECT sub_mch_id FROM user_mch_submit WHERE leader_uid = $uid";
      $data = $db->fetch_array($sql);
      $mchData = array();
      foreach ($data as $row) {
        $mchData[] = $row['sub_mch_id'];
      }
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' + 1days'));
      $data = array(
                  'trade_total' => 0,
                  'trade_amount'=> 0,
                  'recharges_total' => 0,
                  'recharges'   => 0,
                  'service_fee' => 0,
                  'use_recharge'=> 0, 
                  'consumes'         => 0,
                  'coupon_fee'       => 0,
                  'consumes_wechat'  => 0,
                  'consumes_alipay'  => 0,
                  'groupon_revenue'  => 0,
                  'waimai_revenue'   => 0,
                 );
      $today = date('Y-m-d');
      $sql   = "SELECT ";
      foreach ($data as $key=>$v) {
        $sql .= "SUM($key) AS $key,";
      }
      $sql .= "1=1 FROM wechat_pays_today WHERE date_at >= '$dateStart' AND date_at < '$dateEnd'";
      if ($mchData) {
        $sql .= " AND mch_id IN (".implode(',', $mchData).")";
      } else {
        $sql .= " AND mch_id = -1";
      }
      $row = $db->fetch_row($sql);
      if ($row['trade_total']) {
        $row['consumes'] = number_format($row['consumes']/100,2);
        $row['coupon_fee'] = number_format($row['coupon_fee']/100,2);
        $row['service_fee'] = number_format($row['service_fee']/100,2);
        $row['consumes_wechat'] = number_format($row['consumes_wechat']/100,2);
        $row['consumes_alipay'] = number_format($row['consumes_alipay']/100,2);
        $row['groupon_revenue'] = number_format($row['groupon_revenue']/100,2);
        $row['waimai_revenue'] = number_format($row['waimai_revenue']/100,2);
        $data = $row;
      }

      $sql = "SELECT COUNT(id) AS total FROM user_mch_submit WHERE leader_uid = $uid AND created_at > '$dateStart' AND created_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      $data['apply_shops'] = $row['total'];
      echo json_encode($data);
      break;
    case 'stat_date_revenue':
      $uid = $_GET['uid'];
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' + 1days'));
      $data = array(
                  'expand_user_total' => 0,
                  'expand_revenue'    => 0,
                  'expand_reward'     => 0,
                  'pay_revenue' => 0,
                  'wechat_pay_revenue' => 0,
                  'alipay_revenue'=> 0,
                  'groupon_revenue'   => 0,
                  'team_pay_revenue'        => 0,
                  'team_groupon_revenue'=> 0,
                  'day_revenue'         => 0
                 );
      $today = date('Y-m-d');
      $sql   = "SELECT ";
      foreach ($data as $key=>$v) {
        $sql .= "SUM($key) AS $key,";
      }
      $sql .= "1=1 FROM tuitui_user_revenue_days WHERE uid = $uid AND date_at >= '$dateStart' AND date_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      if ($row['day_revenue']) {
        $row['team_wechat_pay_coupon_revenue'] = $row['team_pay_revenue'];
        $data = $row;
      }
      echo json_encode($data);
      break;
    case 'get_revenue_today':
      $uid = $_GET['uid'];
      $date = date('Y-m-d', strtotime('-1 days'));
      $sql = "SELECT wait_cash_out, total_revenue FROM tuitui_users WHERE id = $uid";
      $row = $db->fetch_row($sql);
      if ($row) {
        echo json_encode($row);
      } else {
        $row = array('wait_cash_out'=>'0.00', 'total_revenue'=>'0.00');
        echo json_encode($row);
      }
      break;
    default:
      break;
  }
