<?php
if( !defined('CORE') ) exit('Request Error!');
/**
 * httpsqs队列综合操作类
 *
 * cls_httpsqs::put($queue_name, $queue_data);  如果队列满了，返回:HTTPSQS_PUT_END
 * cls_httpsqs::get($queue_name);  如果队列为空，返回:HTTPSQS_GET_END
 * cls_httpsqs::gets($queue_name);  如果队列为空，返回:array("pos" => 0, "data" => "HTTPSQS_GET_END")
 * cls_httpsqs::status($queue_name);  查看队列状态，返回array数据
 * cls_httpsqs::status_json($queue_name);  查看队列状态，返回json数据
 * cls_httpsqs::view($queue_name, $queue_pos);  从指定的 队列位置(id) 获取数据
 * cls_httpsqs::reset($queue_name);  重设队列
 * cls_httpsqs::maxqueue($queue_name, $num);  设置某个队列的队列长度
 * cls_httpsqs::synctime($num);  修改队列同步更新内容从内存到硬盘的时间间隔
 *
 * @version $Id$
 *
 */

class cls_httpsqs
{

    public static function put($queue_name, $queue_data)
    {
    	$result = self::http_post("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=".$queue_name."&opt=put", $queue_data);
		if ($result["data"] == "HTTPSQS_PUT_OK") {
			return true;
		} else if ($result["data"] == "HTTPSQS_PUT_END") {
			return $result["data"];
		}
		return false;
    }
    
    public static function get($queue_name)
    {
    	$result = self::http_get("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=".$queue_name."&opt=get");
		if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    public static function gets($queue_name)
    {
    	$result = self::http_get("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=".$queue_name."&opt=get");
		if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result;
    }	
	
    public static function status($queue_name)
    {
    	$result = self::http_get("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=".$queue_name."&opt=status");
		if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    public static function view($queue_name, $queue_pos)
    {
    	$result = self::http_get("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=".$queue_name."&opt=view&pos=".$pos);
		if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }
	
    public static function reset($queue_name)
    {
    	$result = self::http_get("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=".$queue_name."&opt=reset");
		if ($result["data"] == "HTTPSQS_RESET_OK") {
			return true;
		}
        return false;
    }
	
    public static function maxqueue($queue_name, $num)
    {
    	$result = self::http_get("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=".$queue_name."&opt=maxqueue&num=".$num);
		if ($result["data"] == "HTTPSQS_MAXQUEUE_OK") {
			return true;
		}
        return false;
    }
	
    public static function status_json($queue_name)
    {
    	$result = self::http_get("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=".$queue_name."&opt=status_json");
		if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
			return false;
		}
        return $result["data"];
    }

    public static function synctime($num)
    {
    	$result = self::http_get("/?auth=".$GLOBALS['config']['queue']['auth']."&charset=".$GLOBALS['config']['queue']['charset']."&name=httpsqs_synctime&opt=synctime&num=".$num);
		if ($result["data"] == "HTTPSQS_SYNCTIME_OK") {
			return true;
		}
        return false;
    }

    public static function http_get($query)
    {
        $socket = fsockopen($GLOBALS['config']['queue']['host'], $GLOBALS['config']['queue']['port'], $errno, $errstr, 5);
        if (!$socket)
        {
            return false;
        }
        $out = "GET {$query} HTTP/1.1\r\n";
        $out .= "Host: {$GLOBALS['config']['queue']['host']}\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        fwrite($socket, $out);
        $line = trim(fgets($socket));
        $header = '';
        $header .= $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($socket))) != "")
        {
            $header .= $line;
            if (strstr($line, "Content-Length:"))
            {
                list($cl, $len) = explode(" ", $line);
 
            }
            if (strstr($line, "Pos:"))
            {
                list($pos_key, $pos_value) = explode(" ", $line);
            }			
            if (strstr($line, "Connection: close"))
            {
                $close = true;
            }
        }
        if ($len < 0)
        {
            return false;
        }
        
        $body = fread($socket, $len);
        $fread_times = 0;
        while(strlen($body) < $len){
        	$body1 = fread($socket, $len);
        	$body .= $body1;
        	unset($body1);
        	if ($fread_times > 100) {
        		break;
        	}
        	$fread_times++;
        }
        //if ($close) fclose($socket);
		fclose($socket);
		$result_array["pos"] = empty($pos_value) ? 0 : (int)$pos_value;
		$result_array["data"] = $body;
        return $result_array;
    }

    public static function http_post($query, $body)
    {
        $socket = fsockopen($GLOBALS['config']['queue']['host'], $GLOBALS['config']['queue']['port'], $errno, $errstr, 1);
        if (!$socket)
        {
            return false;
        }
        $out = "POST {$query} HTTP/1.1\r\n";
        $out .= "Host: {$GLOBALS['config']['queue']['host']}\r\n";
        $out .= "Content-Length: " . strlen($body) . "\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        $out .= $body;
        fwrite($socket, $out);
        $line = trim(fgets($socket));
        $header = '';
        $header .= $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($socket))) != "")
        {
            $header .= $line;
            if (strstr($line, "Content-Length:"))
            {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:"))
            {
                list($pos_key, $pos_value) = explode(" ", $line);
            }			
            if (strstr($line, "Connection: close"))
            {
                $close = true;
            }
        }
        if ($len < 0)
        {
            return false;
        }
        $body = @fread($socket, $len);
        //if ($close) fclose($socket);
		fclose($socket);
		$result_array["pos"] = empty($pos_value) ? 0 : (int)$pos_value;
		$result_array["data"] = $body;
        return $result_array;
    }
}
?>
