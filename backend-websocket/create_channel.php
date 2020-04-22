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
$post_data = array();
$client_id = v('channel');
if (!$client_id) {
    render(1, '参数channel标示不能为空。');
}
$post_data['client_id'] = $client_id;
$post_data['duration'] = 3600;
$ret = $i->post('/channel/v1/create_channel', $post_data);
if (!$ret) {
    render(1, 'create channel error');
}
$ret_parse = json_decode($ret, true);
if (!$ret_parse) {
    render(2, sprintf('create channel error, return value:%s', $ret));
}
$web_socket_address = $ret_parse['data'];
// 可能页面是用的https，手工改一下协议
$web_socket_address = 'wss'.substr($web_socket_address, 2);
render(0, 'success', $web_socket_address);