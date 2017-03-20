<?php
if (!defined('CORE')) exit('Request Error!');
/**
 * 数据库操作类 <<读写分离>>
 *
 * 读 - mysql master
 *    - mysql slave 1
 *    - mysql slave 2
 *    ......
 *
 * 写 - master
 *
 * @author itprato<2500875@qq>
 * @version $Id$
 */
class db extends db_base
{

    protected static function _init_mysql($is_master = false)
    {
        // 获取配置
        $db_config = (self::$link_name == 'default' ? self::_get_default_config() : self::$configs[self::$link_name]);
        // 连接属性及host
        if ($is_master === true)
        {
            $link = 'w';
            $host = $db_config['host']['master'];
        }
        else
        {
            $link = 'r';
            $key = array_rand($db_config['host']['slave']);
            $host = $db_config['host']['slave'][$key];
        }
        // 创建连接
        if (empty(self::$links[self::$link_name][$link]))
        {
            try
            {
                $hosts = explode(':', $host);
                if (empty($hosts[1])) $hosts[1] = 3306;
                self::$links[self::$link_name][$link] = mysqli_connect($hosts[0], $db_config['user'], $db_config['pass'], $db_config['name'], $hosts[1]);
                if (empty(self::$links[self::$link_name][$link]))
                {
                    throw new Exception("Connect MySql Error! ");
                }
                else
                {
                    
                    $charset = str_replace('-', '', strtolower($db_config['charset']));
                    mysqli_query(self::$links[self::$link_name][$link], " SET character_set_connection=" . $charset . ", character_set_results=" . $charset . ", character_set_client=binary, sql_mode='' ");
                    /*
                     * if ( mysqli_select_db(self::$links[self::$link_name][$link], $db_config['name']) === false ) { throw new Exception( mysqli_error(self::$links[self::$link_name][$link]) ); }
                     */
                }
            }
            catch (Exception $e)
            {
                handler_fatal_error('db.php _init_mysql()', $e->getMessage() . ' page: ' . util::get_cururl());
            }
        }
        self::$cur_link = self::$links[self::$link_name][$link];
        return self::$links[self::$link_name][$link];
    }


    public static function query($sql, $is_master = false)
    {
        if (self::$not_break)
        {
            return self::query_over($sql);
        }
        
        $start_time = microtime(true);
        
        // 对SQL语句进行安全过滤
        if (self::$safe_test == true)
        {
            $sql = self::_filter_sql($sql);
        }
        
        // 强制使用主数据库
        if ($is_master === true)
        {
            self::$cur_link = self::_init_mysql(true);
        }
        else
        {
            if (substr(strtolower($sql), 0, 1) === 's')
            {
                self::$cur_link = self::_init_mysql(false);
            }
            else
            {
                self::$cur_link = self::_init_mysql(true);
            }
        }
        
        try
        {
            self::$cur_result = mysqli_query(self::$cur_link, self::get_sql($sql));
            // self::$results[ self::$cur_result ] = self::$cur_result;
            // 记录慢查询
            if (self::$log_slow_query)
            {
                $querytime = microtime(true) - $start_time;
                if ($querytime > self::$log_slow_time)
                {
                    self::_slow_query_log($sql, $querytime);
                }
            }

            if (self::$cur_result === false)
            {
                $err_msg = mysqli_error(self::$cur_link);
                throw new Exception($err_msg);
                return false;
            }
            else
            {
                self::$query_count++;
                return self::$cur_result;
            }
        }
        catch (Exception $e)
        {
            handler_fatal_error('db.php query()', $e->getMessage() . '|' . $sql . ' page:' . util::get_cururl());
        }

    }


    public static function query_over($sql)
    {
        self::$not_break = false;
        // 执行一个不中断的SQL语句，强制主库执行，别写数据到从库就玩大了
        self::$cur_link = self::_init_mysql(true);
        if (self::$safe_test == true)
        {
            $sql = self::_filter_sql($sql);
        }
        $rs = @mysqli_query(self::$cur_link, self::get_sql($sql));
        return $rs;
    }


    public static function insert_id()
    {
        return mysqli_insert_id(self::$cur_link);
    }


    public static function affected_rows()
    {
        return mysqli_affected_rows(self::$cur_link);
    }


    public static function num_rows($rsid = '')
    {
        $rsid = self::_get_rsid($rsid);
        return mysqli_num_rows($rsid);
    }


    public static function fetch_one($rsid = '', $result_type = DB_GET_ASSOC)
    {
        $rsid = self::_get_rsid($rsid);
        $row = mysqli_fetch_array($rsid, $result_type);
        return $row;
    }


    public static function fetch($rsid = '', $result_type = DB_GET_ASSOC)
    {
        return self::fetch_one($rsid, $result_type);
    }


    public static function fetch_all($rsid = '', $result_type = DB_GET_ASSOC)
    {
        $rsid = self::_get_rsid($rsid);
        $row = $rows = array();
        while ($row = mysqli_fetch_array($rsid, $result_type))
        {
            $rows[] = $row;
        }
        mysqli_free_result($rsid);
        return empty($rows) ? false : $rows;
    }


    public static function get_one($sql, $func = '', $is_master = false)
    {
        if (!preg_match("/limit/i", $sql))
        {
            $sql = preg_replace("/[,;]$/i", '', trim($sql)) . " limit 1 ";
        }
        $rsid = self::query($sql, $is_master);
        $row = mysqli_fetch_array($rsid, DB_GET_ASSOC);
        mysqli_free_result($rsid);
        if (!empty($func))
        {
            return call_user_func($func, $row);
        }
        return $row;
    }


    public static function get_all($sql, $func = '', $is_master = false)
    {
        $rsid = self::query($sql, $is_master);
        while ($row = self::fetch_one($rsid, DB_GET_ASSOC))
        {
            $rows[] = $row;
        }
        mysqli_free_result($rsid);
        if (!empty($func))
        {
            return call_user_func($func, $rows);
        }
        return empty($rows) ? array() : $rows;
    }


    public static function ping($link = 'w')
    {
        if (self::$links[self::$link_name][$link] != null && !mysqli_ping(self::$links[self::$link_name][$link]))
        {
            mysqli_close(self::$links[self::$link_name][$link]);
            @mysqli_close(self::$cur_link);
            self::$links[self::$link_name][$link] = self::$cur_link = null;
            self::_init_mysql(true);
        }
    }


    public static function free($rsid)
    {
        return mysqli_free_result($rsid);
    }


    public static function autocommit($mode = false)
    {
        self::$cur_link = self::_init_mysql(true);
        // $int = $mode ? 1 : 0;
        // return @mysqli_query(self::$cur_link, "SET autocommit={$int}");
        return mysqli_autocommit(self::$cur_link, $mode);
    }


    public static function begin_tran()
    {
        // self::$cur_link = self::_init_mysql( true );
        // return @mysqli_query(self::$cur_link, 'BEGIN');
        return self::autocommit(false);
    }


    public static function commit()
    {
        return mysqli_commit(self::$cur_link);
    }


    public static function rollback()
    {
        return mysqli_rollback(self::$cur_link);
    }


    public static function update($table = '', $set = NULL, $where = NULL, $return_sql = FALSE)
    {
        $set = self::strsafe($set);
        if (empty($where) || $where === true)
        {
            exit("Missing argument where");
        }
        
        $fields = self::get_fields($table, $set);
        $sql = self::_get_update_sql($table, $fields, $where);
        
        if ($return_sql) return $sql;
        
        if (empty($sql)) return false;
        
        return self::query($sql);
    }


    public static function insert($table = '', $set = NULL, $return_sql = FALSE)
    {
        $set = self::strsafe($set);
        $fields = self::get_fields($table, $set);
        $sql = self::_get_insert_sql($table, $fields);
        
        if ($return_sql) return $sql;
        
        $rt = self::query($sql);
        $insert_id = self::insert_id();
        $return = empty($insert_id) ? $rt : $insert_id;
        return $return;
    }

    public static function insert_batch($table = '', $set = NULL, $return_sql = FALSE)
    {
        if (empty($table) || empty($set)) 
        {
            return false;
        }
        $set = self::strsafe($set);
        $sql = self::_get_insert_batch_sql($table, $set);

        if ($return_sql) return $sql;
        
        $rt = self::query($sql);
        $insert_id = self::insert_id();
        $return = empty($insert_id) ? $rt : $insert_id;
        return $return;
    }

    public static function update_batch($table = '', $set = NULL, $index = NULL, $where = NULL, $return_sql = FALSE)
    {
        if (is_null($index)) 
        {
            // 提示错误，不要用exit，会中断程序
            //echo "db_must_use_index";
            return false;
        }
        if ($table == '') 
        {
            //echo "db_must_set_table";
            return false;
        }
        if ( ! is_null($set))
		{
            $set = self::strsafe($set);
            $sql = self::_get_update_batch_sql($table, $set, $index, $where);
		}

        if ($return_sql) return $sql;
        
        $rt = self::query($sql);
        $insert_id = self::affected_rows();
        $return = empty($affected_rows) ? $rt : $affected_rows;
        return $return;
    }

    public static function get_fields($table, $set = array())
    {
        // $sql = "SHOW COLUMNS FROM $table"; //和下面的语句效果一样
        $rows = self::get_all("DESC `{$table}`");
        $fields = self::_get_fields($rows, $set);
        return $fields;
    }

    public static function strsafe($array)
    {
        $arrays = array();
        if(is_array($array)===true)
        {
            foreach ($array as $key => $val)
            {                
                if(is_array($val)===true)
                {
                    $arrays[$key] = self::strsafe($val);
                }
                else 
                {
                    //先去掉转义，避免下面重复转义了
                    $val = stripslashes($val);
                    //进行转义
                    $val = addslashes($val);
                    //处理addslashes没法处理的 _ % 字符
                    //$val = strtr($val, array('_'=>'\_', '%'=>'\%'));
                    $arrays[$key] = $val;
                }
            }
            return $arrays;
        }
        else 
        {
            $array = stripslashes($array);
            $array = addslashes($array);
            //$array = strtr($array, array('_'=>'\_', '%'=>'\%'));
            return $array;
        }
    }

}
