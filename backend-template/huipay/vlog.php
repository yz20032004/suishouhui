<?php
  //小视频
  require_once dirname(__FILE__).'/../common.php';

  $action = $_GET['action'];
  switch ($action) {
    case 'get_detail':
      $mchId = $_GET['mch_id'];
      $page  = $_GET['page'];
      $start = $page - 1;
      $sql = "SELECT * FROM mch_vlogs WHERE mch_id = $mchId ORDER BY created_at DESC LIMIT $start, 1";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'love':
      $vlogId = $_GET['vlog_id'];
      $sql = "UPDATE mch_vlogs SET loves = loves + 1 WHERE id = $vlogId";
      $db->query($sql);
      echo 'success';
      break;
    default:
      break;
  }
