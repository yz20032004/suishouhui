<?php
require_once "lib/WxPay.Api.php";
/**
 * 刷卡支付实现类
 */

class MicroPay
{
	/**
	 * 
	 * 提交刷卡支付，并且确认结果，接口比较慢
	 * @param WxPayMicroPay $microPayInput
	 * @throws WxpayException
	 * @return 返回查询接口的结果
	 */
	public function pay($microPayInput)
	{
		$result = WxPayApi::micropay($microPayInput, 5);
		//如果返回成功
		if(!array_key_exists("return_code", $result)
			|| !array_key_exists("out_trade_no", $result)
			|| !array_key_exists("result_code", $result))
		{
			throw new WxPayException("接口调用失败！");
		}
		
		//签名验证
		$out_trade_no = $microPayInput->GetOut_trade_no();
		
		//接口调用成功，明确返回调用失败
		if($result["return_code"] == "SUCCESS" &&
		   $result["result_code"] == "FAIL" && 
		   $result["err_code"] != "USERPAYING" && 
		   $result["err_code"] != "SYSTEMERROR")
		{
			return false;
		}

		//确认10次
		$queryTimes = 10;
		while($queryTimes > 0)
		{
			$succResult = 0;
			$queryResult = $this->query($out_trade_no, $succResult);
			//如果需要等待1s后继续
			if($succResult == 2){
				sleep(2);
				continue;
			} else if($succResult == 1){//查询成功
				return $queryResult;
			} else {//订单交易失败
				return false;
			}
		}
		
		//10次确认失败，则撤销订单
		if(!$this->cancel($out_trade_no))
		{
			throw new WxpayException("撤销单失败！");
		}

		return false;
	}
	
	/**
	 * 
	 * 查询订单情况
	 * @param string $out_trade_no  商户订单号
	 * @param int $succCode         查询订单结果
	 * @return 0 订单不成功，1表示订单成功，2表示继续等待
	 */
	public function query($out_trade_no, &$succCode)
	{
		$queryOrderInput = new WxPayOrderQuery();
		$queryOrderInput->SetOut_trade_no($out_trade_no);
		$result = WxPayApi::orderQuery($queryOrderInput);
		
		if($result["return_code"] == "SUCCESS" 
			&& $result["result_code"] == "SUCCESS")
		{
			//支付成功
			if($result["trade_state"] == "SUCCESS"){
				$succCode = 1;
			   	return $result;
			}
			//用户支付中
			else if($result["trade_state"] == "USERPAYING"){
				$succCode = 2;
				return false;
			}
		}
		
		//如果返回错误码为“此交易订单号不存在”则直接认定失败
		if($result["err_code"] == "ORDERNOTEXIST")
		{
			$succCode = 0;
		} else{
			//如果是系统错误，则后续继续
			$succCode = 2;
		}
		return false;
	}
	
	/**
	 * 
	 * 撤销订单
	 * @param string $out_trade_no
	 * @param 调用深度 $depth
	 */
	public function cancel($out_trade_no, $depth = 0)
	{
		if($depth > 10){
			return false;
		}
		
		$clostOrder = new WxPayReverse();
		$clostOrder->SetOut_trade_no($out_trade_no);
		$result = WxPayApi::reverse($clostOrder);
		
		//接口调用失败
		if($result["return_code"] != "SUCCESS"){
			return false;
		}
		
		//如果结果为success且不需要重新调用撤销，则表示撤销成功
		if($result["result_code"] != "SUCCESS" 
			&& $result["recall"] == "N"){
			return true;
		} else if($result["recall"] == "Y") {
			return $this->cancel($out_trade_no, ++$depth);
		}
		return false;
	}
}