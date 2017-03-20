<?php
if( !defined('CORE') ) exit('Request Error!');
/**
 * 默认缓存类
 *
 * 使用缓存应该比较注意的问题，没特殊原因一般都使用memcache或memcached作为缓存类型，对于很小规模的应用， 可以考虑用 file 作为缓存， 
 * 缓存进行 set/get/deldte 时务必指定 prefix，实际上系统最终得到的 key 是 base64_encode( self::df_prefix.'_'.$prefix.'_'.$key )
 * 为什么要这样做呢？
 * 因为memcache或memcached对应用缓存服务器群通常是很多网站一起使用的，如果没前缀区分，很容易会错把目标网站的同名缓存给crear掉
 *
 * @since 2011-07-20
 * @author itprato<2500875@qq>
 * @version $Id$
 */
define('CLS_CACHE', true);
class cache
{
    
   //缓存记录内存变量
   private $caches = array();
   
   //文件缓存系统或memcache游标
   private $mc_handle = null;
   
   //缓存类型（file|memcache|memcached）
   public static $cache_type = 'file';
   
   //key默认前缀
   private static $df_prefix = 'mc_df_';
   
   //默认缓存时间 2 小时，单位是秒
   private static $cache_time = 7200;
   
   //当前类实例
   private static $instance = null;
   
   //是否使用内存数组
   public static $need_mem = true;
   
   /**
    * 构造函数
    * @return void
    */
    public function __construct()
    {
        if( !$GLOBALS['config']['cache']['enable'] ) {
            return;
        }
        self::$df_prefix  = $GLOBALS['config']['cache']['df_prefix'];
        self::$cache_time = $GLOBALS['config']['cache']['cache_time'];
        self::$cache_type = $GLOBALS['config']['cache']['cache_type'];
        if( self::$cache_type == 'file' )
        {
            $this->mc_handle = cls_filecache::factory( $GLOBALS['config']['cache']['file_cachename'] );
        }
        else if( self::$cache_type == 'memcached' )
        {
            $this->mc_handle = new Memcached();
            $servers = array();
            foreach($GLOBALS['config']['cache']['memcache']['host'] as $k => $mcs) {
                $mc_hosts = parse_url ( $mcs );
                $servers[] = array($mc_hosts['host'], $mc_hosts['port']);
            }
            $this->mc_handle->addServers( $servers );
        }
        else
        {
            $this->mc_handle = new Memcache();
            $mc_hosts = parse_url ( $GLOBALS['config']['cache']['memcache']['host'][0] );
            $this->mc_handle->connect( $mc_hosts['host'], $mc_hosts['port'], $GLOBALS['config']['cache']['memcache']['time_out'] );
        }
    }
    
   /**
    * 为自己创建实例，以方便对主要方法进行静态调用
    */
    protected static function _check_instance()
    {
        if( !$GLOBALS['config']['cache']['enable'] ) {
            return false;
        }
        if( self::$instance == null ) {
            self::$instance = new cache();
        }
        return self::$instance;
    }
   
   /**
    * 获取key
    */
    protected static function _get_key($prefix, $key)
    {
        $key = base64_encode(cache::$df_prefix.'_'.$prefix.'_'.$key);
        if( strlen($key) > 32 ) $key = md5( $key );
        return $key;
    }
   
   /**
    * 增加/修改一个缓存
    * @param $prefix     前缀
    * @parem $key        键(key=base64($prefix.'_'.$key))
    * @param $value      值
    * @parem $cachetime  有效时间，单位是秒(0不限, -1使用系统默认)
    * @return void
    */               
    public static function set($prefix, $key, $value, $cachetime=-1)
    {
        if( self::_check_instance()===false ) {
            return false;
        }
        if($cachetime==-1) {
            $cachetime = self::$cache_time;
        }
        $key = self::_get_key($prefix, $key);
        if( self::$need_mem ) {
            self::$instance->mc_handle->caches[ $key ] = $value;
        }
        //修正memcached不支持压缩选项
        if( self::$cache_type == 'memcached' ) {
            return self::$instance->mc_handle->set($key, $value, $cachetime);
        } else {
            return self::$instance->mc_handle->set($key, $value, 0, $cachetime);
        }
    }
    
   /**
    * 删除缓存
    * @param $prefix     前缀
    * @parem $key        键
    * @return void
    */               
    public static function del($prefix, $key)
    {
        if( self::_check_instance()===false ) {
            return false;
        }
        $key = self::_get_key($prefix, $key);
        if( isset(self::$instance->mc_handle->caches[ $key ]) ) {
            self::$instance->mc_handle->caches[ $key ] = false;
            unset(self::$instance->mc_handle->caches[ $key ]);
        }
        return self::$instance->mc_handle->delete( $key );
    }
    
   /**
    * 读取缓存
    * @param $prefix     前缀
    * @parem $key        键
    * @return void
    */               
    public static function get($prefix, $key)
    {
        //全局禁用cache(调试使用的情况)
        if( defined('NO_CACHE') && NO_CACHE ) {
            return false;
        }
        if( self::_check_instance()===false ) {
            return false;
        }
        $key = self::_get_key($prefix, $key);
        if( isset(self::$instance->mc_handle->caches[ $key ]) ) {
            return self::$instance->mc_handle->caches[ $key ];
        }
        return self::$instance->mc_handle->get( $key );
    }
    
   /**
    * 清除保存在缓存类的缓存
    * @return void
    */               
    public static function free_mem($flush_memc = false)
    {
        if( isset(self::$instance->mc_handle->caches) ) {
            self::$instance->mc_handle->caches = array();
        }
        
        if ($flush_memc)
        {
            return self::$instance->mc_handle->flush();
        }
        
        return true;
    }
    
   /**
    * 关闭链接
    * @return void
    */               
    public static function free()
    {
        if( self::_check_instance()===false ) {
            return false;
        }
        if( self::$cache_type != 'memcached' ) {
            self::$instance->mc_handle->close();
        }
    }
    
   /**
    * 重设cache参数(使用其它缓存文件)
    * @return void
    */               
    public static function reconfig( $config )
    {
        $GLOBALS['config']['cache'] = $config;
        if( self::$instance != null )
        {
            if( self::$cache_type != 'memcached' ) {
                self::$instance->mc_handle->close();
            }
            self::$instance = null;
        }
        return self::_check_instance();
    }
    
}
