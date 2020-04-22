<?php
  /**
   * 接收来自表单大师的POST
   */
  require_once 'common.php';
  require_once dirname(__FILE__).'/unit/log.php';

  $now = date('Y-m-d H:i:s');
  $logHandler= new CLogFileHandler("/mnt/tmp/waimai_logs/".date('Ymd').'.log');
  $log = Log::Init($logHandler, 15);
  $post = file_get_contents('php://input');
  Log::DEBUG($post);

  $postData = json_decode($post, true);
  $orderAmount = $postData['amount'];
  $formId      = $postData['form_id'];
  $entryId     = $postData['id'];
  $amount      = $postData['amount'];
  $key         = $postData['ext_value'];

  $keyData     = explode('#', $redis->hget('keyou_waimai_orders', $key));
  $mchId       = $keyData[0];
  $openId      = $keyData[1];

  $cmd = 'curl -u '.JSFORM_APP_KEY.':'.JSFORM_APP_SECRET.' --header "Content-Type:application/json" --url http://api.jsform.com/api/v1/fields/'.$formId;
  exec($cmd, $output);
  $result = json_decode($output[0], true);
  $formFieldData = $result['fields'];

  $appendFieldData = unserialize($redis->hget('keyou_waimai_append_fields', $mchId));

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
      $dish        = $label.'/'.$value;
      if (!array_key_exists($fatherField, $orderData)) {
        $orderData[$fatherField] = array('dish'=>$dish, 'total'=>$total);
      } else {
        $dish = $orderData[$fatherField]['dish'].'/'.$value;
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
      if ('备注' == $label) {
        $remark = $value;
      } else {
        $orderData[$field] = array('dish'=>$label, 'total'=>$value);
      }
    }
  }
  $orderDetail = serialize(array_values($orderData));
  $sql = "INSERT INTO member_waimai_orders (mch_id, form_id, entry_id, openid, amount, detail, remark, created_at) VALUES ($mchId, '$formId', '$entryId', '$openId', $amount, '$orderDetail', '$remark', '$now')";
  $db->query($sql);
