<?php
  //商户VLOG管理
  require_once 'common.php';
  //防止MYSQL注入
  foreach ($_GET AS $key=>&$v) {
    $v = str_replace("'", '', $v);
    $v = str_replace('"', '', $v);
  }
  foreach ($_POST AS $key=>&$v) {
    $v = str_replace("'", '', $v);
    $v = str_replace('"', '', $v);
  }

  $now = date('Y-m-d H:i:s');
  $action  = $_GET['action'];

  switch ($action) {
    case 'get_enable_groupons':
      $mchId = $_GET['mch_id'];
      $sql = "SELECT id, title FROM mch_groupons WHERE mch_id = $mchId AND is_stop = 0 AND (date_end > '$now' AND date_start < '$now') ORDER BY created_at DESC";
      $data = $db->fetch_array($sql);
      echo json_encode($data);
      break;
    case 'create':
      $mchId       = $_GET['mch_id'];
      $vedioUrl    = $_GET['vedio_url'];
      //$thumbUrl    = $_GET['thumb_url'];
      $width       = $_GET['width'];
      $height      = $_GET['height'];
      $grouponId   = $_GET['groupon_id'];
      $grouponName = $_GET['groupon_name'];
      $detail      = $_GET['detail'];
      $loves = rand(70,200);

      $thumbUrl = $vedioUrl.'?x-oss-process=video/snapshot,t_1000,f_jpg,w_'.$width.',h_'.$height.',m_fast';
      $filename = substr(md5('vlogthumb_'.$mchId.time()), 8, 16);
      $object = 'vlog/'.date('Ymd').'/'.$filename.'.jpg';
      $thumbUrl = putOssObject($object, file_get_contents($thumbUrl));

      $sql = "INSERT INTO mch_vlogs (mch_id, vedio_url, detail, groupon_id, groupon_name, thumb_url, width, height, loves, created_at) VALUES ($mchId, '$vedioUrl', '$detail', $grouponId, '$grouponName', '$thumbUrl', $width, $height, $loves, '$now')";
      $db->query($sql);
      $insertId = $db->get_insert_id();
      echo json_encode(array('id'=>$insertId));
      break;
    case 'upload_vedio':
      $mchId = $_POST['mch_id'];
      $ret = explode('.', $_FILES['file']['name']);
      $extension = end($ret);
      $tmpFile = '/tmp/vlog_'.rand(10000,99999).'.'.$extension;
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);

      $filename = substr(md5('vlog_'.$mchId.time()), 8, 16);
      $object = 'vlog/'.date('Ymd').'/'.$filename.'.mp4';
      $vedioUrl = putOssObject($object, file_get_contents($tmpFile));

      unlink($tmpFile);
      $data = array('vedio_url'=>$vedioUrl);
      echo json_encode($data);
      break;
    case 'upload_thumb':
      //真机环境下无返回缩略图
      break;
      $ret = explode('.', $_FILES['file']['name']);
      $tmpFile = '/tmp/vlogthumb_'.rand(10000,99999).'.jpg';
      move_uploaded_file($_FILES['file']['tmp_name'], $tmpFile);

      $filename = substr(md5('vlogthumb_'.time()), 8, 16);
      $object = 'vlog/'.date('Ymd').'/'.$filename.'.jpg';
      $thumbUrl = putOssObject($object, file_get_contents($tmpFile));

      unlink($tmpFile);
      $data = array('thumb_url'=>$thumbUrl);
      echo json_encode($data);
      break;
    case 'share':
      $id = $_GET['id'];
      $appId    = $_GET['appid'];

      $sql = "SELECT mch_id, detail, thumb_url FROM mch_vlogs WHERE id = $id";
      $row = $db->fetch_row($sql);
      $mchId  = $row['mch_id'];
      $imageUrl = $row['thumb_url'];
      $detail = $row['detail'];

      if (SUISHOUHUI_APP_ID == $appId) {
        $miniAccessToken = $redis->hget('keyou_mini', 'access_token');
      } else {
        $miniAccessToken = $redis->hget('keyou_suishouhui_authorizer_access_token', $appId);
      }
      $url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$miniAccessToken;
      $data = array('path'=>'pages/vlog/list?mch_id='.$mchId);
      $buffer = sendHttpRequest($url, $data);

      $filename = substr(md5('vlogshare_'.$id.time()), 8, 16);
      $shareQrCodeObject = 'vlog/'.date('Ymd').'/'.$filename.'.png';
      $shareQrCodeUrl = putOssObject($shareQrCodeObject, $buffer);

      if (mb_strlen($detail) > 10) {
        $markTitleLineOne = mb_substr($detail, 0, 10);
        $markTitleLineTwo = mb_substr($detail, 10, 10);
        $markTitleLineThree = mb_substr($detail, 20, 10);
        $markTitleLineFour = mb_substr($detail, 30, 10);
      } else {
        $markTitleLineOne = $detail;
        $markTitleLineTwo = '';
        $markTitleLineThree = '';
        $markTitleLineFour = '';
      }

      $markHeadImgUrl = $imageUrl.'?x-oss-process=image/resize,w_620,h_770,limit_0,m_fill';
      $markHeadObject = 'vlog/'.date('Ymd').'/'.substr(md5('vlog_mark'.$id.'_'.time()), 8, 8).'.jpg';
      $markedUrl = putOssObject($markHeadObject, file_get_contents($markHeadImgUrl));
      
      $url = 'https://keyoucrmcard.oss-cn-hangzhou.aliyuncs.com/vlog/assets/vlogbackground.jpg?x-oss-process=image/resize,w_750/watermark,';
      $url .= 'image_'.urlencode(base64_encode($markHeadObject)).',t_90,g_nw,x_60,y_70/watermark,';
      $markPlayIconObject = 'vlog/assets/play120.png';
      $url .= 'image_'.urlencode(base64_encode($markPlayIconObject)).',t_90,g_nw,x_330,y_400/watermark,';
      $url .= 'image_'.urlencode(base64_encode($shareQrCodeObject.'?x-oss-process=image/resize,P_28')).',t_90,g_se,x_80,y_200/watermark,';
    
      if (!$markTitleLineOne) {
        $sql = "SELECT merchant_name FROM mchs WHERE mch_id = $mchId";
        $row = $db->fetch_row($sql);
        $markTitleLineOne = $row['merchant_name'];
      }
      $lineOneObject = str_replace('/', '_', base64_encode($markTitleLineOne));
      $lineOneObject = str_replace('+', '-', $lineOneObject);
      $url .= 'text_'.$lineOneObject.',color_000000,size_35,g_sw,x_65,y_370';
      if ($markTitleLineTwo) {
        $lineTwoObject = str_replace('/', '_', base64_encode($markTitleLineTwo));
        $lineTwoObject = str_replace('+', '-', $lineTwoObject);
        $url .= '/watermark,text_'.$lineTwoObject.',size_35,g_sw,x_65,y_320';
      }
      if ($markTitleLineThree) {
        $lineThreeObject = str_replace('/', '_', base64_encode($markTitleLineThree));
        $lineThreeObject = str_replace('+', '-', $lineThreeObject);
        $url .= '/watermark,text_'.$lineThreeObject.',size_35,g_sw,x_65,y_270';
      }
      if ($markTitleLineFour) {
        $lineFourObject = str_replace('/', '_', base64_encode($markTitleLineFour));
        $lineFourObject = str_replace('+', '-', $lineFourObject);
        $url .= '/watermark,text_'.$lineFourObject.',size_35,g_sw,x_65,y_220';
      }

      $shareImgObject = 'vlog/'.date('Ymd').'/'.substr(md5('share_'.$id.'_'.time()), 8, 8).'.jpg';
      $photoUrl = putOssObject($shareImgObject, file_get_contents($url));
    
      echo $photoUrl;
      break;
    default:
      break;
  }

