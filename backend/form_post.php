<?php
  /**
   * 接收来自表单大师的POST,调研有礼
   */
  require_once 'common.php';
  require_once dirname(__FILE__).'/unit/log.php';

  $now = date('Y-m-d H:i:s');
  $logHandler= new CLogFileHandler("/mnt/tmp/form_logs/".date('Ymd').'.log');
  $log = Log::Init($logHandler, 15);
  $post = file_get_contents('php://input');
  Log::DEBUG($post);

  $postData = json_decode($post, true);
  $formId      = $postData['form_id'];
  $entryId     = $postData['id'];
  $key         = $postData['ext_value'];

  $keyData     = explode('#', $redis->hget('keyou_form_user_submits', $key));
  $mchId       = $keyData[0];
  $subOpenId   = $keyData[1];

  $sql = "SELECT award_type, award_value FROM mch_form_list WHERE mch_id = $mchId AND formid = '$formId'";
  $row = $db->fetch_row($sql);
  $couponName = '';
  if ('point' == $row['award_type']) {
    $awardPoint = $row['award_value'];
    $sql = "UPDATE members SET point = point + $awardPoint WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
    $db->query($sql);

    $sql = "INSERT INTO member_point_history (mch_id, openid, modify_point, detail, created_at) VALUES ($mchId, '$subOpenId', $awardPoint, '参与活动奖励积分', '$now')";
    $db->query($sql);

    $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
    $data = array(
                  'job'          => 'update_member_point',
                  'wx_access_token' => $accessToken,
                  'mch_id'       => $mchId,
                  'sub_openid'   => $subOpenId,
                  'point'        => $awardPoint,
                  );
    $redis->rpush('member_job_list', serialize($data));
  }
