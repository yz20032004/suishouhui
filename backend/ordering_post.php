<?php
  /**
   * 接收来自表单大师的POST，在线点餐
   */
  require_once 'common.php';
  require_once dirname(__FILE__).'/unit/log.php';

  $now = date('Y-m-d H:i:s');
  $logHandler= new CLogFileHandler("/mnt/tmp/order_logs/".date('Ymd').'.log');
  $log = Log::Init($logHandler, 15);
  $post = file_get_contents('php://input');
  Log::DEBUG($post);

  $postData = json_decode($post, true);
  $orderAmount = $postData['amount'];
  $formId      = $postData['form_id'];
  $entryId     = $postData['id'];
  $amount      = $postData['amount'];
  $key         = $postData['ext_value'];

  $keyData     = explode('#', $redis->hget('keyou_ordering_orders', $key));
  $mchId       = $keyData[0];
  $openId      = $keyData[1];
  $tableId     = $keyData[2];
  $postType    = $keyData[3]; //insert, append
  $payFirst    = $keyData[4];

  $cmd = 'curl -u '.JSFORM_APP_KEY.':'.JSFORM_APP_SECRET.' --header "Content-Type:application/json" --url http://api.jsform.com/api/v1/fields/'.$formId;
  exec($cmd, $output);
  $result = json_decode($output[0], true);
  $formFieldData = $result['fields'];

  $appendFieldData = unserialize($redis->hget('keyou_order_append_fields', $mchId));

  $remark    = '';
  $orderData = array();

  foreach ($postData as $field=>$value) {
    if (!strstr($field, 'field')) {
      continue;
    }
    if (!isset($formFieldData[$field])) {
      //商品多规格字段，表单大师获取不到，从redis append里获取
      $fatherField = $appendFieldData[$field];
      $label       = $formFieldData[$fatherField]['label'];
      $total       = $postData[$fatherField];
      $dish        = $label.'('.$value.')';
      if (!array_key_exists($fatherField, $orderData)) {
        $orderData[$fatherField] = array('dish'=>$dish, 'total'=>$total);
      } else {
        $dish = $orderData[$fatherField]['dish'].'('.$value.')';
        $orderData[$fatherField] = array('dish'=>$dish, 'total'=>$total);
      }
      continue;
    }
    $label = $formFieldData[$field]['label'];
    if ('Number' == $formFieldData[$field]['date_type']) {
      if (!array_key_exists($field, $orderData)) {
        $orderData[$field] = array('dish'=>$label, 'total'=>$value);
      }
    } else {
      $remark = $value;
    }
  }
  $orderDetail = serialize(array_values($orderData));

  if ($tableId) {
    $sql = "SELECT table_name FROM mch_ordering_tables WHERE mch_id = $mchId AND table_id = $tableId";
    $row = $db->fetch_row($sql);
    $tableName = $row['table_name'];
  } else {
    $tableName = '';
  }
  $outTradeNo = !$payFirst ? getOutTradeNo() : '';
  //新点单
  if ('insert' == $postType) {
    $sql = "INSERT INTO member_ordering_orders (mch_id, form_id, entry_id, openid, amount, detail, out_trade_no, table_id, table_name, remark, created_at) VALUES ($mchId, '$formId', '$entryId', '$openId', $amount, '$orderDetail', '$outTradeNo', $tableId, '$tableName', '$remark', '$now')";
    $db->query($sql);

    if (!$payFirst) {
      //占座
      $sql = "UPDATE mch_ordering_tables SET is_seat = 1 WHERE mch_id = $mchId AND table_id = $tableId";
      $db->query($sql);
      //打印预点单
      $data = array(
                  'sub_mch_id'    => $mchId,
                  'out_trade_no'  => $outTradeNo,
                  'job'           => 'print_ordering_receipt'
                ); 
      $redis->rpush('member_job_list', serialize($data));
    }
  } else {
  //加菜
    $sql = "SELECT id, out_trade_no, detail FROM member_ordering_orders WHERE mch_id = $mchId AND openid = '$openId' AND is_pay = 0 ORDER BY id DESC LIMIT 1";
    $row = $db->fetch_row($sql);
    $outTradeNo   = $row['out_trade_no'];
    $newOrderData = array_merge(array_values($orderData), unserialize($row['detail']));
    $newOrderDetail = serialize($newOrderData);
    $sql = "UPDATE member_ordering_orders SET amount = amount + $amount, detail = '$newOrderDetail'";
    if ($remark) {
      $sql .= " ,remark = '$remark'";
    }
    $sql .= " WHERE id = $row[id]";
    $db->query($sql);

    //加菜打印
    $data = array(
                'sub_mch_id'    => $mchId,
                'out_trade_no'  => $outTradeNo,
                'append_dishes' => $orderData,
                'job'           => 'print_ordering_append_receipt'
              ); 
    $redis->rpush('member_job_list', serialize($data));
  }

