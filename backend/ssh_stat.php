<?php
  //统计操作
  require_once 'common.php';

  $now = date('Y-m-d H:i:s');
  $action  = $_GET['action'];
  $dateAt  = date('Y-m-d', strtotime('-1 days'));

  switch ($action) {
    case 'get_date':
      $mchId = $_GET['mch_id'];
      $shopId= isset($_GET['shop_id'])?$_GET['shop_id']:0;
      $dateStart = $_GET['date_start'];
      $dateEnd   = date('Y-m-d', strtotime($_GET['date_end'].' + 1days'));
      $data = array(
                  'members'     => 0,
                  'trade_total' => 0,
                  'member_trade_total' => 0,
                  'trade_amount'=> 0,
                  'recharges_total' => 0,
                  'recharges'   => 0,
                  'save'        => 0,
                  'service_fee' => 0,
                  'service_fee_wechat' => 0,
                  'service_fee_alipay' => 0,
                  'use_recharge'=> 0, 
                  'member_discount'  => 0,
                  'use_coupon_total' => 0,
                  'use_coupon_amount'=> 0,
                  'point_amount'     => 0,
                  'get_point'        => 0,
                  'reduce'           => 0,
                  'discount'         => 0,
                  'member_consumes'  => 0,
                  'consumes'         => 0,
                  'coupon_fee'       => 0,
                  'use_point'        => 0,
                  'refund_fee_wechat'=> 0,
                  'consumes_wechat'  => 0,
                  'consumes_alipay'  => 0,
                  'consumes_other'   => 0,
                  'vipcard_revenue'  => 0,
                  'waimai_revenue'   => 0,
                  'mall_revenue'     => 0,
                  'groupon_revenue'  => 0
                 );
      $today = date('Y-m-d');
      $sql   = "SELECT ";
      foreach ($data as $key=>$v) {
        $sql .= "SUM($key) AS $key,";
      }
      $sql .= "1=1 FROM wechat_pays_today WHERE mch_id = $mchId AND date_at >= '$dateStart' AND date_at < '$dateEnd'";
      if ($shopId) {
        $sql .= " AND shop_id = $shopId";
      }
      $row = $db->fetch_row($sql);
      if ($row['trade_total']) {
        $row['consumes'] = $row['consumes']/100;
        $row['member_consumes'] = $row['member_consumes']/100;
        $row['coupon_fee'] = $row['coupon_fee']/100;
        $row['service_fee'] = $row['service_fee']/100;
        $row['service_fee_wechat'] = $row['service_fee_wechat']/100;
        $row['service_fee_alipay'] = $row['service_fee_alipay']/100;
        $row['refund_fee_wechat'] = $row['refund_fee_wechat']/100;
        $row['consumes_wechat'] = $row['consumes_wechat']/100;
        $row['consumes_alipay'] = $row['consumes_alipay']/100;
        $row['consumes_other'] = $row['consumes_other']/100;
        $row['waimai_revenue'] = $row['waimai_revenue']/100;
        $row['groupon_revenue'] = $row['groupon_revenue']/100;
        $row['mall_revenue'] = $row['mall_revenue']/100;
        $data = $row;
      }

      $paramData = array(
                      'total' => 0,
                      'trade' => 0,
                      'coupon_total' => 0,
                      'coupon_expired'=>0,
                      'coupon_used'   =>0,
                      'refund'        =>0,
                      'revenue'       =>0,
                      'distribute_bonus' => 0,
                      'together_total'=>0,
                      'together_success' => 0
                  );
      $sql   = "SELECT ";
      foreach ($paramData as $key=>$v) {
        $sql .= "SUM($key) AS $key,";
      }
      $sql .= "1=1 FROM wechat_groupon_pays_today WHERE mch_id = $mchId AND date_at >= '$dateStart' AND date_at < '$dateEnd'";
      $row = $db->fetch_row($sql);
      foreach ($row as $key=>&$v) {
        $v = $v ? $v : 0;
      }
      $row['trade'] = $row['trade']/100;
      $row['revenue'] = $row['revenue']/100;
      $row['refund'] = $row['refund']/100;
      $row['distribute_bonus'] = $row['distribute_bonus']/100;
      $row['coupon_used_rate'] = $row['coupon_total'] ? round($row['coupon_used']/$row['coupon_total'],2) : '-';
      $data['groupon'] = $row;
      echo json_encode($data);
      break;
    case 'campaign_detail':
      $data = array();
      $couponId = $_GET['coupon_id'];
      $shopId   = $_GET['shop_id'];
      $dateStart = date('Y-m-d', strtotime('-30 days'));
      if ($shopId == 0) {
        $sql = "SELECT date_format(date_at, '%c-%d') AS date_at, bring_trade_count, bring_trade_amount FROM daily_coupons WHERE appid = '$appId' AND coupon_id = '$couponId' AND date_at >= '$dateStart' AND date_at <= '$dateAt' GROUP BY date_at";
      } else {
        $sql = "SELECT date_format(date_at, '%c-%d') AS date_at, SUM(bring_trade_count) AS bring_trade_count, SUM(bring_trade_amount) AS bring_trade_amount FROM daily_coupons WHERE appid = '$appId' AND shop_id = '$shopId' AND coupon_id = '$couponId' AND date_at >= '$dateStart' AND date_at <= '$dateAt' GROUP BY date_at";
      }
      $ret = $db->fetch_array($sql);
      foreach ($ret as $k=>$v) {
        $data['date'][$k] = $v['date_at'];
        $data['count'][$k] = $v['bring_trade_count'];
        $data['amount'][$k] = $v['bring_trade_amount'];
      }
      echo json_encode($data);
      break;
    case 'coupon_used':
      $appId = $_GET['appid'];
      $dateStart  = date('Y-m-d', strtotime($_GET['date_start']));
      $dateEnd    = date('Y-m-d', strtotime($_GET['date_end'].'+1 days'));
      $sql = "SELECT coupon_id, COUNT(id) AS total FROM coupons_used WHERE appid = $appId AND created_at > '$dateStart' AND created_at < '$dateEnd' GROUP BY coupon_id";
      $data = $db->fetch_array($sql);
      if ($data) {
        foreach ($data as &$row) {
          $sql = "SELECT name FROM coupons WHERE id = $row[coupon_id]";
          $ret = $db->fetch_row($sql);
          $row['name'] = $ret['name'];
        }
      }
      echo json_encode($data);
      break;
    case 'coupon_used_list':
      $couponId   = $_GET['coupon_id'];
      $dateStart  = date('Y-m-d', strtotime($_GET['date_start']));
      $dateEnd    = date('Y-m-d', strtotime($_GET['date_end'].'+1 days'));
      $sql = "SELECT mobile, created_at FROM coupons_used WHERE coupon_id = $couponId AND created_at > '$dateStart' AND created_at < '$dateEnd' ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    default:
      break;
  }
