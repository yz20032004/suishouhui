<?php
  require_once dirname(__FILE__).'/../common.php';

  $action = $_GET['action'];
  switch ($action) {
    case 'list':
      $mchId = $_GET['mch_id'];
      $pointRuleData = unserialize($redis->hget('keyou_mch_point_rules', $mchId));

      $sql = "SELECT * FROM app_grades WHERE mch_id = $mchId ORDER BY grade";
      $data = $db->fetch_array($sql);
      foreach ($data as &$row) {
        $row['point_rule'] = $pointRuleData;
      }
      echo json_encode($data);
      break;
    default:
      break;
  }
