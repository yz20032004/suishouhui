<?php
  /*
   * 点餐桌台管理
   */
  require_once 'common.php';

  $action     = $_GET['action'];
  $now        = date('Y-m-d H:i:s');
  $mchId = $_GET['mch_id'];

  switch ($action) {
    case 'get_list':
      $sql = "SELECT * FROM mch_ordering_tables WHERE mch_id = $mchId ORDER BY table_id";
      $data= $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'get_order_detail':
      $tableId = $_GET['table_id'];
      $sql = "SELECT amount, table_name, detail, created_at FROM member_ordering_orders WHERE mch_id = $mchId AND table_id = $tableId ORDER BY id DESC LIMIT 1";
      $row = $db->fetch_row($sql);
      $row['dishes']    = unserialize($row['detail']);
      echo json_encode($row);
      break;
    case 'clear':
      $tableId = $_GET['table_id'];
      $sql = "UPDATE mch_ordering_tables SET is_seat = 0 WHERE mch_id = $mchId AND table_id = $tableId";
      $db->query($sql);

      $sql = "UPDATE member_ordering_orders SET is_pay = 1 WHERE mch_id = $mchId AND table_id = $tableId ORDER BY id DESC LIMIT 1";
      $db->query($sql);
      echo 'success';
      break;   
    case 'delete':
      $tableId = $_GET['table_id'];
      $sql = "DELETE FROM mch_ordering_tables WHERE mch_id = $mchId AND table_id = $tableId";
      $db->query($sql);
      echo 'success';
      break;
    case 'get_detail':
      $tableId = $_GET['table_id'];
      $sql = "SELECT * FROM mch_ordering_tables WHERE mch_id = $mchId AND table_id = $tableId";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'add_table':
      $tableName = addslashes(trim($_GET['table_name']));
      $seats     = addslashes(trim($_GET['seats']));     
      $sql = "SELECT id FROM mch_ordering_tables WHERE mch_id = $mchId AND table_name = '$tableName'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        echo 'fail';
      } else {
        $sql = "SELECT MAX(table_id) AS table_id FROM mch_ordering_tables WHERE mch_id = $mchId";
        $row = $db->fetch_row($sql);
        $tableId = $row['table_id'] + 1;

        $sql = "SELECT appid FROM mchs WHERE mch_id = $mchId";
        $ret = $db->fetch_row($sql);
        if (SUISHOUHUI_APP_ID == $ret['appid']) {
          $miniAccessToken = $redis->hget('keyou_mini', 'access_token');//随手惠生活ACCESSTOKEN
        } else {
          $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $ret['appid']);
        } 
        $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
        $data = array('path'=>'pages/order/index?mch_id='.$mchId.'&table_id='.$tableId);
        $buffer = sendHttpRequest($url, $data);

        $filename = substr(md5('order_'.$mchId.$tableId.time()), 8, 16);
        $object = 'order/'.date('Ymd').'/'.$filename.'.png';
        $qrcodeUrl = putOssObject($object, $buffer);

        $sql = "INSERT INTO mch_ordering_tables (mch_id, table_id, table_name, seats, qrcode_url, created_at) VALUES ($mchId, $tableId, '$tableName', $seats, '$qrcodeUrl', '$now')";
        $db->query($sql);
        echo 'success';
      }
      break;
    default:
      break;
  }
