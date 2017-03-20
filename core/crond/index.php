<?php

/* CROND 定时控制器 By jab <mixboy@gmail.com> */

define('__THIS__', strtr(__FILE__, array('\\' => '/','/index.php' => '','\index.php' => '')));
require __THIS__ . '/../init.php';

/* 永不超时 */
ini_set('max_execution_time', 0);
set_time_limit(0);

/* 执行CROND */
exit(crond());

/* CROND函数 */
function crond()
{
    require PATH_CONFIG . '/inc_crond.php';
    if (file_exists(PATH_DATA . '/task_list.php')) 
    {
        include PATH_DATA . '/task_list.php';
        $GLOBALS['CROND_TIMER']['the_time'] = array_merge($GLOBALS['CROND_TIMER']['the_time'], $task_list);
    }

    $index_time_start = microtime(true);
    $index_memory_start = memory_get_usage();

    /* 提取要执行的文件 */
    $exe_file = array();
    foreach ($GLOBALS['CROND_TIMER']['the_format'] as $format)
    {
        $key = date($format, ceil($index_time_start));
        if (is_array(@$GLOBALS['CROND_TIMER']['the_time'][$key]))
        {
            $exe_file = array_merge($exe_file, $GLOBALS['CROND_TIMER']['the_time'][$key]);
        }
    }

    echo "\n" . date('Y-m-d H:i', time()), "\n\n";

    /* 加载要执行的文件 */
    foreach ($exe_file as $file)
    {
        // 过滤掉不是 core/crond/ 目录下的文件，否则被上传到data目录的php就很危险了
        $pathinfo = pathinfo($file);
        if (empty($pathinfo['basename'])) 
        {
            continue;
        }
        $file = $pathinfo['basename'];
        echo '  ', $file,"\n";
        $runtime_start = microtime(true);
        $time = time();
        //这里没法准确更新到正确的task去，是根据运行脚本和状态来的
        db::query("UPDATE `users_task` SET `lasttime`='{$time}' WHERE `filename` LIKE '%{$file}%' AND `status`=1");
        include __THIS__ . '/' . $file;
        $runtime = microtime(true) - $runtime_start;
        db::query("UPDATE `users_task` SET `runtime`='{$runtime}' WHERE `filename` LIKE '%{$file}%' AND `status`=1");
        echo "\n\n";
    }

    $size = memory_get_usage() - $index_memory_start;
    $unit = array('b','kb','mb','gb','tb','pb'); 
    $memory = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i]; 
    echo 'total: ', microtime(true) - $index_time_start . " seconds\t $memory\n";

//    sleep(2);
//    crond();
}
