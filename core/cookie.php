<?php
  
class cookie
{
    public static function set($name, $value, $expire = 0)
    {
        setcookie($name, $value, $expire, '/', COOKIE_DOMAIN);
    }

    public static function get($name)
    {
        return req::item($name);
    }
}
