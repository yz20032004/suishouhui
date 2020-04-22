<?php
  require_once dirname(__FILE__).'/../common.php';

  $action = $_GET['action'];
  switch ($action) {
    case 'list':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT * FROM app_recharge_rules WHERE mch_id = $mchId ORDER BY touch";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        if ('coupon' == $row['award_type']) {
          $sql = "SELECT coupon_id, coupon_name, total FROM app_recharge_coupon_rules WHERE recharge_id = $row[id]";
          $ret = $db->fetch_array($sql);
          $row['coupons'] = $ret;
        }
      }
      echo json_encode($data);
      break;
    case 'get_detail':
      $id  = $_GET['id'];
      $sql = "SELECT * FROM app_recharge_rules WHERE id = $id";
      $row = $db->fetch_row($sql);
      $mchId = $row['mch_id'];
      if ('coupon' == $row['award_type']) {
        $sql = "SELECT coupon_id, coupon_name, total FROM app_recharge_coupon_rules WHERE recharge_id = $row[id]";
        $ret = $db->fetch_array($sql);
        foreach ($ret as &$v) {
          $sql = "SELECT consume_limit, validity_type, total_days, is_usefully_sendday, date_start, date_end FROM coupons WHERE id = $v[coupon_id]";
          $r   = $db->fetch_row($sql);
          $v['expire_data'] = $r;
        }
        $row['coupons'] = $ret;
      }
      
      $sql = "SELECT recharge_point_speed FROM app_point_rules WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $row['recharge_point_speed'] = $ret['recharge_point_speed'];
      echo json_encode($row);
      break;
    default:
      break;
  }
