<?php
  /**
   * 获取顾客填写商户表单数据，跳转回小程序 
   */
  require_once 'common.php';
  require_once dirname(__FILE__).'/lib/jssdk.php';

  $jssdk = new JSSDK($redis, KEYOU_MP_APP_ID, KEYOU_MP_APP_SECRET);
  $signPackage = $jssdk->getSignPackage();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<script src="https://libs.baidu.com/jquery/2.1.4/jquery.min.js"></script>
<script type="text/javascript" src="https://res.wx.qq.com/open/js/jweixin-1.3.0.js"></script>
</head>
<script type="text/javascript">
wx.config({
  debug: false,
  appId: '<?php echo $signPackage["appId"];?>',
  timestamp: <?php echo $signPackage["timestamp"];?>,
  nonceStr: '<?php echo $signPackage["nonceStr"];?>',
  signature: '<?php echo $signPackage["signature"];?>',
});
wx.ready(function(){
     wx.miniProgram.reLaunch({
      url: '/pages/form/preview',
      success:function(res){
        window.history.back();
      }
     })
});
</script>
</html>
