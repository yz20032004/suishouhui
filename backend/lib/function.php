<?php
  use OSS\OssClient;
  use OSS\Core\OssException;

  function curl_file_get_contents($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_ENCODING, "gzip");
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
              "Accept-Language:zh-cn,zh;q=0.8,en-us;q=0.5,en;q=0.3",
              "Accept-Encoding:gzip, deflate",
              'Accept:image/png,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8')
      );
    //以下两项设置为FALSE时,$url可以为"https://login.yahoo.com"协议
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  FALSE);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }
  function httpPost($postUrl, $data='')
  {
    $ch = curl_init();
    // 设置URL和相应的选项
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    if ($data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }


  function sendHttpGet($url, $data='')
  { 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    if ($data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmpInfo = curl_exec($ch);
    if (curl_errno($ch)) {
    echo 'Errno'.curl_error($ch);
    }
    curl_close($ch);
    return $tmpInfo;
  }

  function sendHttpRequest($url, $data='')
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "$url");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    if ($data) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tmpInfo = curl_exec($ch);
    if (curl_errno($ch)) {
    echo 'Errno'.curl_error($ch);
    }
    curl_close($ch);
    return $tmpInfo;
  }

  function getShops($appId)
  {
    global $redis;
    $shops = explode(',', $redis->hget('keyou_app_shops', $appId));

    $shopData = array();
    foreach ($shops as $businessId) {
      $shopData[$businessId] = $redis->hget('keyou_shops', $businessId);
    }
    return $shopData;
  }

  function getOutTradeNo()
  {
    $outTradeNo = date('ymdHis').rand(10000,99999);
    return $outTradeNo;
  }

  function getTradeNo()
  {
    global $redis;
    $tradeNo = date('ymdHi').rand(100000, 999999);
    if ($redis->hget('consume_tradeno_'.date('ymd'), $tradeNo)) {
      getTradeNo();
    } else {
      $redis->hset('consume_tradeno_'.date('ymd'), $tradeNo, 1);
      $redis->expire('consume_tradeno_'.date('ymd'), 3600*24);
      return $tradeNo;
    }
  }

  function putOssObject($object, $content)
  {
    require_once dirname(__FILE__).'/aliyun-oss-php-sdk-2.2.3.phar';

    $endpoint = "oss-cn-hangzhou-internal.aliyuncs.com";
    $externalEndpoint = "oss-cn-hangzhou.aliyuncs.com";
    try {
        $ossClient = new OssClient(ALIYUN_ACCESS_KEY_ID, ALIYUN_ACCESS_KEY_SECRET, $endpoint);
        $bucket    = 'keyoucrmcard';

        $ossClient->putObject($bucket, $object, $content);

        $url = "http://$bucket.$externalEndpoint/$object";
        return $url;
    } catch (OssException $e) {
        print $e->getMessage();
        echo PHP_EOL;
    }
  }

  function getDateAt($a)
  {
    $year = substr($a, 0, 4);
    $month = substr($a, 4, 2);
    $date  = substr($a, 6, 2);
    $hour = substr($a, 8, 2);
    $minite = substr($a, 10, 2);
    $sencond = substr($a, 12, 2);
    $createdAt = $year.'-'.$month.'-'.$date.' '.$hour.':'.$minite.':'.$sencond;

    return $createdAt;
  }

  function FromXml($xml)
  {
    //added on 2018-10-08 微信防止XML注入
    $disableLibxmlEntityLoader = libxml_disable_entity_loader(true);
        //将XML转为array 
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
  }

	function ToXml($values)
	{
    	$xml = "<xml>";
    	foreach ($values as $key=>$val)
    	{
    		if (is_numeric($val)){
    			$xml.="<".$key.">".$val."</".$key.">";
    		}else{
    			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
    	}
        }
        $xml.="</xml>";
        return $xml; 
	}



	function MakeSign($values, $type='MD5')
	{
		//签名步骤一：按字典序排序参数
		ksort($values);
		$string = ToUrlParams($values);
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".WxPayConfig::KEY;
		//签名步骤三：MD5加密
    if ('MD5' == $type) {
  		$string = md5($string);
    } else {
      $string = hash_hmac('sha256', $string, WxPayConfig::KEY);
    }
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}

	function ToUrlParams($values)
	{
		$buff = "";
		foreach ($values as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}

	function postXmlCurl($xml, $url, $useCert = false, $second = 30)
	{		
        //初始化curl        
       	$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
		
        //如果有配置代理这里就设置代理
		if(WxPayConfig::CURL_PROXY_HOST != "0.0.0.0" 
			&& WxPayConfig::CURL_PROXY_PORT != 0){
			curl_setopt($ch,CURLOPT_PROXY, WxPayConfig::CURL_PROXY_HOST);
			curl_setopt($ch,CURLOPT_PROXYPORT, WxPayConfig::CURL_PROXY_PORT);
		}
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		if($useCert == true){
			//设置证书
			//使用证书：cert 与 key 分别属于两个.pem文件
			curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLCERT, WxPayConfig::SSLCERT_PATH);
			curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
			curl_setopt($ch,CURLOPT_SSLKEY, WxPayConfig::SSLKEY_PATH);
		}
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
        $data = curl_exec($ch);

		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		} else { 
			$error = curl_errno($ch);
			curl_close($ch);
			throw new WxPayException("curl出错，错误码:$error");
		}
	}

	function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}


  function getMillisecond()
	{
		//获取毫秒的时间戳
		$time = explode ( " ", microtime () );
		$time = $time[1] . ($time[0] * 1000);
		$time2 = explode( ".", $time );
		$time = $time2[0];
		return $time;
	}
