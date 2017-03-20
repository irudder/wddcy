<?php
class Sendnoec{
	public function sendSMS($url,$ac,$authkey,$cgid,$m,$c,$csid,$t)
	{
		$data = array
		(
				'action'=>'sendOnce',                                //发送类型 ，可以有sendOnce短信发送，sendBatch一对一发送，sendParam	动态参数短信接口
				'ac'=>$ac,					                         //用户账号
				'authkey'=>$authkey,	                             //认证密钥
				'cgid'=>$cgid,                                       //通道组编号
				'm'=>$m,		                                     //号码,多个号码用逗号隔开
				'c'=>iconv('gbk','utf-8',$c),		                 //如果页面是gbk编码，则转成utf-8编码，如果是页面是utf-8编码，则不需要转码
				'csid'=>$csid,                                       //签名编号 ，可以为空，为空时使用系统默认的签名编号
				't'=>$t                                              //定时发送，为空时表示立即发送
		);
		$xml=$this->postSMS($url,$data);			                     //POST方式提交
		$re=simplexml_load_string(utf8_encode($xml));
		if(trim($re['result'])==1)                               //发送成功 ，返回企业编号，员工编号，发送编号，短信条数，单价，余额
		{
			foreach ($re->Item as $item)
			{
	
				$stat['msgid'] =trim((string)$item['msgid']);
				$stat['total']=trim((string)$item['total']);
				$stat['price']=trim((string)$item['price']);
				$stat['remain']=trim((string)$item['remain']);
				$stat_arr[]=$stat;
					
			}
			if(is_array($stat_arr))
			{
				return trim($re['result']);
			}
	
		}
		else  //发送失败的返回值
		{
			return trim($re['result']);
			
			/* switch(trim($re['result'])){
				case  0: echo "帐户格式不正确(正确的格式为:员工编号@企业编号)";break;
				case  -1: echo "服务器拒绝(速度过快、限时或绑定IP不对等)如遇速度过快可延时再发";break;
				case  -2: echo " 密钥不正确";break;
				case  -3: echo "密钥已锁定";break;
				case  -4: echo "参数不正确(内容和号码不能为空，手机号码数过多，发送时间错误等)";break;
				case  -5: echo "无此帐户";break;
				case  -6: echo "帐户已锁定或已过期";break;
				case  -7:echo "帐户未开启接口发送";break;
				case  -8: echo "不可使用该通道组";break;
				case  -9: echo "帐户余额不足";break;
				case  -10: echo "内部错误";break;
				case  -11: echo "扣费失败";break;
				default:break;
			} */
		}
	}
	
	public function postSMS($url,$data='')
	{
		$row = parse_url($url);
		$host = $row['host'];
		@$port = $row['port'] ? $row['port']:80;
		$file = $row['path'];
		while (list($k,$v) = each($data))
		{
			@$post .= rawurlencode($k)."=".rawurlencode($v)."&";	//转URL标准码
		}
		$post = substr( $post , 0 , -1 );
		$len = strlen($post);
		$fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
		if (!$fp) {
			return "$errstr ($errno)\n";
		} else {
			$receive = '';
			$out = "POST $file HTTP/1.0\r\n";
			$out .= "Host: $host\r\n";
			$out .= "Content-type: application/x-www-form-urlencoded\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Content-Length: $len\r\n\r\n";
			$out .= $post;
			fwrite($fp, $out);
			while (!feof($fp)) {
				$receive .= fgets($fp, 128);
			}
			fclose($fp);
			$receive = explode("\r\n\r\n",$receive);
			unset($receive[0]);
			return implode("",$receive);
		}
	}
}
?>