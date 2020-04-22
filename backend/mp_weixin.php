<?php
  /*
   * 服务商公众号的消息接口
   * 用于接收用户领取会员卡等事件接收的后续操作
   */
  include_once('const.php');
  include_once('lib/weixin.class.php');
  include_once('lib/db.class.php');
  include_once('lib/function.php');
  $db = new DB(DBHOST, DBUSER_SUISHOUHUI, DBPASS_SUISHOUHUI, DBNAME_SUISHOUHUI);
  $db->query("SET NAMES utf8");

  $redis = new Redis();
  $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->select(REDIS_DB);

  $accessToken = $redis->hget('keyouxinxi', 'wx_access_token');

  $now = date('Y-m-d H:i:s');
  $weixin = new Weixin(MP_TOKEN, DEBUG);
  //$weixin->valid();
  $weixin->getMsg();
  $type = $weixin->msgtype;
  $openId = $weixin->msg['FromUserName'];
  if ($type==='text') {
    $reply = $weixin->makeText('ad');
  }elseif ($type==='location') {
  }elseif ($type==='voice') {     
  }elseif ($type==='event') {
    $event = $weixin->msg['Event'];
    switch ($event) {
      //这个事件包括了领取会员卡和领取卡券两种
      case 'user_get_card':
        $cardId = $weixin->msg['CardId'];
        $code   = $weixin->msg['UserCardCode'];
        $unionId= $weixin->msg['UnionId'];
        $mchId   = $redis->hget('keyou_card_mch', $cardId);
        //12位CODE是优惠券编号，也有可能是会员卡号
        if ($mchId) {
          $sql = "UPDATE members SET openid = '$openId', unionid = '$unionId', is_deleted = 0, member_cardid = '$cardId', cardnum = '$code' WHERE mch_id = $mchId AND (unionid = '$unionId' OR openid = '$openId')";
          file_put_contents('/tmp/sql', $sql.PHP_EOL, FILE_APPEND);
          $db->query($sql);
        } else {
          //优惠券是否来自好友转赠,暂不实现
          $sql = "SELECT * FROM coupons WHERE wechat_cardid = '$cardId'";
          $row = $db->fetch_row($sql);
          $couponId = $row['id'];
          $mchId    = $row['mch_id'];
          $couponType= $row['coupon_type'];

          $sql = "SELECT sub_openid, mch_id, nickname, headimgurl, province, city, gender FROM members WHERE unionid = '$unionId' OR openid = '$openId'";
          $member = $db->fetch_row($sql);
          $subOpenId = $member['sub_openid'];
          if ($subOpenId) {
            $sql = "UPDATE member_coupons SET code = '$code', in_wechat = 1 WHERE coupon_id = $couponId AND openid = '$subOpenId' AND in_wechat = 0 AND status = 1 LIMIT 1";
            file_put_contents('/tmp/sql', $sql.PHP_EOL, FILE_APPEND);
            $db->query($sql);
            $affected =  $db->affected_rows();
            if ('groupon' != $couponType && !$affected) {
              //扫码领优惠券,团购券不可以直接扫码领券
              $couponName = $row['name'];
              $amount     = $row['amount'];
              $discount   = $row['discount'];
              $detail     = $row['description'];
              $consumeLimit = $row['consume_limit'];

              if ('relative' == $row['validity_type']) {
                $dateStart = $row['is_usefully_sendday'] ? date('Y-m-d') : date('Y-m-d', strtotime('+1 days'));
                $totalDays = $row['total_days'] - 1;
                $dateEnd   = date('Y-m-d', strtotime('+'.$totalDays.' days'));
              } else {
                $dateStart = $row['date_start'];
                $dateEnd   = $row['date_end'];
              }

              $sql = "INSERT INTO member_coupons (mch_id, openid, coupon_id, coupon_type, coupon_name, amount, discount, consume_limit, code, in_wechat, detail, date_start, date_end, get_type, created_at) VALUES ('$mchId', '$subOpenId', '$couponId', '$couponType', '$couponName', '$amount', '$discount', '$consumeLimit', '$code', 1, '$detail', '$dateStart', '$dateEnd', 'get', '$now')";
              $db->query($sql);

              $sql = "UPDATE members SET coupons = coupons + 1 WHERE mch_id = $mchId AND sub_openid = '$subOpenId'";
              $db->query($sql);
              $affectedRows = $db->affected_rows();
              if (!$affectedRows) {
                //顾客多会员卡，在其它商户有会员，领券商户无会员卡，自动获取该顾客会员卡信息插入领券商户会员表
                $sql = "INSERT INTO members (mch_id, openid, sub_openid, unionid, grade, grade_title, nickname, headimgurl, province, city, gender, coupons, created_at) VALUES ($mchId, '$openId', '$subOpenId', '$unionId', 0, '粉丝', '$member[nickname]', '$member[headimgurl]', '$member[province]', '$member[city]', $member[gender], 1, '$now')";
                file_put_contents('/tmp/sql', $sql.PHP_EOL, FILE_APPEND);
                $db->query($sql);
              }
            }
            $data = array('job'=>'update_member_card', 'access_token'=>$accessToken, 'point'=>'0', 'detail'=>'', 'mch_id'=>$mchId, 'openid'=>$subOpenId, 'is_member'=>true);
            $redis->rpush('member_job_list', serialize($data));
          }
        }
        break;
      case 'submit_membercard_user_info':
        $cardId = $weixin->msg['CardId'];
        $code = $weixin->msg['UserCardCode'];
        $mchId   = $redis->hget('keyou_card_mch', $cardId);
        //拉取会员数据
        $url = 'https://api.weixin.qq.com/card/membercard/userinfo/get?access_token='.$accessToken;
        $data = array(
                'card_id' => $cardId,
                'code'    => $code
                );
        $ret = httpPost($url, json_encode($data));
        $r   = json_decode($ret, true);
        
        $birthday = '';
        foreach ($r['user_info']['common_field_list'] as $field) {
          if ('USER_FORM_INFO_FLAG_MOBILE' == $field['name']) {
            $mobile = $field['value'];
          } else if ('USER_FORM_INFO_FLAG_SEX' == $field['name']) {
            $sex  = $field['value'];
          } else if ('USER_FORM_INFO_FLAG_NAME' == $field['name']) {
            $name   = $field['value'];
          } else if ('USER_FORM_INFO_FLAG_BIRTHDAY' == $field['name']) {
            $birthday = $field['value'];
          }
        }
        $sql = "UPDATE members SET mobile = '$mobile', name = '$name', birthday = '$birthday' WHERE cardnum = '$code'";
        $db->query($sql);

        $today = date('Y-m-d');
        $sql = "SELECT id FROM wechat_pays_today WHERE mch_id = $mchId AND date_at = '$today'";
        $row = $db->fetch_row($sql);
        if ($row['id']) {
          $sql = "UPDATE wechat_pays_today SET members = members + 1 WHERE id = $row[id]";
        } else {
          $sql = "INSERT INTO wechat_pays_today (mch_id, members, date_at, updated_at) VALUES ($mchId, 1, '$today', '$now')";
        }
        $db->query($sql);

        if (!$mobile) {
          $mobile = $code;
        }
        $mchId = $redis->hget('keyou_card_mch', $cardId);
        $sql = "SELECT coupons, grade, grade_title FROM members WHERE mch_id = $mchId AND cardnum = '$code'";
        $row = $db->fetch_row($sql);
        $url = 'https://api.weixin.qq.com/card/membercard/activate?access_token='.$accessToken;
        $data = array(
                  'membership_number' => $mobile,
                  'code'              => $code,
                  'card_id'           => $cardId,
                );
        if ($row['grade']) {
          //先购买了付费卡，然后再开卡激活,开卡礼已经写入
          $data['init_custom_field_value1'] = $row['coupons'].urlencode('张');
          $data['init_custom_field_value2'] = urlencode($row['grade_title']);
        }
        $ret = httpPost($url, json_encode($data));

        if (!$row['grade']) {
          $data = array('job'=>'update_member_opengift', 'access_token'=>$accessToken, 'mch_id'=>$mchId, 'code'=>$code, 'point'=>0);
          $redis->rpush('member_job_list', serialize($data));
        }
        break;
      case 'user_consume_card':
        $cardId = $weixin->msg['CardId'];
        $code   = $weixin->msg['UserCardCode'];
        //12位CODE是优惠券编号，11位CODE是会员卡手机号
        if ('12' == strlen($code)) {
          $sql = "UPDATE member_coupons SET status = 0 WHERE code = '$code'";
          $db->query($sql);
        }
      case 'user_del_card':
        $cardId = $weixin->msg['CardId'];
        $code   = $weixin->msg['UserCardCode'];
        //12位CODE是优惠券编号，也有可能是会员卡号,若可以查到merchantId,则是会员卡号，否则是优惠券号
        $sql = "UPDATE member_coupons SET status = 0 WHERE code = '$code'";
        $ret = $db->query($sql);
        if (!$ret) {
          $sql = "UPDATE members SET is_deleted = 1 WHERE cardnum = '$code' AND mch_id = '$mchId'";
          $db->query($sql);
        }
        break;
      case 'user_gifting_card':
        //转赠卡券
        break;
      case 'subscribe':
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$accessToken.'&openid='.$openId.'&lang=zh_CN';
        $ret = httpPost($url);
        $r   = json_decode($ret, true);
        $unionId = $r['unionid'];
        $nickname= $r['nickname'];
        $sql = "INSERT INTO user_mp_openids (openid, unionid, nickname, created_at) VALUES ('$openId', '$unionId', '$nickname', '$now')";
        $db->query($sql);
        break;
      default:
        break;
    }
  }
  
  if (isset($reply)) {
    $weixin->reply($reply);
  }
 
