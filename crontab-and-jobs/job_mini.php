<?php
  /*
   * 微信开放平台中小程序的创建、配置、上传代码和提交审核等一系列操作
   */
  require_once 'const.php';
  require_once dirname(__FILE__).'/lib/db.class.php';
  require_once dirname(__FILE__).'/lib/function.php';

  $redis = new Redis();
  $a = $redis->connect(REDIS_HOST, REDIS_PORT);
  $redis->auth(REDIS_PASSWORD);
  $redis->setOption(Redis::OPT_READ_TIMEOUT, -1); //永不超时
  $redis->select(REDIS_DB);

  $db = new DB(DBHOST, DBUSER, DBPASS, DBNAME);
  $db->query("SET NAMES utf8");

  while (true) {
    $result = $redis->blpop('keyou_mini_job_list', 1800);
    if ($result) {
      $message = unserialize($result[1]);
      $job  = $message['job'];
      switch ($job) {
        case 'init':
          init($message);
          break;
        case 'setnickname':
          setnickname($message);
          break;
        case 'modifysignature':
          modifysignature($message);
          break;
        case 'modify_domain':
          modify_domain($message);
          break;
        case 'setwebviewdomain':
          setwebviewdomain($message);
          break;
        case 'modifyheadimage':
          modifyheadimage($message);
          break;
        case 'addcategory':
          addcategory($message);
          break;
        case 'modifycategory':
          modifycategory($message);
          break;
        case 'commit':
          commit($message);
          break;
        case 'submit_audit':
          submit_audit($message);
          break;
        case 'qrcodejumppublish':
          qrcodejumppublish($message);
          break;
        case 'release':
          release($message);
          break;
        case 'qrcodejumpadd':
          qrcodejumpadd($message);
          break;
        case 're-release':
          //上传代码提交审核，用于版本更新
          commit($message);
          submit_audit($message);
          break;
        case 'initTemplateSubscribe':
          initTemplateSubscribe($message);
          break;
        case 'update_membercard_url':
          update_membercard_url($message);
          break;
        default:
          break;
      }
    }
  }

  function init($data)
  {
    global $db;
    $appId  = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];
    modify_domain($data);
    setwebviewdomain($data);
    qrcodejumpdownload($data);
    qrcodejumpadd($data);
    createopen($data);
    setnickname($data);
    modifyheadimage($data);
    addcategory($data);
    $data['signature'] = '展示优惠活动，提供会员服务';
    modifysignature($data);
    initcategory($data);
    initTemplateSubscribe($data);
    commit($data);
    submit_audit($data);
  }

  function setwebviewdomain($data)
  {
    $accessToken = $data['authorizer_access_token'];
    $url = 'https://api.weixin.qq.com/wxa/setwebviewdomain?access_token='.$accessToken;
    /*$postData = array(
                  'action' => 'get',
                );*/
    $ret = httpPost($url, '{}');
    echo 'setwebviewdomain is '.$ret.PHP_EOL;
  }

  function modify_domain($data)
  {
    $accessToken = $data['authorizer_access_token'];
    $url = 'https://api.weixin.qq.com/wxa/modify_domain?access_token='.$accessToken;
    $postData = array(
                  'action' => 'add',
                  'requestdomain' => 'coupons.keyouxinxi.com',
                  'wsrequestdomain' => 'channel.sinaapp.com'
    );
    $ret = sendHttpRequest($url, $postData);
    echo 'modify domain is '.$ret.PHP_EOL;
  }

  function qrcodejumpdownload($data)
  {
    $accessToken = $data['authorizer_access_token'];
 
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/qrcodejumpdownload?access_token='.$accessToken;
    $ret = httpPost($url, '{}');
    $retData = json_decode($ret, true);
    $fileName= $retData['file_name'];
    $fileContent = $retData['file_content'];
  
    $command = 'echo "'.$fileContent.'" > /var/www/html/keyou_template/mini/'.$fileName;
    exec($command, $out);
    echo 'qrcodejumpdownload is '.PHP_EOL;
    print_r($out);
  }

  function qrcodejumpadd($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    $sql = "SELECT mch_id FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    if (!$row['mch_id']) {
      return false;
    }
    $mchId = $row['mch_id'];
    
    $sql = "SELECT counter FROM app_counters WHERE mch_id = $mchId AND counter_type = 'scan'";
    $row = $db->fetch_row($sql);
    $counter = $row['counter'];

    $prefix = 'http://template.keyouxinxi.com/mini/h5pay.php?counter='.$counter;
    $postData = array(
      'prefix' => $prefix,
      'permit_sub_rule' => 1,
      'path'            => 'pages/index/selfpay',
      'open_version'    => 1,
      'is_edit'         => 0
    );
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/qrcodejumpadd?access_token='.$accessToken;
    $ret = sendHttpRequest($url, $postData);
    echo 'qrcodejumpadd is '.$ret.PHP_EOL;
  }

  function createopen($data)
  {
    global $db;
    $accessToken = $data['authorizer_access_token'];
    $appId       = $data['authorizer_appid'];
    $url = 'https://api.weixin.qq.com/cgi-bin/open/create?access_token='.$accessToken;
    $postData = array('appid'=>$appId);
    $ret = sendHttpRequest($url, $postData);
    $data = json_decode($ret, true);

    $openAppId = $data['open_appid'];
    $sql = "UPDATE apps SET open_appid = '$openAppId' WHERE appid = '$appId'";
    $db->query($sql);
    echo 'createopenaccount is '.$ret.PHP_EOL;
  }

  function setnickname($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    $sql = "SELECT mch_id, nickname FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    $mchId    = $row['mch_id'];
    $nickName = $row['nickname']; 

    $sql = "SELECT license_url FROM user_mch_submit WHERE sub_mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $licenseFile = '/tmp/license_'.$mchId.'.jpg';

    exec("curl -F media=@$licenseFile 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=$accessToken&type=image'", $result);
    $data = json_decode($result[0], true);
    $license = $data['media_id'];
    $url = 'https://api.weixin.qq.com/wxa/setnickname?access_token='.$accessToken;

    $postData = array(
      'nick_name' => urlencode($nickName),
      'license'   => $license
    );
    $ret = httpPost($url, urldecode(json_encode($postData)));
    unlink($licenseFile);
    echo 'setnickname is '.$ret.PHP_EOL;
  }

  function modifysignature($data)
  {
    $accessToken = $data['authorizer_access_token'];
    $signature   = $data['signature'];
    $url = 'https://api.weixin.qq.com/cgi-bin/account/modifysignature?access_token='.$accessToken;
    $postData = array(
      'signature' => urlencode($signature)
    );
    $ret = httpPost($url, urldecode(json_encode($postData)));
    echo 'modifysignature is '.$ret.PHP_EOL;
  }

  function modifyheadimage($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    $sql = "SELECT mch_id FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    $mchId = $row['mch_id'];

    $sql = "SELECT logo_url FROM user_mch_submit WHERE sub_mch_id = $mchId";
    $row = $db->fetch_row($sql);
    $headImage = $row['logo_url'];

    $headImageFile = '/tmp/headimg_'.$mchId.'.jpg';

    exec("curl -F media=@$headImageFile 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=$accessToken&type=image'", $result);
    $data = json_decode($result[0], true);
    $headImageMediaId = $data['media_id'];

    $url = 'https://api.weixin.qq.com/cgi-bin/account/modifyheadimage?access_token='.$accessToken;
    $postData = array(
      'head_img_media_id' => $headImageMediaId,
      'x1' => 0,
      'y1' => 0,
      'x2' => 1,
      'y2' => 1
    );
    $ret = sendHttpRequest($url, $postData);
    echo 'modifyheadimage is '.$ret.PHP_EOL;
  }

  function modifycategory($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    $sql = "SELECT cate_first, cate_second, first_class, second_class FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    $first = $row['cate_first'];
    $second = $row['cate_second'];
 
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/modifycategory?access_token='.$accessToken;
    $postData = array(
                        'first' => $first,
                        'second'=> $second,
                        'certicates' => array(
                                          array('key'=>urlencode('旗下有资质门店的《食品经营许可证》'), 'value'=>'mldOLIMRcrxfGDB4cVCQDk0W4GTmXCVopVAF6qMKTxSe0p9_uOs-gLSRbR7MJ8Lz'),
                                          array('key'=>urlencode('《餐饮平台与门店的管理关系声明》'), 'value'=>'l0iZsz3UhkYJM_6n9ojFZVSxNazaaEUR7zv6gd3z7Av2OlqRA0zue1k-XwPUf-cj'),
                                          array('key'=>urlencode('《微信小程序餐饮门店运营资质和责任承诺函》（含旗下所有门店名单）'), 'value'=>'1jx5fLrgbWACofZWu4WXnwb-77T1zBpWRCTj05zUtXAIQ_UiwBFPhtbHaRh4s8-j')

                                        )
                      );
    $ret = httpPost($url, urldecode(json_encode($postData)));
    echo 'modifycategory is '.$ret.PHP_EOL;
  }

  function initcategory($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    //餐饮-排队类目
    $first = 220;
    $second= 634;
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/addcategory?access_token='.$accessToken;
    $postData = array(
      'categories' => array( array(
                        'first' => $first,
                        'second'=> $second,
                                        )
                      )
    );
    $ret = sendHttpRequest($url, $postData);
    echo 'initcategory is '.$ret.PHP_EOL;
  }

  function initTemplateSubscribe($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];
    $now = date('Y-m-d H:i:s');

    $sql = "SELECT mch_id FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    $mchId = $row['mch_id'];

    $url = 'https://api.weixin.qq.com/wxaapi/newtmpl/addtemplate?access_token='.$accessToken;
    $tid = '1920';
    $title = '拼团失败通知';
    $postData = array(
                  'tid' => $tid,
                  'kidList' => array(4,5,2,3,6),
                  'sceneDesc' => $title
                );
    $ret = wwwFormRequest($url, $postData);
    $retData = json_decode($ret, true);

    if ('0' == $retData['errcode']) {
      $templateId = $retData['priTmplId'];
      $sql = "INSERT INTO app_subscribe_list (mch_id, subscribe_type, tid, title, template_id, created_at) VALUES ($mchId, 'pintuan_fail', $tid, '$title', '$templateId', '$now')";
      $db->query($sql);
    } else {
      echo $ret.PHP_EOL;
    }

    $tid = '1923';
    $title = '拼团成功通知';
    $postData = array(
                  'tid' => $tid,
                  'kidList' => array(2,3,4,7,6),
                  'sceneDesc' => $title
                );
    $ret = wwwFormRequest($url, $postData);
    $retData = json_decode($ret, true);
    if ('0' == $retData['errcode']) {
      $templateId = $retData['priTmplId'];
      $sql = "INSERT INTO app_subscribe_list (mch_id, subscribe_type, tid, title, template_id, created_at) VALUES ($mchId, 'pintuan_success', $tid, '$title', '$templateId', '$now')";
      $db->query($sql);
    } else {
      echo $ret.PHP_EOL;
    }

    $tid = '1815';
    $title = '外卖接单成功通知';
    $postData = array(
                  'tid' => $tid,
                  'kidList' => array(2,3),
                  'sceneDesc' => $title
                );
    $ret = wwwFormRequest($url, $postData);
    $retData = json_decode($ret, true);
    if ('0' == $retData['errcode']) {
      $templateId = $retData['priTmplId'];
      $sql = "INSERT INTO app_subscribe_list (mch_id, subscribe_type, tid, title, template_id, created_at) VALUES ($mchId, 'waimai_accept', $tid, '$title', '$templateId', '$now')";
      $db->query($sql);
    } else {
      echo $ret.PHP_EOL;
    }

    $tid = '1818';
    $title = '点餐成功通知';
    $postData = array(
                  'tid' => $tid,
                  'kidList' => array(2,3),
                  'sceneDesc' => $title
                );
    $ret = wwwFormRequest($url, $postData);
    $retData = json_decode($ret, true);
    if ('0' == $retData['errcode']) {
      $templateId = $retData['priTmplId'];
      $sql = "INSERT INTO app_subscribe_list (mch_id, subscribe_type, tid, title, template_id, created_at) VALUES ($mchId, 'order_success', $tid, '$title', '$templateId', '$now')";
      $db->query($sql);
    } else {
      echo $ret.PHP_EOL;
    }

    echo 'init subscribeTemplate'.PHP_EOL;
  }

  function addcategory($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    $sql = "SELECT cate_first, cate_second, first_class, second_class FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    $first = $row['cate_first'];
    $second = $row['cate_second'];

    if ('632' == $second) {
      //餐饮服务场所类目需要提交餐饮许可证
      $sql = "SELECT user_mch_submit_id FROM apps WHERE appid = '$appId'";
      $row = $db->fetch_row($sql);
      $submitId = $row['user_mch_submit_id'];

      $sql = "SELECT permit_url FROM user_mch_submit WHERE id = $submitId";
      $row = $db->fetch_row($sql);
      $permitUrl = $row['permit_url'];

      $tmpFile = '/tmp/tmppic_'.$submitId.time().'.jpg';

      exec("curl -F media=@$tmpFile 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token=$accessToken&type=image'", $result);
      unlink($tmpFile);
      $data = json_decode($result[0], true);
      $mediaId = $data['media_id'];

      $postData = array(
        'categories' => array( array(
                          'first' => $first,
                          'second'=> $second,
                          'certicates' => array(
                                            array('key'=>urlencode('旗下有资质门店的《食品经营许可证》'), 'value'=>$mediaId),
                                          )
                  ) ) );
    } else {
      initcategory($data);
      return; 
    }
    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/addcategory?access_token='.$accessToken;
    /*$postData = array(
      'categories' => array( array(
                        'first' => $first,
                        'second'=> $second,
                        //'certicates' => array(array('key'=>'', 'value'=>''))
                        'certicates' => array(
                                          array('key'=>urlencode('旗下有资质门店的《食品经营许可证》'), 'value'=>'mldOLIMRcrxfGDB4cVCQDk0W4GTmXCVopVAF6qMKTxSe0p9_uOs-gLSRbR7MJ8Lz'),
                                          //array('key'=>urlencode('《餐饮平台与门店的管理关系声明》'), 'value'=>'l0iZsz3UhkYJM_6n9ojFZVSxNazaaEUR7zv6gd3z7Av2OlqRA0zue1k-XwPUf-cj'),
                                          //array('key'=>urlencode('《微信小程序餐饮门店运营资质和责任承诺函》（含旗下所
有门店名单）'), 'value'=>'MXeU_6zekJDSHeY1IFmGlwMRHRSViIEdsFrKUq4B6l4avGptrgj7zDxLSjP8GF59')
                                        )
                      ))
    );
    print_r($postData);*/
    $ret = sendHttpRequest($url, $postData);
    echo 'addcategory is '.$ret.PHP_EOL;
  }

  function commit($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

   
    $url = 'https://api.weixin.qq.com/wxa/commit?access_token='.$accessToken;
    $extJson = array(
                            'extEnable' => true,
                            'extAppid' => $appId,
                            'ext'      => array('appid'=>$appId)
                        );
    $sql = "SELECT marketing_type FROM mchs WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    if ('groupon' == $row['marketing_type']) {
      $extJson['tabBar'] = array(
                              'color' => '#231815',
                              'selectedColor'   => '#f85f48',
                              'borderStyle'     => 'white',
                              'backgroundColor' => '#f9f9f9',
                              'list' => array(
                                          array(
                                            'pagePath' => 'pages/index/index',
                                            'text'     => '首页',
                                            'iconPath' => '/images/index.png',
                                            'selectedIconPath' => '/images/index1.png'
                                          ),
                                          array(
                                            'pagePath' => 'pages/vip/groupon_history',
                                            'text'     => '订单',
                                            'iconPath' => '/images/list1.png',
                                            'selectedIconPath' => '/images/list.png'
                                          ),
                                          array(
                                            'pagePath' => 'pages/vip/index',
                                            'text'     => '我的',
                                            'iconPath' => '/images/mine.png',
                                            'selectedIconPath' => '/images/mine1.png'
                                          )
                              )

      );
    }
    $postData = array(
      'template_id' => 16,
      'user_version' => '2.4',
      'user_desc'    => '修复电商功能',
      'ext_json'     =>  json_encode($extJson)
    );
    $ret = sendHttpRequest($url, $postData);
    echo 'commit is '.$ret.PHP_EOL;
  }

  function submit_audit($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    $sql = "SELECT nickname, cate_first, cate_second, first_class, second_class FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    
    $url = 'https://api.weixin.qq.com/wxa/submit_audit?access_token='.$accessToken;
    $postData = array(
      'item_list' => array(array(
                      'address' => 'pages/index/index',
                      'first_class' => urlencode($row['first_class']),
                      'second_class'=> urlencode($row['second_class']),
                      'first_id'    => $row['cate_first'],
                      'second_id'   => $row['cate_second'],
                      'title'       => urlencode($row['nickname'])
                     ))
    );
    $ret = httpPost($url, urldecode(json_encode($postData)));
    echo 'commit is '.$ret.PHP_EOL;
  }

  function release($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];
    $url = 'https://api.weixin.qq.com/wxa/release?access_token='.$accessToken;
    $ret = httpPost($url, '{}');
    echo 'release is '.$ret.PHP_EOL;

    $resultData = json_decode($ret, true);
    $resultData['errcode'] = '0';
    if ('0' == $resultData['errcode']) {
      $sql = "SELECT mch_id FROM apps WHERE appid = '$appId'";
      $row = $db->fetch_row($sql);
      $mchId = $row['mch_id'];
      $sql = "SELECT card_url FROM shops WHERE mch_id = $mchId";
      $ret = $db->fetch_row($sql);
      if ($ret['card_url']) {
        //return;
      }
      qrcodejumppublish($data);

      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$accessToken;
      $data = array('path'=>'pages/index/get_membercard?mch_id='.$mchId);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('membercard_'.$mchId.time()), 8, 16);
      $object   = 'xiaowei/'.date('Ymd').'/'.$filename.'.png';
      $cardUrl  = putOssObject($object, $buffer);
      echo 'getmembercard url is'.$cardUrl.PHP_EOL;
      $sql = "UPDATE shops SET card_url = '$cardUrl' WHERE mch_id = $mchId";
      $db->query($sql);
    }
  }

  function qrcodejumppublish($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    $sql = "SELECT mch_id FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    $mchId = $row['mch_id'];

    $sql = "SELECT counter FROM app_counters WHERE mch_id = $mchId AND counter_type = 'scan'";
    $row = $db->fetch_row($sql);
    $counter = $row['counter'];

    $url = 'https://api.weixin.qq.com/cgi-bin/wxopen/qrcodejumppublish?access_token='.$accessToken;
    $prefix = 'http://template.keyouxinxi.com/mini/h5pay.php?counter='.$counter;
    $postData = array(
      'prefix' => $prefix
    );
    print_r($postData);
    $ret = sendHttpRequest($url, $postData);
    echo 'qrcodejumppublish is '.$ret.PHP_EOL;
  }

  function update_membercard_url($data)
  {
    global $db;
    $appId       = $data['authorizer_appid'];
    $accessToken = $data['authorizer_access_token'];

    $sql = "SELECT mch_id FROM apps WHERE appid = '$appId'";
    $row = $db->fetch_row($sql);
    $mchId = $row['mch_id'];

    $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$accessToken;
    $data = array('path'=>'pages/index/get_membercard?mch_id='.$mchId);
    $buffer = sendHttpRequest($url, $data);

    $filename = substr(md5('membercard_'.$mchId.time()), 8, 16);
    $object   = 'xiaowei/'.date('Ymd').'/'.$filename.'.png';
    $cardUrl  = putOssObject($object, $buffer);
    echo 'getmembercard url is'.$cardUrl.PHP_EOL;
    $sql = "UPDATE shops SET card_url = '$cardUrl' WHERE mch_id = $mchId";
    $db->query($sql);
  }

  function wwwFormRequest($url, $data='')
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    if ($data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmpInfo = curl_exec($ch);
    if (curl_errno($ch)) {
    echo 'Errno'.curl_error($ch);
    }
    curl_close($ch);
    return $tmpInfo;
  }
