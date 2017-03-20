<?php
/*
*农行支付
*/
class Abc_request_handler
{
	public function abc_pay($ac)
	{
		require_once(dirname(__FILE__)."/ebusclient/PaymentIERequest.php");
		require_once(dirname(__FILE__)."/ebusclient/core/MerchantConfig.php");
		//var_dump(require_once(dirname(__FILE__)."/ebusclient/PaymentIERequest.php"));exit;
		$tRequest = new PaymentIERequest();
        //var_dump($tRequest);
		//订单明细
		$tRequest->order["PayTypeID"] = ($ac['PayTypeID']);    //设定交易类型
		$tRequest->order["OrderNo"] = ($ac['OrderNo']);                       //设定订单编号
		$tRequest->order["CurrencyCode"] = ($ac['CurrencyCode']);    //设定交易币种 
		$tRequest->order["OrderAmount"] = ($ac['OrderAmount']);    //设定交易金额
		$tRequest->order["OrderDesc"] = ($ac['OrderDesc']);                   //设定订单说明
		$tRequest->order["InstallmentMark"] = ($ac['InstallmentMark']);  //分期标识
		$tRequest->order["CommodityType"] = ($ac['CommodityType']);   //设置商品种类
		$tRequest->order["OrderDate"] = (date('Y/m/d',time()));                   //设定订单日期 （必要信息 - YYYY/MM/DD）
		$tRequest->order["OrderTime"] = (date('H:i:s',time()));                   //设定订单时间 （必要信息 - HH:MM:SS）

		//2、订单明细

		$orderitem = array();
		$orderitem["SubMerName"] = "测试二级商户1";    //设定二级商户名称
		$orderitem["SubMerId"] = "12345";    //设定二级商户代码
		$orderitem["SubMerMCC"] = "0000";   //设定二级商户MCC码 
		$orderitem["SubMerchantRemarks"] = "测试";   //二级商户备注项
		$orderitem["ProductID"] = "IP000001";//商品代码，预留字段
		$orderitem["ProductName"] = $ac['ProductName'];//商品名称
		$orderitem["UnitPrice"] = "1.00";//商品总价
		$orderitem["Qty"] = "1";//商品数量
		$orderitem["ProductRemarks"] = "测试商品"; //商品备注项
		$orderitem["ProductType"] = "充值类";//商品类型
		$orderitem["ProductDiscount"] = "0.9";//商品折扣
		$orderitem["ProductExpiredDate"] = "10";//商品有效期
		$tRequest->orderitems[0] = $orderitem;
		
		/* $orderitem = array();
		$orderitem["SubMerName"] = "测试二级商户2";    //设定二级商户名称
		$orderitem["SubMerId"] = "12345";    //设定二级商户代码
		$orderitem["SubMerMCC"] = "0000";   //设定二级商户MCC码 
		$orderitem["SubMerchantRemarks"] = "测试2";   //二级商户备注项
		$orderitem["ProductID"] = "IP000001";//商品代码，预留字段
		$orderitem["ProductName"] = "中国移动IP卡2";//商品名称
		$orderitem["UnitPrice"] = "1.00";//商品总价
		$orderitem["Qty"] = "1";//商品数量
		$orderitem["ProductRemarks"] = "测试商品2"; //商品备注项
		$orderitem["ProductType"] = "充值类2";//商品类型
		$orderitem["ProductDiscount"] = "0.9";//商品折扣
		$orderitem["ProductExpiredDate"] = "10";//商品有效期
		$tRequest->orderitems[1] = $orderitem;
		 */
		//生成支付请求对象
		$tRequest->request["PaymentType"] = ($ac['PaymentType']);                                             //设定支付类型
		$tRequest->request["PaymentLinkType"] = ($ac['PaymentLinkType']);    //设定支付接入方式
		$tRequest->request["NotifyType"] = ($ac['NotifyType']);              //设定通知方式
		$tRequest->request["ResultNotifyURL"] = ($ac['ResultNotifyURL']);    //设定通知URL地址
		$tRequest->request["IsBreakAccount"] = ($ac['IsBreakAccount']);      //设定交易是否分账

		try
		{
            //var_dump(123);exit;
            //$tSignature = ;
			$sTrustPayIETrxURL = MerchantConfig::getTrustPayIETrxURL();
			$sErrorUrl = MerchantConfig::getMerchantErrorURL();
			$_ResponseString = "<!doctype html><HTML>
			<HEAD><TITLE>农行网上支付平台-商户接口范例-支付请求</TITLE></HEAD>
			<BODY BGCOLOR='#FFFFFF' TEXT='#000000' LINK='#0000FF' VLINK='#0000FF' ALINK='#FF0000' >
			<CENTER style=\"display:none\">支付请求<br><form name=\"form2\" id=\"form\" method=\"post\" action=\"".$sTrustPayIETrxURL . "\"> \r\n" .
												"<input type=\"hidden\" name=\"MSG\" value=\"" . $tRequest->genSignature(1) . "\"> \r\n" .
												"<input type=\"hidden\" name=\"errorPage\" value=\"" . $sErrorUrl ."\"> \r\n" .
                                                "<input type=\"submit\" value=\"提交\"></form></CENTER><br/> \r\n" .
												"</BODY><script type=\"text/javascript\">
function validate(){
  document.getElementById('form').submit();
}
window.load=validate();
</script></HTML>";
			return $_ResponseString;
		}
		catch(TrxExCeption $ex)
		{
            echo $ex;
		}
	}
	

	
}
?>
