<?php
/**
 * Created by PhpStorm.
 * User: 37708
 * Date: 2018/9/21
 * Time: 13:09
 */
define('ROOT', dirname(__FILE__));
require(ROOT.'/gapi.php');
require(ROOT.'/base.php');

use sinacloud\sae\Gapi;

$i = new Gapi(SAE_CHANNEL_ACCESSKEY, SAE_CHANNEL_SECRETKEY);
$client_id = v('channel');
if (!$client_id) {
    render(1, '参数channel标示不能为空。');
}
$message = v('message');
if (!$message) {
    render(2, '发送的消息不能为空。');
}
$post_data = array();
$post_data['client_id'] = $client_id;
$post_data['message'] = $message;
$ret = $i->post('/channel/v1/send_message', $post_data);
if (!$ret) {
    render(1, 'send message error');
}
$ret_parse = json_decode($ret, true);
if (!$ret_parse) {
    render(2, sprintf('send message error, return value:%s', $ret));
}
render(0, 'success');