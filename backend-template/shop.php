<?php
  //门店类操作
  require_once 'const.php';
  require_once 'lib/function.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);

  $action = $_GET['action'];
  $now = date('Y-m-d H:i:s');

  switch ($action) {
    case 'get_detail':
      $mchId = $_GET['mch_id'];
      $sql   = "SELECT * FROM shops WHERE mch_id = $mchId";
      $data  = $db->fetch_array($sql);
      $row = $data[0];
      $row['total'] = count($data);

      $sql = "SELECT id FROM app_wechat_groups WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      $row['has_wechat_group'] = $ret['id'] ? true : false ;
      echo json_encode($row);
      break;
    case 'get_membercard_openinfo':
      $mchId = $_GET['mch_id'];
      $grade = $_GET['grade'];

      $row = array();
      $today = date('Y-m-d');
      $sql = "SELECT coupon_id, coupon_name, coupon_total FROM app_opengifts WHERE mch_id = $mchId AND grade = $grade";
      $ret = $db->fetch_array($sql);
      $row['opengifts'] = $ret;

      $pointRules = $redis->hget('keyou_mch_point_rules', $mchId);
      $pointRuleData = unserialize($pointRules);
      $row['point_rule'] = '消费'.$pointRuleData['award_need_consume'].'元返1积分';
      if ($pointRuleData['can_cash']) {
        $row['point_rule'] .= ','.$pointRuleData['exchange_need_points'].'积分抵扣1元';
      } else {
        $row['point_rule'] .= ',积分可兑换礼品';
      }

      $memberDay = '';
      $sql = "SELECT id, title, point_speed, discount, reduce, coupon_id, consume, coupon_total FROM campaigns WHERE mch_id = $mchId AND campaign_type = 'member_day' AND is_stop = 0";
      $ret = $db->fetch_row($sql);
      if ($ret['id']) {
        $memberDay = $ret['title'];
        if ($ret['point_speed'] > 1) {
          $memberDay .= '积分'.round($ret['point_speed'],1).'倍加速';
        } else if ($ret['discount'] > 0) {
          $memberDay .= round($ret['discount'],1).'折优惠';
        } else if ($ret['reduce']) {
          $memberDay .= '消费每满'.$ret['consume'].'立减'.$ret['reduce'];
        } else if ($ret['coupon_id']) {
          $sql = "SELECT name FROM coupons WHERE id = $ret[coupon_id]";
          $r   = $db->fetch_row($sql);
          $memberDay .= '消费每满'.$ret['consume'].'返'.$r['name'].$ret['coupon_total'].'张';
        }
      }
      $row['member_day'] = $memberDay;

      $sql = "SELECT name, privilege, discount, point_speed FROM app_grades WHERE mch_id = $mchId AND grade = $grade";
      $row['grade_data'] = $db->fetch_row($sql);

      $sql = "SELECT merchant_shortname AS nickname, logo_url AS head_img, marketing_type FROM user_mch_submit WHERE sub_mch_id = $mchId";
      $row['app_info'] = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'get_membercard_extradata':
      $key = $_GET['key'];
      $str = $redis->get('keyou_pay_'.$key);
      $data = explode('#', $str);
      $mchId = $data[0];

      $cardId = $redis->hget('keyou_mch_card', $mchId);

      $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');
      $url = 'https://api.weixin.qq.com/card/membercard/activate/geturl?access_token='.$accessToken;
      $ret  = httpPost($url, json_encode(array('card_id'=>$cardId)));
      $data = json_decode($ret, true);

      $url  = $data['url'];
      $data = explode('&', $url);
      foreach ($data as $v) {
        if (strstr($v, 'encrypt_card_id')) {
          $encrypt_card_id = substr($v, strpos($v, '=')+1);
        } else if (strstr($v, 'biz')) {
          $biz = substr($v, strpos($v, '=')+1, strpos($v, '#')-4);
        }
      }
      $data = array('encrypt_card_id'=>$encrypt_card_id, 'biz'=>$biz);
      echo json_encode($data);
      break;
    default:
      break;
  }
