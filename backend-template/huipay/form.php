<?php
  require_once dirname(__FILE__).'/../common.php';

  $now = date('Y-m-d H:i:s');
  $action = $_GET['action'];
  switch ($action) {
    case 'get_form_url':
      $id  = $_GET['id'];
      $sql = "SELECT is_open, formid, award_type  FROM mch_form_list WHERE id = $id";
      $row = $db->fetch_row($sql);
      $formId   = $row['formid'];
      if (!$row['is_open']) {
        echo json_encode(array('errcode'=>'fail'));
      } else {
        $mchId    = $_GET['mch_id'];
        $openId   = $_GET['openid'];
        $orderExt = $mchId.'#'.$openId;
        $key      = substr(md5($orderExt), 8, 8);
        $redis->hset('keyou_form_user_submits', $key, $orderExt);
        $url = 'https://biaodan.info/web/formview/'.$formId.'?ex='.$key;
        $data = array('errcode'=>'success', 'url'=>$url, 'form_id'=>$formId, 'award_type'=>$row['award_type']);
        echo json_encode($data);
      }
      break;
    case 'get_detail':
      $mchId   = $_GET['mch_id'];
      $openId  = $_GET['openid'];
      $formId  = $_GET['form_id'];

      $orderExt = $mchId.'#'.$openId;
      $key      = substr(md5($orderExt), 8, 8);

      $sql = "SELECT award_type, award_value FROM mch_form_list WHERE mch_id = $mchId AND formid = '$formId'";
      $row = $db->fetch_row($sql);
      $couponName = '';
      if ('coupon' == $row['award_type']) {
        $sql = "SELECT name FROM coupons WHERE id = $row[award_value]";
        $ret = $db->fetch_row($sql);
        $couponName = $ret['name'];
      }
      $row['coupon_name'] = $couponName;
      echo json_encode($row);
      break;
    default:
      break;
  }
