<?php
  //小程序用户类操作
  require_once 'common.php';

  $action  = $_GET['action'];
  $now = date('Y-m-d H:i:s');

  switch ($action) {
    case 'get_mch':
      $openId = $_GET['openid'];
      $sql = "SELECT applyment_state, sub_mch_id, merchant_shortname FROM user_mch_submit WHERE openid = '$openId'";
      $row = $db->fetch_row($sql);
      if (!$row) {
        $sql = "SELECT sub_mch_id, merchant_shortname FROM user_mch_submit WHERE id = 1";
        $row = $db->fetch_row($sql);
        $row['demo_mode'] = true;
      } else {
        $row['demo_mode'] = false;
      }
      echo json_encode($row);
      break;
    case 'update_user_info':
      $openId = $_GET['openid'];
      $mchId  = $_GET['mch_id'];
      $avatarUrl = $_GET['avatarUrl'];
      $city      = $_GET['city'];
      $province  = $_GET['province'];
      $nickName  = $_GET['nickname'];
      $gender    = $_GET['gender'];

      $sql = "SELECT id FROM users WHERE mch_id = $mchId AND openid = '$openId'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $sql = "SELECT business_name FROM shops WHERE mch_id = $mchId";
        $row = $db->fetch_row($sql);
        $merchantName = $row['business_name'];

        $sql = "INSERT INTO users (mch_id, merchant_name, openid, head_img, role, is_admin, created_at) VALUES ($mchId, '$merchantName', '$openId', '$avatarUrl', 'assistant', 0, '$now')";
      } else {
        $sql = "UPDATE users SET head_img = '$avatarUrl', status = 1 WHERE mch_id = $mchId AND openid = '$openId'";
      }
      $db->query($sql);
      echo 'success';
      break;
    case 'bind':
      $mchId  = $_GET['mch_id'];
      $shopId = isset($_GET['shop_id'])?$_GET['shop_id']:0;
      $shopName = $_GET['shop_name'];
      $openId = $_GET['openid'];
      $name     = str_replace("'", '', $_GET['name']);
      $mobile   = str_replace("'", '', $_GET['mobile']);

      $sql = "UPDATE users SET shop_id = '$shopId', branch_name = '$shopName', name = '$name', mobile = '$mobile' WHERE mch_id = $mchId AND openid = '$openId'";
      $db->query($sql);

      $sql = "SELECT id, mch_id, shop_id, merchant_name, branch_name, name, mobile, role, is_admin, status, sms_total FROM users WHERE openid = '$openId'";
      $row = $db->fetch_row($sql);
      $row['is_demo'] = false;
      echo json_encode($row);
      break;
    case 'unbind':
      $mchId  = $_GET['mch_id'];
      $openId = $_GET['openid'];
      $sql = "UPDATE users SET status = 0 WHERE mch_id = $mchId AND openid = '$openId'";
      $db->query($sql);
      echo 'success';
      break;
    case 'login':
      $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.SSHGJ_APP_ID.'&secret='.SSHGJ_APP_SECRET.'&grant_type=authorization_code&js_code='.$_GET['js_code'];
      $ret    = httpPost($url);
      echo $ret;
      break;
    case 'qylogin':
      //企业微信登陆
      $redis->select(0);
      $suiteAccessToken = $redis->hget('keyou_qywork_open', 'sshgj_suite_access_token');
      $url = 'https://qyapi.weixin.qq.com/cgi-bin/service/miniprogram/jscode2session?suite_access_token='.$suiteAccessToken.'&js_code='.$_GET['js_code'].'&grant_type=authorization_code';
      $ret    = json_decode(httpPost($url), true);
      $corpId = $ret['corpid'];
      $userId = $ret['userid'];
      $openId = 'qywork'.getNonceStr(22);

      $sql = "SELECT mch_id FROM qywork_corpids WHERE corpid = '$corpId'";
      $r = $db->fetch_row($sql);
      $mchId = $r['mch_id'];
      $ret['mch_id'] = $mchId;
      if (!$mchId) {
        $ret['user'] = array(
                    'id'     => 0,
                    'openid' => $openId,
                    'mch_id' => 1537003461,
                    'shop_id' => 0,
                    'branch_name' => '',
                    'merchant_name' => '随手惠生活线下体验店',
                    'name'          => '体验用户',
                    'mobile'        => '',
                    'role'          => 'assistant',
                    'is_admin'      => 0,
                    'sms_total'     => 0,
                    'status'        => 1,
                    'is_demo'       => true
                    );
      }

      $sql = "SELECT id FROM users WHERE mch_id = '$mchId' AND userid = '$userId'";
      $row = $db->fetch_row($sql);
      if ($mchId && !$row['id']) {
        $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
        $r = $db->fetch_row($sql);
        $merchantName = $r['merchant_name'];

        $corpAccessToken = $redis->hget('keyou_qywork_external_access_token_list', $corpId);
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token='.$corpAccessToken.'&userid='.$userId;
        $data = json_decode(sendHttpGet($url), true);
        $mobile = $data['mobile'];

        $sql = "SELECT id FROM users WHERE mch_id = $mchId AND mobile = '$mobile'";
        $r   = $db->fetch_row($sql);
        if (!$r['id']) {
          $sql = "INSERT INTO users (mch_id, merchant_name, openid, userid, name, mobile, head_img, role, status, created_at) VALUES ($mchId, '$merchantName', '$openId', '$data[userid]', '$data[name]', '$data[mobile]', '$data[avatar]', 'assistant', 1, '$now')";
          $db->query($sql);
        } else {
          $sql = "UPDATE users SET userid = '$userId' WHERE mobile = '$mobile' AND mch_id = $mchId";
          $db->query($sql);
        }
      }
      echo json_encode($ret);
      break;
    case 'get_qy_detail':
      //企业微信员工登陆
      $mchId  = $_GET['mch_id'];
      $userId = $_GET['userid'];

      $sql = "SELECT id, userid, openid, shop_id, mch_id, merchant_name, branch_name, name, mobile, role, is_admin, status, sms_total FROM users WHERE mch_id = '$mchId' AND userid = '$userId'";
      $row = $db->fetch_row($sql);
      $row['is_demo'] = false;
      echo json_encode($row);
      break;
    case 'get_detail':
      $openId = $_GET['openid'];
      $sql = "SELECT id, shop_id, mch_id, merchant_name, branch_name, name, mobile, role, is_admin, status, sms_total FROM users WHERE openid = '$openId'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        $row = array(
                    'id'     => 0,
                    'mch_id' => 1537003461,
                    'shop_id' => 0,
                    'branch_name' => '',
                    'merchant_name' => '随手惠生活线下体验店',
                    'name'          => '体验用户',
                    'mobile'        => '',
                    'role'          => 'assistant',
                    'is_admin'      => 0,
                    'sms_total'     => 0,
                    'status'        => 1,
                    'is_demo'       => true
                    );
      } else {
        if ('admin' == $row['role']) {
          $sql = "SELECT id, applyment_state, logo_url FROM user_mch_submit WHERE openid = '$openId'";
          $ret = $db->fetch_row($sql);
          if (!$ret['id']) {
            //还未申请完成,继续体验模式
            $row['mch_id'] = 1537003461;
            $row['merchant_name'] = '随手惠生活线下体验店';
            $row['role']   = 'assistant';
            $row['is_demo'] = true;
          } else {
            if ('FINISH' == $ret['applyment_state']) {
              $row['logo_url'] = $ret['logo_url'];
              $row['is_demo'] = false;
            } else {
              $row['mch_id'] = 1537003461;
              $row['merchant_name'] = '随手惠生活线下体验店';
              $row['role']   = 'assistant';
              $row['is_demo'] = true;
            }
          }
        } else {
          $row['is_demo'] = false;
        }
      }
      echo json_encode($row);
      break;
    case 'getphonenumber':
      require_once "lib/wxBizDataCrypt.php";
      $openId        = $_GET['openid'];
      $encryptedData = $_GET['encryptedData'];
      $iv            = $_GET['iv'];
      $sessionKey    = $_GET['session_key'];

      $pc = new WXBizDataCrypt(SSHGJ_APP_ID, $sessionKey);
      $errCode = $pc->decryptData($encryptedData, $iv, $data);
      $ret = json_decode($data, true);
      $phoneNumber = $ret['phoneNumber'];
      echo $phoneNumber;
      break;
    case 'get_user':
      $openId      = $_GET['openid'];
      $phoneNumber = $_GET['mobile'];
      $sql = "SELECT id, sub_mch_id, account_name, merchant_shortname FROM user_mch_submit WHERE mobile = '$phoneNumber'";
      $row = $db->fetch_row($sql);
      if (!$row['id']) {
        echo 'fail';
        exit();
      }
      $mchId = $row['sub_mch_id'];
      $name  = $row['account_name'];
      $merchantName = $row['merchant_shortname'];

      $sql = "SELECT id FROM users WHERE openid = '$openId'";
      $ret = $db->fetch_row($sql);
      if (!$ret['id']) {    
        $sql = "INSERT INTO users (mch_id, merchant_name, openid, name, mobile, is_admin, created_at) VALUES ($mchId, '$merchantName', '$openId', '$name', '$phoneNumber', 1, '$now')";
        $db->query($sql);

        $sql = "UPDATE user_mch_submit SET openid = '$openId' WHERE mobile = '$phoneNumber'";
        $db->query($sql);
      }

      $sql = "SELECT id, mch_id, shop_id, branch_name, merchant_name, name, mobile, role, is_admin, status, sms_total FROM users WHERE openid = '$openId'";
      $row = $db->fetch_row($sql);

      echo json_encode($row);
      break;
    case 'get_reminds':
      $mchId  = $_GET['mch_id'];
      $openId = $_GET['openid'];
      $sql = "SELECT * FROM user_reminds WHERE mch_id = $mchId AND openid = '$openId'";
      $row = $db->fetch_row($sql);
      echo json_encode($row);
      break;
    case 'update_remind':
      $mchId  = $_GET['mch_id'];
      $openId = $_GET['openid'];
      $unionId= $_GET['unionid'];
      $remindFunction = $_GET['remind_function'];
      $isRemind       = $_GET['is_remind'];
      $sql = "SELECT id FROM user_reminds WHERE mch_id = $mchId AND openid = '$openId'";
      $row = $db->fetch_row($sql);
      if ($row['id']) {
        $sql = "UPDATE user_reminds SET $remindFunction = $isRemind WHERE id = $row[id]";
      } else {
        $sql = "SELECT openid FROM user_mp_openids WHERE unionid = '$unionId'";
        $ret = $db->fetch_row($sql);
        $mpOpenId = $ret['openid'];
        $sql = "INSERT INTO user_reminds (mch_id, openid, mp_openid, unionid, $remindFunction, created_at) VALUES ($mchId, '$openId', '$mpOpenId', '$unionId', $isRemind, '$now')";
      }
      $db->query($sql);
      echo 'success';
      break;
    case 'get_service_phone':
      $appId = $_GET['appid'];
      $sql = "SELECT tuitui_uid FROM tuitui_apps WHERE appid = '$appId'";
      $row = $db->fetch_row($sql);
      $uid = $row['tuitui_uid'];

      $sql = "SELECT mobile FROM tuitui_users WHERE id = $uid";
      $row = $db->fetch_row($sql);
      $mobile = $row['mobile'];
      echo $mobile;
      break;
    default:
      break;
  }

