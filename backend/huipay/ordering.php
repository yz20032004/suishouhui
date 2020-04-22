<?php
  require_once dirname(__FILE__).'/../common.php';

  $now = date('Y-m-d H:i:s');
  $action = $_GET['action'];
  switch ($action) {
    case 'get_order_url':
      $mchId = $_GET['mch_id'];
      $openId= $_GET['openid'];
      $tableId = $_GET['table_id'];
      $sql = "SELECT is_open, formid, pay_first, order_time_start, order_time_end FROM mch_ordering_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $formId   = $row['formid'];
      $payFirst = $row['pay_first'];
      if (!$row['is_open']) {
        echo json_encode(array('errcode'=>'fail'));
      } else if (time() < strtotime(date('Y-m-d '.$row['order_time_start'])) || time() > strtotime(date('Y-m-d '.$row['order_time_end'].':00'))) {
        echo json_encode(array('errcode'=>'close'));
      } else {
        if (!$payFirst) {
          $sql = "SELECT id, openid, out_trade_no FROM member_ordering_orders WHERE mch_id = $mchId AND table_id = $tableId AND is_pay = 0 AND created_at > '".date('Y-m-d')."'";
          $row = $db->fetch_row($sql);
          if ($row['id']) {
            if ($row['openid'] == $openId) {
              echo json_encode(array('errcode'=>'repeat'));
              return;
            } else {
              echo json_encode(array('errcode'=>'hasseat', 'openid'=>$row['openid']));
              return;
            }
          }
        }

        $orderExt = $mchId.'#'.$openId;
        $key      = substr(md5($orderExt), 8, 8);
        $redis->hset('keyou_ordering_orders', $key, $orderExt.'#'.$tableId.'#insert#'.$payFirst);
        $url = 'https://biaodan.info/web/formview/'.$formId.'?ex='.$key;
        echo $url;
      }
      break;
    case 'get_order_append_url':
      $mchId   = $_GET['mch_id'];
      $openId  = $_GET['openid'];
      $tableId = $_GET['table_id'];
      $sql = "SELECT formid, pay_first FROM mch_ordering_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $formId   = $row['formid'];
      $payFirst = $row['pay_first'];

      $orderExt = $mchId.'#'.$openId;
      $key      = substr(md5($orderExt), 8, 8);
      $redis->hset('keyou_ordering_orders', $key, $orderExt.'#'.$tableId.'#append#'.$payFirst);
      $url = 'https://biaodan.info/web/formview/'.$formId.'?ex='.$key;
      echo $url;
      break;
    case 'get_order':
      $mchId   = $_GET['mch_id'];
      $openId  = $_GET['openid'];

      $orderExt = $mchId.'#'.$openId;
      $key      = substr(md5($orderExt), 8, 8);

      $sql = "SELECT pay_first FROM mch_ordering_configs WHERE mch_id = $mchId";
      $row = $db->fetch_row($sql);
      $payFirst = $row['pay_first'];

      $sql = "SELECT amount, table_id, table_name, detail FROM member_ordering_orders WHERE mch_id = $mchId AND openid = '$openId' ORDER BY id DESC LIMIT 1";
      $row = $db->fetch_row($sql);
      $row['pay_first'] = $payFirst;
      $row['dishes']    = unserialize($row['detail']);
      echo json_encode($row);
      break;
    case 'update_remind':
      $outTradeNo     = $_GET['out_trade_no'];
      $remindFunction = $_GET['remind_function'];
      $isRemind       = $_GET['is_remind'];
      $sql = "UPDATE member_waimai_orders SET $remindFunction = $isRemind WHERE out_trade_no = '$outTradeNo'";
      $db->query($sql);
      echo 'success';
      break;
    default:
      break;
  }
