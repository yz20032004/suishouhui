<?php
  require_once dirname(__FILE__).'/const.php';
  require_once dirname(__FILE__).'/lib/function.php';
  require_once dirname(__FILE__).'/lib/db.class.php';

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);
