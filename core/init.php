<?php
/**
 * 框架核心入口文件
 *
 * 环境检查，核心文件加载
 *
 * @author itprato<2500875@qq>
 * @version $Id$  
 */

////////////////////////////////////////////////////////////////////
//init start 系统初始化开始

$_page_start_time = microtime(true);

// 严格开发模式
error_reporting( E_ALL );

//开启register_globals会有诸多不安全可能性，因此强制要求关闭register_globals
if ( ini_get('register_globals') )
{
    exit('php.ini register_globals must is Off! ');
}

//核心库目录
define('CORE', dirname(__FILE__));

//系统配置

if( file_exists( CORE."/../config/inc_online_config.php" ) )
{
    require CORE."/../config/inc_online_config.php"; 
}
else
{
    require CORE.'/../config/inc_config.php';
}

//设置时区
date_default_timezone_set( $GLOBALS['config']['timezone_set'] );

ini_set('memory_limit', '1024M');

//CLI模式下不debug，不启动路由、不引用模板和不启用session
if( PHP_SAPI == 'cli' )
{
    require CORE.'/util.php';
    require CORE.'/db.php';
    require CORE.'/tpl.php';
    require CORE.'/log.php';
    require CORE.'/cache.php';
    //require CORE.'/config.php';
}
else
{
    //外部请求程序处理(路由)
    require CORE.'/req.php';
    req::init();
    
    //加载核心类库
    require CORE.'/util.php';
    require CORE.'/db.php';
    require CORE.'/tpl.php';
    require CORE.'/log.php';
    require CORE.'/cache.php';
    //require CORE.'/config.php';

    //debug设置   
    if( in_array( util::get_client_ip(), $GLOBALS['config']['safe_client_ip']) )
    {
        $_debug_safe_ip = true;
    }
    else
    {
        $_debug_safe_ip = false;
    }
    require PATH_LIBRARY.'/debug/lib_debug.php';
    if( $_debug_safe_ip || DEBUG_MODE === true )
    {
        ini_set('display_errors', 'On');
    }
    else
    {
        ini_set('display_errors', 'Off');
    }
    set_exception_handler('handler_debug_exception');
    set_error_handler('handler_debug_error', E_ALL);
    
    //session接口(使用session前需自行调用session_start，可以app_config里设定，验证码类程序建议使用独立的app)
    require CORE.'/session.php';  
}

//加载用户自定义配置，通过 config::$bone_configs[$key] 或 config::get($key) 调用
//config::get();

// 前面发一个header，这里php才能判断是否ajax
// beforeSend : function (XMLHttpRequest) {
//     XMLHttpRequest.setRequestHeader("request_type", "ajax");
// },
if (isset($_SERVER['HTTP_REQUEST_TYPE']) && $_SERVER['HTTP_REQUEST_TYPE'] == "ajax"){//ajax提交
    $is_ajax = true;
}else{//非ajax提交
    $is_ajax = false;
}

/**
 * 程序结束后执行的动作
 */
register_shutdown_function('handler_php_shutdown');
function handler_php_shutdown()
{
    //调试模式执行时间
    global $_page_start_time,$_debug_safe_ip,$is_ajax;
    if( ($_debug_safe_ip || DEBUG_MODE === true) && !$is_ajax ) {
        $et = sprintf('%0.4f', microtime(true) - $_page_start_time);
        //echo "<div style='font-size:11px' align='center'>执行时间：{$et} 秒</div>";
    }

    if( PHP_SAPI != 'cli' && !$is_ajax ) {
        show_debug_error();
    }

    log::save();
    if( defined('CLS_CACHE') ) {
        cache::free();
    }
    if( defined('CLS_CACHE_NATIVE') ) {
        cls_cache_native::close();
    }
}

/**
 * 致命错误处理接口
 * 系统发生致命错误后的提示
 * (致命错误是指发生错误后要直接中断程序的错误，如数据库连接失败、找不到控制器等)
 */
function handler_fatal_error( $errtype, $msg )
{
    global $_debug_safe_ip,$is_ajax;
    $log_str = $errtype.':'.$msg;
    if( ($_debug_safe_ip || DEBUG_MODE === true) && !$is_ajax ) 
    {
    	log::add('fatal_error', $msg);
        throw new Exception( $log_str );
    }
    else
    {
        log::add('fatal_error', $msg);
        header ( "location:/404.html" );
        exit();
    }
}

/**
 * 路由控制
 *
 * @param $ctl  控制器
 * @parem $ac   动作
 * @return void
 */
function run_controller()
{
    try
    {
        $ac = preg_replace("/[^0-9a-z_]/i", '', req::item('ac', 'index') );
        $ac = empty ( $ac ) ? $ac = 'index' : $ac;

        $ctl = 'ctl_'.preg_replace("/[^0-9a-z_]/i", '', req::item('ct', 'index') );
        $path_file = PATH_CONTROL . '/' . $ctl . '.php';

        if( file_exists( $path_file ) )
        {
            require $path_file;
        }
        else
        {
            throw new Exception ( "Contrl {$ctl}--{$path_file} is not exists!" );
        }
        if( method_exists ( $ctl, $ac ) === true )
        {
            $instance = new $ctl ( );
            $instance->$ac ();
        }
        else
        {
            throw new Exception ( "Method {$ctl}::{$ac}() is not exists!" );
        }
    }
    catch ( Exception $e )
    {
        handler_fatal_error( 'init.php run_controller()', $e->getMessage().' url:'.util::get_cururl() );
    }
}

/**
 * 自动加载类库处理
 * 加载优先级 /core/library => 应用目录/model => 根目录/model
 * (如果不在这些位置, 则需自行手工加载，对于小型项目，也可以把model全放到library以减少类文件查找时间)
 * @return void
 */
function __autoload( $classname )
{
    $classname = preg_replace("/[^0-9a-z_]/i", '', $classname);
    if( class_exists ( $classname ) ) {
        return true;
    }
    $classfile = $classname.'.php';
    try
    {
        if ( file_exists ( PATH_LIBRARY.'/'.$classfile ) )
        {
            require PATH_LIBRARY.'/'.$classfile;
        }
        else if( file_exists ( PATH_MODEL.'/'.$classfile ) )
        {
            require PATH_MODEL.'/'.$classfile;
        }
        else if( file_exists ( PATH_ROOT.'/model/'.$classfile ) )
        {
            require PATH_ROOT.'/model/'.$classfile;
        }
        else
        {
            return false;
            throw new Exception ( 'Error: Cannot find the '.$classname );
        }
    }
    catch ( Exception $e )
    {
        handler_fatal_error( 'init.php __autoload()', $e->getMessage().'|'.$classname.' url:'.util::get_cururl() );
    }
}

/**
 * req::item 别名函数
 */
function request($key, $df='')
{
    return req::item($key, $df);
}

