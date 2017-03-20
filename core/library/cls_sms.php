<?php
/*********************************************************************************
 * 短信发送接口
 * @author itprato<2500875@qq>
 * ********************************************************************************/
if( !defined('USE_CURL') ) define('USE_CURL', true);
//短信网关接口配置
$GLOBALS['config']['sms_base_url']          =  'http://gd.ums86.com:8899/sms/Api/Send.do';
$GLOBALS['config']['sms_send_user']         =  'wd20150901';
$GLOBALS['config']['sms_send_password']     =  'n9d9J8';
$GLOBALS['config']['sms_send_spcode']       =  '222865';
//$GLOBALS['config']['sms_send_phone']        =  '13710264680';

/**
 * HTTP接口发送短信，参数说明见文档，需要安装CURL扩展
 * 
 * 使用示例：
 * $sendSms = new SendSmsHttp();
 * $sendSms->SpCode = '123456';
 * $sendSms->LoginName = 'abc123';
 * $sendSms->Password = '123abc';
 * $sendSms->MessageContent = '测试短信';
 * $sendSms->UserNumber = '15012345678,13812345678';
 * $sendSms->SerialNumber = '';
 * $sendSms->ScheduleTime = '';
 * $sendSms->ExtendAccessNum = '';
 * $sendSms->f = '';
 * $res = $sendSms->send();
 * echo $res ? '发送成功' : $sendSms->errorMsg;
 * 
 */
class cls_sms
{
    private static $error_msg = "";
	/**
	 * 发送短信
	 * @return boolean
	 */
	public static function send($msg, $phone = '') {
		$params = array(
            "SpCode"            => $GLOBALS['config']['sms_send_spcode'],
            "LoginName"         => $GLOBALS['config']['sms_send_user'],
            "Password"          => $GLOBALS['config']['sms_send_password'],
            "MessageContent"    => iconv("UTF-8", "GB2312//IGNORE", $msg),
            "UserNumber"        => $phone,
            "SerialNumber"      => '',
            "ScheduleTime"      => '',
            "ExtendAccessNum"   => '',
            "f" => '',
		);
		$data = http_build_query($params);
		$res = iconv('GB2312', 'UTF-8//IGNORE', self::_http_client($data));
		$resArr = array();
        parse_str($res, $resArr);
	
		if (!empty($resArr) && $resArr["result"] == 0) return true;
		else {
			if (empty(self::$error_msg)) self::$error_msg = isset($resArr["description"]) ? $resArr["description"] : '未知错误';
			return false;
		}
	}
	
	/**
	 * POST方式访问接口
	 * @param string $data
	 * @return mixed
	 */
	private static function _http_client($data) {
		try {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL,$GLOBALS['config']['sms_base_url']);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$res = curl_exec($ch);
			curl_close($ch);
			return $res;
		} catch (Exception $e) {
			self::$error_msg = $e->getMessage();
			return false;
		}
	}
}

