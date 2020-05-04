<?php
  require_once 'common.php';

  $now    = date('Y-m-d H:i:s');

  $action = $_GET['action'];
  switch ($action) {
    case 'get_detail':
      $appId = $_GET['appid'];
      $sql = "SELECT * FROM apps WHERE appid = '$appId'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    default:
      break;
  }
