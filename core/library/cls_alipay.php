<?php
/**
 * 支付宝支付
 **/
class cls_alipay
{
	public static function check_sign($array, $key)
    {
        $sign = self::make_sign($array, $key);
        return $sign == $array['sign']; 
    }

    /**
	 * 生成签名
	 * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
	 */
	public static function make_sign($array, $key)
	{
        unset($array['sign'], $array['sign_type']);
		//签名步骤一：按字典序排序参数
		ksort($array);
        $string = urldecode(http_build_query($array));
        //$string = self::to_url_params($array);
		//签名步骤二：在string后加入KEY
		$string = $string . $key;
		//签名步骤三：MD5加密
		$string = md5($string);
		return $string;
	}

    /**
	 * 格式化参数格式化成url参数
	 */
	public static function to_url_params($array)
	{
		$buff = "";
		foreach ($array as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
}
