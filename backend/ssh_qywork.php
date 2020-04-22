<?php
  //会员操作
  require_once 'common.php';

  $mchId = $_GET['mch_id'];
  $action  = $_GET['action'];
  $now = date('Y-m-d H:i:s');

  switch ($action) {
    case 'get_external_openid':
      $mchId  = $_GET['mch_id'];
      $externalUserId = $_GET['userid'];

      $sql = "SELECT sub_openid FROM members WHERE mch_id = $mchId AND external_userid = '$externalUserId' AND sub_openid != ''";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    default:
      break;
  }
