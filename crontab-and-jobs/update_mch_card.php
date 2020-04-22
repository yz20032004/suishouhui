<?php
  /*
   * 商户自助申请后，需要将mch_id和membercard_id对应起来
   */
  require_once 'const.php';

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);

  $mchId = $argv[1];
  $cardId= $argv[2];
  $redis->hset('keyou_card_mch', $cardId, $mchId);
  $redis->hset('keyou_mch_card', $mchId, $cardId);
  echo 'update mch_card success';
