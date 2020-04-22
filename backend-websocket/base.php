<?php
/**
 * Created by PhpStorm.
 * User: 37708
 * Date: 2018/9/21
 * Time: 15:07
 */
define('SAE_CHANNEL_ACCESSKEY', 'xxxxx');
define('SAE_CHANNEL_SECRETKEY', 'xxxxxx');

// 以下是通用的函数
function v($key)
{
    $use = array_merge($_GET, $_POST);
    if (!array_key_exists($key, $use)) {
        return false;
    }
    return $use[$key];
}

function render($code, $message, $data = false)
{
    $retval = array();
    $retval['code'] = $code;
    $retval['message'] = $message;
    if ($data !== false) {
        $retval['data'] = $data;
    }
    $json = json_encode($retval);
    header('content-type:application/json');
    // 单独处理JSONP请求
    if (v('callback')) {
        echo(sprintf(';%s(%s)', v('callback'), $json));
    } else {
        echo($json);
    }
    exit(1);
}
