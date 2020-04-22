<?php
  require_once dirname(__FILE__).'/../common.php';

  $action = $_GET['action'];
  $mchId  = isset($_GET['mch_id'])?$_GET['mch_id']:$_POST['mch_id'];
  $now    = date('Y-m-d H:i:s');

  switch ($action) {
    case 'get_detail':
      $sql = "SELECT * FROM mchs WHERE mch_id = '$mchId'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_top_background_img':
      $sql = "SELECT * FROM mch_index_top_images WHERE mch_id = $mchId ORDER BY id";
      $data= $db->fetch_array($sql);
      if (!$data) {
        $sql = "SELECT logo_url AS pic_url FROM user_mch_submit WHERE sub_mch_id = $mchId";
        $data = $db->fetch_array($sql);
      }
      echo json_encode($data);
      break;
    default:
      break;
  }
