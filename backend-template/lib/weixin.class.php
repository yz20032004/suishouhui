<?php
class Weixin
{
    public $token = '';//token
    public $debug =  false;//是否debug的状态标示，方便我们在调试的时候记录一些中间数据
    public $setFlag = false;
    public $msgtype = 'text';   //('text','image','location')
    public $msg = array();
 
    public function __construct($token,$debug)
    {
        $this->token = $token;
        $this->debug = $debug;
    }//获得用户发过来的消息（消息内容和消息类型  ）
    public function getMsg()
    {
        //$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postStr = file_get_contents('php://input');
        if ($this->debug) {
                        $this->write_log($postStr);
        }
        if (!empty($postStr)) {
            $this->msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $this->msgtype = strtolower($this->msg['MsgType']);
        }
    }//回复文本消息
    public function makeText($text='')
    {
        $CreateTime = time();
        $FuncFlag = $this->setFlag ? 1 : 0;
        $textTpl = "<xml>
            <ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
            <FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>%s</FuncFlag>
            </xml>";
        return sprintf($textTpl,$text,$FuncFlag);
    }//根据数组参数回复图文消息
    public function makeNews($newsData=array())
    {
        $CreateTime = time();
        $FuncFlag = $this->setFlag ? 1 : 0;
        $newTplHeader = "<xml>
            <ToUserName><![CDATA[{$this->msg['FromUserName']}]]></ToUserName>
            <FromUserName><![CDATA[{$this->msg['ToUserName']}]]></FromUserName>
            <CreateTime>{$CreateTime}</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <ArticleCount>%s</ArticleCount><Articles>";
        $newTplItem = "<item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
            </item>";
        $newTplFoot = "</Articles>
            <FuncFlag>%s</FuncFlag>
            </xml>";
        $Content = '';
        $itemsCount = count($newsData['items']);
        $itemsCount = $itemsCount < 10 ? $itemsCount : 10;//微信公众平台图文回复的消息一次最多10条
        if ($itemsCount) {
            foreach ($newsData['items'] as $key => $item) {
                if ($key<=9) {
                    $Content .= sprintf($newTplItem,$item['title'],$item['description'],$item['picurl'],$item['url']);
                }
            }
        }
        $header = sprintf($newTplHeader,$newsData['content'],$itemsCount);
        $footer = sprintf($newTplFoot,$FuncFlag);
        return $header . $Content . $footer;
    }
    public function reply($data)
    {
        if ($this->debug) {
                    $this->write_log($data);
        }
        echo $data;
    }
    public function valid()
    {
        if ($this->checkSignature()) {
            if( $_SERVER['REQUEST_METHOD']=='GET' )
            {
                echo $_GET['echostr'];
                exit;
            }
        }else{
            $this->write_log('认证失败');
            exit;
        }
    }
    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
 
        $tmpArr = array($this->token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
 
        $this->write_log('post signature is '.$signature);
        $this->write_log('my signature is '.$tmpStr);
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    private function write_log($log){
      $logFile = '/mnt/tmp/weixin_logs/'.date('Ymd').'.log';
      file_put_contents($logFile, date('Y-m-d H:i:s').PHP_EOL.$log.PHP_EOL, FILE_APPEND);
    }
}
