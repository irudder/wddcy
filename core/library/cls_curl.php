<?php
/**
 * CURL
 *
 * @author seatle <seatle888@gmail.com>
 *
 * @copyright http://www.114la.com
 *
 * @since 2013-02-17
 *
 * @version 1.0
 *
 * @example
 *
 * -----------------------------------------------
 *
 *
 * -----------------------------------------------
 */
class cls_curl
{

    protected static $timeout = 1;

    protected static $ch = null;

    protected static $proxy = null;

    protected static $useragent = 'Mozilla/5.0';

    protected static $cookie = null;

    protected static $referer = null;

    protected static $ip = null;

    /**
     * set timeout
     *
     * @param init $timeout
     * @return
     */
    public static function set_timeout($timeout)
    {
        self::$timeout = $timeout;
    }

    /**
     * set proxy
     *
     */
    public static function set_proxy($proxy)
    {
        self::$proxy = $proxy;
    }
    
    /**
     * set referer
     *
     */
    public static function set_referer($referer)
    {
        self::$referer = $referer;
    }

    /**
     * 设置 user_agent
     *
     * @param string $useragent
     * @return void
     */
    public static function set_useragent($useragent)
    {
        self::$useragent = $useragent;
    }

    /**
     * 设置COOKIE
     *
     * @param string $cookie
     * @return void
     */
    public static function set_cookie($cookie)
    {
        self::$cookie = $cookie;
    }

    /**
     * 设置IP
     *
     * @param string $ip
     * @return void
     */
    public static function set_ip($ip)
    {
        self::$ip = $ip;
    }

    /**
     * 初始化 CURL
     *
     */
    public static function init()
    {
        if (empty ( self::$ch ))
        {
            self::$ch = curl_init ();
            curl_setopt ( self::$ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt ( self::$ch, CURLOPT_CONNECTTIMEOUT, self::$timeout );
            curl_setopt ( self::$ch, CURLOPT_HEADER, false );
            curl_setopt ( self::$ch, CURLOPT_USERAGENT, self::$useragent );
            curl_setopt ( self::$ch, CURLOPT_TIMEOUT, self::$timeout + 5);
        }
        return self::$ch;
    }

    /**
     * get
     *
     *
     */
    public static function get($url, $proxy = false)
    {
        self::init ();
        curl_setopt ( self::$ch, CURLOPT_URL, $url );
        if (self::$useragent)
        {
            curl_setopt(self::$ch, CURLOPT_USERAGENT, self::$useragent);
        }
        if (self::$cookie)
        {
            curl_setopt(self::$ch, CURLOPT_COOKIE, self::$cookie);
        }
        if (self::$referer)
        {
            curl_setopt(self::$ch, CURLOPT_REFERER, self::$referer);
        }
        if (self::$ip)
        {
            curl_setopt ($ch, CURLOPT_HTTPHEADER, array('CLIENT-IP:'.self::$ip, 'X-FORWARDED-FOR:'.self::$ip));
        }
        if ($proxy)
        {
            curl_setopt ( self::$ch, CURLOPT_PROXY, $url );
            curl_setopt ( self::$ch, CURLOPT_USERAGENT, $url );
        }
        $data = curl_exec ( self::$ch );
        if ($data)
        {
            return $data;
        }
        else
        {
            return false;
        }
    }

    /**
     * post
     *
     */
    public static function post($url, $fields, $proxy = false)
    {
        self::init ();
        curl_setopt ( self::$ch, CURLOPT_URL, $url );
        curl_setopt ( self::$ch, CURLOPT_POST, true );
        curl_setopt ( self::$ch, CURLOPT_POSTFIELDS, $fields );
        if ($proxy)
        {
            curl_setopt ( self::$ch, CURLOPT_PROXY, $url );
            curl_setopt ( self::$ch, CURLOPT_USERAGENT, $url );
        }
        if (self::$cookie)
        {
            curl_setopt(self::$ch, CURLOPT_COOKIE, self::$cookie);
        }
        if (self::$referer)
        {
            curl_setopt(self::$ch, CURLOPT_REFERER, self::$referer);
        }
        $data = curl_exec ( self::$ch );
        if ($data)
        {
            return $data;
        }
        else
        {
            return false;
        }
    }



}
?>
