<?php
  //监控脚本是否正常执行
  require_once 'const.php';
  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);

  $needWarningJobData = array(
    'job_load_trade',
    'job_update_member',
    'job_mch',
    'job_profit_share',
    'job_send_sms',
  ); 
  $smsTempId = 'SMS_177256782';
  $now = date('Y-m-d H:i:s');

  foreach ($needWarningJobData as $job) {
    exec('ps aux | grep '.$job, $ret);
    if (count($ret) == '1') {
      $smsParam = json_encode(array('job_name'=>$job));
      $smsData = array('template_code'=>$smsTempId, 'sms_params'=>$smsParam, 'mobile'=>'13917486084');
      //$redis->rpush('keyou_sms_job_list', serialize($smsData));
      echo $job.'.php stop on '.$now.PHP_EOL;
      exec('nohup /usr/local/php/bin/php '.$job.'.php >> /tmp/'.$job.'.log 2>&1 &');
    }
    $ret = array();
  }
