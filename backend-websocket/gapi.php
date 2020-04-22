<?php
/**
 * Created by PhpStorm.
 * User: mingming6
 * Date: 2018/4/8
 * Time: 17:01
 */

namespace sinacloud\sae;
class Gapi
{
    private $accessKey;
    private $secretKey;
    private $gapi = 'http://g.sinacloud.com';

    public function __construct($accessKey, $secretKey)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
    }

    /**
     * 发送一个GET请求
     * @param $uri
     * @return bool|mixed
     */
    public function get($uri)
    {
        if (!$uri) {
            return false;
        }
        return $this->_curl($uri);
    }

    public function post($uri, $post_data = array())
    {
        if (!$uri) {
            return false;
        }
        return $this->_curl($uri, $post_data, 'POST');
    }

    private function _cal_sign_and_set_header($ch, $uri, $method = 'GET')
    {
        $a = array();
        $a[] = $method;
        $a[] = $uri;
        // $timeline unix timestamp
        $timeline = time();
        $b = array('x-sae-accesskey' => $this->accessKey, 'x-sae-timestamp' => $timeline);
        ksort($b);
        foreach ($b as $key => $value) {
            $a[] = sprintf("%s:%s", $key, $value);
        }
        $str = implode("\n", $a);
        $s = hash_hmac('sha256', $str, $this->secretKey, true);
        $b64_s = base64_encode($s);
        $headers = array();
        $headers[] = sprintf('x-sae-accesskey:%s', $this->accessKey);
        $headers[] = sprintf('x-sae-timestamp:%s', $timeline);
        $headers[] = sprintf('Authorization: SAEV1_HMAC_SHA256 %s', $b64_s);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        return $headers;
    }

    private function _curl($uri, $post_data = array(), $method = 'GET')
    {
        $ch = curl_init();
        $url = sprintf('%s%s', $this->gapi, $uri);
        curl_setopt($ch, CURLOPT_URL, $url);
        $this->_cal_sign_and_set_header($ch, $uri, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($post_data) {
            if (is_array($post_data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            }
        }
        $txt = curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);
        if ($error) {
            return false;
        }
        return $txt;
    }
}