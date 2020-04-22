<?php
  //支付宝扫码支付
  require_once 'common.php';
  $counter = trim(addslashes($_GET['counter']));
  $sql = "SELECT mch_id, tenpay_shopid, merchant_name, counter, name FROM app_counters WHERE counter = '$counter'";
  $row = $db->fetch_row($sql);
  $mchId = $row['mch_id'];
  if (!$mchId) {
    echo '无对应门店';
    exit();
  }
  if ($row['tenpay_shopid']) {
    //转到腾讯云支付的H5支付页面
    $url = 'https://pay.qcloud.com/cpay/qrcode_shop?id='.$row['tenpay_shopid'].'&rid=5';
    header('location: '.$url);
    exit();
  }
  $merchantName = $row['merchant_name'];
  $counter      = $row['counter'];
  $counterName  = $row['name'];
  $alipayToken  = $redis->hget('alipay_code', $mchId);
  if (!$alipayToken) {
    echo '<div style="font-size:30px;text-align:center;margin-top:10%;">该门店暂未开通支付宝收款</div>';
    exit();
  }
  $alipayTokenData = json_decode($alipayToken, true);
  $appAuthToken    = $alipayTokenData['app_auth_token'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" name="viewport">
<meta content="yes" name="apple-mobile-web-app-capable">
<meta content="black" name="apple-mobile-web-app-status-bar-style">
<meta content="telephone=no" name="format-detection">
<meta content="email=no" name="format-detection">
<link rel="stylesheet" href="https://act.weixin.qq.com/static/cdn/css/wepayui/0.1.1/wepayui.min.css">
<link rel="stylesheet" href="./static/css/pay.css">
<link rel="stylesheet" href="./static/css/keyboard.css">
<style>
.alipay {
  color: #ffffff;
  font-size: 120%;
  background-color: #B2DFEE;
}
.paynum {
  font-size:25px;
}
.pay-zero {
  font-size:25px;
}
.pay-float {
  font-size:25px;
}
.weui-wepay-pay__title {
  font-size:20px;
}
.weui-wepay-pay__input{
  font-size:30px;
}
</style>
<title>向商家付款</title>
</head>
<!-- 
	通用说明： 
	1.模块的隐藏添加class:hide;
	2.body标签默认绑定ontouchstart事件，激活所有按钮的:active效果
-->
<body ontouchstart class="weui-wepay-pay-wrap">
<div class="weui-wepay-pay">
    <div class="weui-wepay-pay__bd">
        <div class="weui-wepay-pay__inner">
            <h1 class="weui-wepay-pay__title"><?php echo $merchantName;?></h1>
            <div class="weui-wepay-pay__inputs"> <strong class="weui-wepay-pay__strong">￥</strong>
                <input id="paymoney" type="number" class="weui-wepay-pay__input" placeholder="请输入金额"></div>
            <div class="weui-wepay-pay__intro">向商家询问支付金额</div>
        </div>
    </div>
    <div class="weui-wepay-pay__ft" style="display:none">
        <p class="weui-wepay-pay__info">支付金额给商户</p>
        <div class="weui-wepay-pay__btn">
        	 <img  class="weui-btn" src="https://act.weixin.qq.com/static/cdn/img/wepayui/0.1.1/wepay_logo_default_gray.svg" alt="" height="16">
        </div>
    </div>
</div>
<div></div>
<div class="payinfo">
	<table cellspacing="0" cellpadding="0">
		<tr>
			<td class="paynum">1</td>
			<td class="paynum">2</td>
			<td class="paynum">3</td>
			<td id="pay-return"><div class="keybord-return"></div></td>
		</tr>
		<tr>
			<td class="paynum">4</td>
			<td class="paynum">5</td>
			<td class="paynum">6</td>
			<td rowspan="3" class="alipay">支付</td>
		</tr>
		<tr>
			<td class="paynum">7</td>
			<td class="paynum">8</td>
			<td class="paynum">9</td>
		</tr>
		<tr>
			<td id="pay-stop"><div class="keybord-stop"></div></td>
			<td id="pay-zero" class="pay-zero">0</td>
			<td id="pay-float" class="pay-float">.</td>
		</tr>
	</table>
</div>
</body>
<script src="https://cdn.bootcss.com/jquery/1.11.0/jquery.min.js"></script>
<script src="https://gw.alipayobjects.com/as/g/h5-lib/alipayjsapi/3.1.1/alipayjsapi.inc.min.js"></script>
<script src="./static/layer/layer.js"></script>
<script type="text/javascript">
	$(function(){
		$(".payinfo").slideDown();
		var $paymoney = $("#paymoney");
		
		$("#pay-stop").click(function(){
			$(".payinfo").slideUp("fast");
		});
		
		$("#paymoney").focus(function(){
			$(".payinfo").slideDown();
       		document.activeElement.blur();
		});
		
		$(".paynum").each(function(){
			$(this).click(function(){
				if(($paymoney.val()).indexOf(".") != -1 && ($paymoney.val()).substring(($paymoney.val()).indexOf(".") + 1, ($paymoney.val()).length).length == 2){
					return;
				}
				if($.trim($paymoney.val()) == "0"){
					return;
				}
        var $new_pay = $paymoney.val() + $(this).text()
        if (parseInt($new_pay) > 10000) {
          return;
        }
        if (parseFloat($new_pay) > 0) {
          $('.alipay').css('background-color','#108ee9');
        } else {
          $('.alipay').css('background-color','#B2DFEE');
        }
				$paymoney.val($new_pay);
			});
		});
		
		$("#pay-return").click(function(){
      var $new_pay = ($paymoney.val()).substring(0, ($paymoney.val()).length - 1)
			$paymoney.val($new_pay);
      if (parseFloat($new_pay) > 0) {
        $('.alipay').css('background-color','#108ee9');
      } else {
        $('.alipay').css('background-color','#B2DFEE');
      }
		});
		
		$("#pay-zero").click(function(){
			if(($paymoney.val()).indexOf(".") != -1 && ($paymoney.val()).substring(($paymoney.val()).indexOf(".") + 1, ($paymoney.val()).length).length == 2){
				return;
			}
			if($.trim($paymoney.val()) == "0"){
				return;
			}
      var $new_pay = $paymoney.val() + $(this).text()
      if (parseInt($new_pay) > 10000) {
        return;
      }
      if (parseFloat($new_pay) > 0) {
        $('.alipay').css('background-color','#108ee9');
      } else {
        $('.alipay').css('background-color','#B2DFEE');
      }
      $paymoney.val($new_pay);
		});
		
		$("#pay-float").click(function(){
			if($.trim($paymoney.val()) == ""){
				return;
			}
		
			if(($paymoney.val()).indexOf(".") != -1){
				return;
			}
			
			if(($paymoney.val()).indexOf(".") != -1){
				return;
			}
			
			$paymoney.val($paymoney.val() + $(this).text());
		});

		$(".alipay").click(function(){
      var trade = $paymoney.val()
      if (parseFloat(trade) > 0) {

      } else {
        return;
      }
      var mch_id = '<?php echo $mchId;?>'
      var merchant_name = '<?php echo $merchantName;?>'
      var counter = '<?php echo $counter;?>'
      var counter_name = '<?php echo $counterName;?>'
      var url = 'ssh_trade.php?action=alipay_precreate&trade='+trade+'&mch_id='+mch_id+'&m_name='+merchant_name+'&counter='+counter+'&counter_name='+counter_name
      $.get(url, function (result, status) {
            if ('success' == status) {
              var pay_url = result.alipay_trade_precreate_response.qr_code
              var out_trade_no = result.alipay_trade_precreate_response.out_trade_no
              ap.pushWindow({
                url:pay_url
              })
            } else {
                alert("请求失败 status=" + status);
            }
        }, "json");
		});
	});
</script>
</html>
