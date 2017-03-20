<?php
/**
 *
 * 数据库基类)
 * -------------------------------------------------------------------------------------------
 * 本文件结束处根据是否支持mysqli引入db_mysqli.php/db_mysql.php继承本类，并进行实际性数据库操作
 * 在程序中，应当使用类名 db 进行相关操作，不要直接使用基类 db_base
 * 本类要么不定义get_one、get_all等子类会实现的方法，要么在本类调用这些方法的时候用static替代self
 * 因为用self调用get_one、get_all等方法会调用本类的空方法，而不是子类已经实现了的方法
 * -------------------------------------------------------------------------------------------
 * @author itprato<2500875@qq>
 * @version $Id$  
 */
if (defined('MYSQLI_ASSOC'))
{
    define('DB_GET_ASSOC', MYSQLI_ASSOC);
    define('DB_GET_NUM', MYSQLI_NUM);
    define('DB_GET_BOTH', MYSQLI_BOTH);
}
else
{
    define('DB_GET_ASSOC', MYSQL_ASSOC);
    define('DB_GET_NUM', MYSQL_NUM);
    define('DB_GET_BOTH', MYSQL_BOTH);
}
class db_base
{
    // 连接默认是 $links[ self::$link_name ]['w'] || $links[ self::$link_name ]['r']
    // 如果用户要开一个新的连接, 用 set_connect($link_name, db配置) ， 当前链接sql操作完后，使用 set_connect_default 还原为默认
    // config 格式与 $GLOBALS['config']['db'] 一致
    protected static $links = array();
    
    // 数据库配置数组
    protected static $configs = array();
    
    // 当前连接名，系统通过 $links[ self::$link_name ]['w'] || $links[ self::$link_name ]['r'] 识别特定配置的链接
    protected static $link_name = 'default';
    
    // 当前使用的链接， 如果不用 set_connect 或 set_connect_default 进行改变， 这连接由系统决定
    protected static $cur_link = null;
    
    // 游标集
    protected static $cur_result = null;
    protected static $results = array();
    
    // 统计
    protected static $query_count = 0;
    protected static $log_slow_query = true;
    protected static $log_slow_time = 0.5;
    
    // 是否对SQL语句进行安全检查并处理，这个过滤在插入十万条以上数据的时候会出现瓶颈
    public static $safe_test = true;
    public static $rps = array('/*', '--', 'union', 'sleep', 'benchmark', 'load_file', 'outfile');
    
    public static $rpt = array('/×', '——', 'ｕｎｉｏｎ', 'ｓｌｅｅｐ', 'ｂｅｎｃｈｍａｒｋ', 'ｌｏａｄ_ｆｉｌｅ', 'ｏｕｔｆｉｌｅ');
        
    // 对不需要强制中断的lurd插入方法进行临时处理
    public static $not_break = false;
    
    // 最后执行sql
    public static $sql;

    /**
    * 改变链接为指定配置的链接(如果不同时使用多个数据库，不会涉及这个操作)
    * @parem  $link_name 链接标识名
    * @parem  $config 多次使用时， 这个数组只需传递一次
    *         config 格式与 $GLOBALS['config']['db'] 一致
    * @return void
    */
    public static function set_connect($link_name, $config = array())
    {
        self::$link_name = $link_name;
        if (!empty($config))
        {
            self::$configs[self::$link_name] = $config;
        }
        else
        {
            if (empty(self::$configs[self::$link_name]))
            {
                throw new Exception("You not set a config array for connect!");
            }
        }
    }

    /**
    * 还原为默认连接(如果不同时使用多个数据库，不会涉及这个操作)
    * @parem $config 指定配置（默认使用inc_config.php的配置）
    * @return void
    */
    public static function set_connect_default($config = '')
    {
        if (empty($config))
        {
            $config = self::_get_default_config();
        }
        self::set_connect('default', $config);
    }

    /**
    * 获取默认配置
    */
    protected static function _get_default_config()
    {
        if (empty(self::$configs['default']))
        {
            if (!is_array($GLOBALS['config']['db']))
            {
                handler_fatal_error('db.php _get_default_config()', '没有mysql配置的情况下，尝试使用数据库，page: ' . util::get_cururl());
            }
            self::$configs['default'] = $GLOBALS['config']['db'];
        }
        return self::$configs['default'];
    }


    public static function get_link_name() {
        return self::$link_name;
    }
    /**
     * (读+写)连接数据库+选择数据库
     * @parem $is_master 是否强制为主库，否则通过 sql 语句判断主从
     * @return void
     */
    protected static function _init_mysql($is_master = false) { }

    /**
     * 返回修正后的sql
     * #PB# 替代db_prefix，如果数据库本身需插入这个字符串，使用#!PB#替代
     * @return string
     */
    public static function get_sql($sql)
    {
        self::$sql = $sql;
        // $sql = str_replace('#bc#', $GLOBALS['db']['db_prefix'], $sql);
        // $sql = str_replace('#!bc#', '#bc#', $sql);
        return self::$sql;
    }

    /**
    * SQL语句过滤程序（检查到有不安全的语句仅作替换和记录攻击日志而不中断）
    * @parem string $sql 要过滤的SQL语句 
    */
    protected static function _filter_sql($sql)
    {
        $clean = $error = '';
        $old_pos = 0;
        $pos = -1;
        $userIP = util::get_client_ip();
        $getUrl = util::get_cururl();
        // 完整的SQL检查，当数据量超过 1万 条的时候会出现性能瓶颈，特别是 10万 条的时候特别慢，最好就处理 1万 条
        while (true)
        {
            $pos = strpos($sql, '\'', $pos + 1);
            if ($pos === false)
            {
                break;
            }
            $clean .= substr($sql, $old_pos, $pos - $old_pos);
            while (true)
            {
                $pos1 = strpos($sql, '\'', $pos + 1);
                $pos2 = strpos($sql, '\\', $pos + 1);
                if ($pos1 === false)
                {
                    break;
                }
                elseif ($pos2 == false || $pos2 > $pos1)
                {
                    $pos = $pos1;
                    break;
                }
                $pos = $pos2 + 1;
            }
            $clean .= '$s$';
            $old_pos = $pos + 1;
        }
        $clean .= substr($sql, $old_pos);
        $clean = trim(strtolower(preg_replace(array('~\s+~s'), array(' '), $clean)));
        $fail = false;
        // sql语句中出现注解
        if (strpos($clean, '/*') > 2 || strpos($clean, '--') !== false || strpos($clean, '#') !== false)
        {
            $fail = true;
            $error = 'commet detect';
        }
        // 常用的程序里也不使用union，但是一些黑客使用它，所以检查它
        else if (strpos($clean, 'union') !== false && preg_match('~(^|[^a-z])union($|[^[a-z])~s', $clean) != 0)
        {
            $fail = true;
            $error = 'union detect';
        }
        // 这些函数不会被使用，但是黑客会用它来操作文件，down掉数据库
        elseif (strpos($clean, 'sleep') !== false && preg_match('~(^|[^a-z])sleep($|[^[a-z])~s', $clean) != 0)
        {
            $fail = true;
            $error = 'slown down detect';
        }
        elseif (strpos($clean, 'benchmark') !== false && preg_match('~(^|[^a-z])benchmark($|[^[a-z])~s', $clean) != 0)
        {
            $fail = true;
            $error = "slown down detect";
        }
        elseif (strpos($clean, 'load_file') !== false && preg_match('~(^|[^a-z])load_file($|[^[a-z])~s', $clean) != 0)
        {
            $fail = true;
            $error = "file fun detect";
        }
        elseif (strpos($clean, 'into outfile') !== false && preg_match('~(^|[^a-z])into\s+outfile($|[^[a-z])~s', $clean) != 0)
        {
            $fail = true;
            $error = "file fun detect";
        }
        // 检测到有错误后记录日志并对非法关键字进行替换
        if ($fail === true)
        {
            $sql = str_ireplace(self::$rps, self::$rpt, $sql);
            
            // 进行日志
            // $gurl = htmlspecialchars( util::get_cururl() );
            // $msg = "Time: {$qtime} -- ".date('y-m-d H:i', time())." -- {$gurl}<br>\n".htmlspecialchars( $sql )."<hr size='1' />\n";
            // log::add('filter_sql', $msg);
        }
        return $sql;
    }


    /**
    * 修正被防注入程序修改了的字符串
    * 在读出取时有必要完全还原才使用此方法
    * @param string $fvalue
    */
    public static function revert($fvalue)
    {
        $fvalue = str_ireplace(self::$rpt, self::$rps, $fvalue);
        return $fvalue;
    }


    /**
     * 记录慢查询日志
     *
     * @param string $sql
     * @param float $qtime
     * @return bool
     */
    protected static function _slow_query_log($sql, $qtime)
    {
        $gurl = htmlspecialchars(util::get_cururl());
        //$msg = "Time: {$qtime} -- " . date('y-m-d H:i', time()) . " -- {$gurl}<br>\n" . htmlspecialchars($sql) . "<hr size='1' />\n";
        $msg = "Time: {$qtime} -- " . date('y-m-d H:i', time()) . " -- {$gurl}\n" . $sql . "\n\n";
        log::add('slow_query', $msg);

        //CLI模式下每次都写到硬盘并清空日志数组，防止内存溢出
        if( PHP_SAPI == 'cli' )
        {
            log::save();
        }
    }


    /**
    * 获得分页符列表
    * @param $url 前置url（如果为空，则使用当前url，过滤$pagename=\d+等这些内容）
    *             如果url里含有 %s 则视这字符串为模板，会直接把pageno值替换这个%s
    * @param $config = array('count_num' => 0, 'pagesize' => 0, 'pagename' => 'pageno', 'cur_page' => 1, 'css_class' => 'lurd-pager', 'movepos' => 前后偏移);
    * @param $index_url  首页url，如果有，则用这个url替代pageno==1的url
    * @param $use_info 是否显示（共多少页多少条记录信息 ）
    * @return string
    */
    public static function pagination($url = '', $config = array(), $index_url = '', $use_info = true)
    {
        $config_df = array('count_num' => 0,'pagesize' => 20,'pagename' => 'pageno','cur_page' => 1,'css_class' => 'lurd-pager','movepos' => 6);
        foreach ($config_df as $k => $v)
        {
            if (empty($config[$k])) $config[$k] = $v;
        }
        
        // echo '<xmp>'; print_r($config);
        
        if (empty($url))
        {
            $config['url'] = preg_replace('/' . $config['pagename'] . '=\d+/i', '', '?' . $_SERVER['QUERY_STRING']);
            $config['url'] = preg_replace('/&{2,}/', '&', $config['url']);
        }
        else
        {
            $config['url'] = $url;
        }
        
        $istpl = preg_match("/%s/", $url);
        
        // 总页数
        $config['total_page'] = ceil($config['count_num'] / $config['pagesize']);
        
        // 限制结果页数
        if (isset($config['max_page']) && $config['total_page'] > $config['max_page'])
        {
            $config['total_page'] = $config['max_page'];
        }
        
        // 总页数不到二页时不分页
        if (empty($config) or $config['total_page'] < 2)
        {
            return '';
        }
        
        // 分页内容
        $pages = '<div class="' . $config['css_class'] . '">';
        // 下一页
        $next_page = $config['cur_page'] + 1;
        // 上一页
        $prev_page = $config['cur_page'] - 1;
        // 末页
        $last_page = $config['total_page'];
        
        $flag = 0;
        
        $nextstep = $config['movepos'] * 2 + 1;
        
        // 首页和上一页
        if ($config['cur_page'] > 1)
        {
            // 首页
            $dourl = $istpl ? str_replace('%s', 1, $config['url']) : $config['url'];
            if ($index_url != '') $dourl = $index_url;
            if ($config['total_page'] > $nextstep)
            {
                $pages .= "<a href='{$dourl}' class='nextprev'>&laquo;首页</a>\n";
            }
            // 上一页
            $dourl = $istpl ? str_replace('%s', $prev_page, $config['url']) : "{$config['url']}&{$config['pagename']}={$prev_page}";
            if ($index_url != '' && $prev_page == 1) $dourl = $index_url;
            $pages .= "<a href='{$dourl}' class='nextprev'>&laquo;上一页</a>\n";
        }
        
        // 前偏移
        for ($i = $config['cur_page'] - $config['movepos']; $i <= $config['cur_page'] - 1; $i++)
        {
            if ($i < 1)
            {
                continue;
            }
            $dourl = $istpl ? str_replace('%s', $i, $config['url']) : "{$config['url']}&{$config['pagename']}={$i}";
            if ($i == 1 && $index_url != '') $dourl = $index_url;
            $pages .= "<a href='{$dourl}'>$i</a>\n";
        }
        
        // 当前页
        $pages .= "<span class='current'>" . $config['cur_page'] . "</span>\n";
        
        // 后偏移
        if ($config['cur_page'] < $config['total_page'])
        {
            for ($i = $config['cur_page'] + 1; $i <= $config['total_page']; $i++)
            {
                $dourl = $istpl ? str_replace('%s', $i, $config['url']) : "{$config['url']}&{$config['pagename']}={$i}";
                $pages .= "<a href='{$dourl}'>$i</a>\n";
                $flag++;
                if ($flag == $config['movepos'])
                {
                    break;
                }
            }
        }
        
        // 末页和下一页
        if ($config['cur_page'] != $config['total_page'])
        {
            // 下一页
            $dourl = $istpl ? str_replace('%s', $next_page, $config['url']) : "{$config['url']}&{$config['pagename']}={$next_page}";
            $pages .= "<a href='{$dourl}' class='nextprev'>下一页&raquo;</a>\n";
            // 末页
            $dourl = $istpl ? str_replace('%s', $last_page, $config['url']) : "{$config['url']}&{$config['pagename']}={$last_page}";
            if ($config['total_page'] > $nextstep)
            {
                $pages .= "<a href='{$dourl}'>末页&raquo;</a>\n";
            }
        }
        
        // 输入框跳转
        if (!empty($config['input']))
        {
            if ($istpl)
            {
                $pages .= "<input type=\"text\" onkeydown=\"javascript:if(event.keyCode==13){ var offset = this.value; var url_tpl='{$config['url']}'; location = url_tpl.replace('%s'+, offset); }\" onkeyup=\"value=value.replace(/[^\d]/g,\'\')\" />";
            }
            else
            {
                $pages .= "<input type=\"text\" onkeydown=\"javascript:if(event.keyCode==13){ var offset = this.value; location=\'{$config['url']}&{$config['pagename']}='+offset;}\" onkeyup=\"value=value.replace(/[^\d]/g,\'\')\" />";
            }
        }
        if ($use_info)
        {
            $pages .= "<span>共 {$config['total_page']} 页，{$config['count_num']} 记录</span>\n";
        }
        $pages .= '</div>';
        return $pages;
    }


    /**
     * 返回查询游标
     * @return rsid
     */
    protected static function _get_rsid($rsid = '')
    {
        return $rsid == '' ? self::$cur_result : $rsid;
    }


    /**
     * 执行一条语句(读 + 写)
     *
     * @param  string $sql
     * @return $rsid (返回一个游标id或false)
     */
    public static function query($sql, $is_master = false) { }


    /**
     * (写)，执行一个出错也不中断的语句（通常是涉及唯一主键的操作）
     * 也可以强制设置 db::$not_break = true; 让任意位置的query操作转到这函数(执行当前函数马上恢复为false)
     * @param  string $sql
     * @return bool
     */
    public static function query_over($sql) { }


    /**
    * 取得最后一次插入记录的ID值
    *
    * @return int
    */
    public static function insert_id() { }


    /**
     * 返回受影响数目
     * @return init
     */
    public static function affected_rows() { }


    /**
     * 返回本次查询所得的总记录数...
     *
     * @return int
     */
    public static function num_rows($rsid = '') { }


    /**
     * (读)返回单条记录数据
     *
     * @parem  $rsid   (查询语句返回的游标，如果此项为空， 则用最后一次查询的游标)
     * @param  $result_type (DB_GET_ASSOC DB_GET_NUM DB_GET_BOTH)
     * @return array
     */
    public static function fetch_one($rsid = '', $result_type = DB_GET_ASSOC) { }
    // 继承的子类里也必须包含这个方法
    public static function fetch($rsid = '', $result_type = DB_GET_ASSOC)
    {
        return self::fetch_one($rsid, $result_type);
    }


    /**
     * (读)返回多条记录数据
     *
     * @deprecated    DB_GET_ASSOC DB_GET_NUM DB_GET_BOTH
     * @param   int   $result_type
     * @return  array
     */
    public static function fetch_all($rsid = '', $result_type = DB_GET_ASSOC) { }


    /**
     * (读)直接从一个sql语句返回单条记录数据
     * 查询的话默认是从数据库，但为了插入后立刻得到数据，有时也需要强制从主库取
     *
     * @deprecated   DB_GET_ASSOC DB_GET_NUM DB_GET_BOTH
     * @param  int   $result_type
     * @return array
     */
    public static function get_one($sql, $func = '', $is_master = false) { }


    /**
     * (读)直接从一个sql语句返回多条记录数据
     * 查询的话默认是从数据库，但为了插入后立刻得到数据，有时也需要强制从主库取
     *
     * @param  $sql
     * @param  $key 
     * @return  array
     */
    public static function get_all($sql, $func = '', $is_master = false) { }


    /**
    * 析放结果
    * @param bool
    */
    public static function free($rsid) { }


    /**
    * 检测连接是否已经超时
    * @param bool
    */
    public static function ping($link = 'w') { }


    /**
     * 设置是否自动提交事务
     * 只针对InnoDB类型表
     * 
     * @access public
     * @param bool $mode
     * @return bool
     */
    public static function autocommit($mode = false) { }


    /**
     * 开始事务
     * 只针对InnoDB类型表
     * 
     * @access public
     * @return bool
     */
    public static function begin_tran() { }


    /**
     * 提交事务
     * 在执行self::autocommit||begin_tran后执行
     * 
     * @access public
     * @return bool
     */
    public static function commit() { }


    /**
     * 回滚事务
     * 在执行self::autocommit||begin_tran后执行后执行
     * 
     * @access public
     * @return bool
     */
    public static function rollback() { }


    /**
	 * Generate an update string
	 *
	 * @access	public
	 * @param	string	the table upon which the query will be performed
	 * @param	array	an associative array data of key/values e.g. array('a'=>1,'b'=>2)
	 * @param	mixed	the "where" statement
     * @return  boolean 如果想得到affected_rows请调用 db::affected_rows
	 */
    public static function update($table = '', $set = array(), $where = NULL, $return_sql = FALSE) { }

    protected static function _get_update_sql($table = '', $fields = array(), $where = NULL)
    {
        $sql = "UPDATE `{$table}` SET ";
        
        if (isset($fields['is_admin']))
        {
            /* 通过后台管理操作会对该值有影响 */
            unset($fields['is_admin']);
        }
        
        if (!empty($fields))
        {
            foreach ($fields as $k => $v)
            {
                if (in_array($k, array("user_utime")))
                {
                    $sql .= "`{$k}` = {$v},";
                }
                else
                {
                    $sql .= "`{$k}` = \"{$v}\",";
                }
            }
            if (!is_array($where))
            {
                $where = array($where);
            }
            // 删除空字段,不然array("")会成为WHERE
            foreach ($where as $k => $v)
            {
                if (empty($v))
                {
                    unset($where[$k]);
                }
            }
            $where = empty($where) ? "" : " WHERE " . implode(" AND ", $where);
            $sql = substr($sql, 0, -1) . $where;
        }
        else
        {
            $sql = "";
        }
        return $sql;
    }
    
    /**
	 * Update_Batch
	 *
	 * Compiles an update string and runs the query
	 *
	 * @param	string	the table to retrieve the results from
	 * @param	array	an associative array of update values
	 * @param	string	the where key
	 * @return	object
	 */
    public static function update_batch($table = '', $set = NULL, $index = NULL, $where = NULL, $return_sql = FALSE) { }

    protected static function _get_update_batch_sql($table, $values, $index, $where)
    {
        $ids = array();
        $where = ($where != '' AND count($where) >=1) ? implode(" ", $where).' AND ' : '';

        foreach ($values as $val)
		{
            // 去重
            $key = md5($val[$index]);
			$ids[$key] = $val[$index];

			foreach (array_keys($val) as $field)
			{
				if ($field != $index)
				{
					$final[$field][$key] =  'When `'.$index.'` = "'.$val[$index].'" Then "'.$val[$field].'"';
				}
			}
		}
        //$ids = array_values($ids);

		$sql = "Update `".$table."` Set ";
		$cases = '';

		foreach ($final as $k => $v)
		{
			$cases .= '`'.$k.'` = Case '."\n";
			foreach ($v as $row)
			{
				$cases .= $row."\n";
			}

			$cases .= 'Else `'.$k.'` End, ';
		}

		$sql .= substr($cases, 0, -2);

		$sql .= ' Where '.$where.$index.' In ("'.implode('","', $ids).'")';

        // 一百条执行一次
        //for ($i = 0, $total = count($ar_set); $i < $total; $i = $i + 1)
        //{
            //$set = array_slice($ar_set, $i, 100);
        //}

        exit($sql);
        return $sql;
    }

    // --------------------------------------------------------------------
    
    /**
     * Generate an insert string
     *
     * @access	public
     * @param	string	the table upon which the query will be performed
     * @param	array	an associative array data of key/values e.g. array('a'=>1,'b'=>2)
	 * @return	string
	 */
    public static function insert($table = '', $set = NULL, $return_sql = FALSE) { }

    /**
	 * Insert_Batch
	 *
	 * Compiles batch insert strings and runs the queries
	 *
	 * @param	string	the table to retrieve the results from
	 * @param	array	an associative array of insert values
	 * @return	object
	 */
    public static function insert_batch($table = '', $set = NULL, $return_sql = FALSE) { }

    protected static function _get_insert_batch_sql($table, $set)
    {
        $keys_sql = $vals_sql = array();
        foreach ($set as $i=>$fields) 
        {
            $vals = array();
            foreach ($fields as $k => $v)
            {
                if ($i == 0 && $k == 0) 
                {
                    $keys_sql[] = "`$k`";
                }
                $vals[] = "\"$v\"";
            }
            $vals_sql[] = implode(",", $vals);
        }

        $sql = "Insert Into `{$table}`(".implode(", ", $keys_sql).") Values (".implode("), (", $vals_sql).")";
        return $sql;
    }

    protected static function _get_insert_sql($table, $fields)
    {
        $items_sql = $values_sql = "";
        foreach ($fields as $k => $v)
        {
            $items_sql .= "`$k`,";
            $values_sql .= "\"$v\",";
        }
        $sql = "Insert Into `{$table}` (" . substr($items_sql, 0, -1) . ") Values (" . substr($values_sql, 0, -1) . ")";
        return $sql;
    }
    
    // 不要在父类直接用 self::get_fields(),因为他只能取父类的，所有的self操作都应该放到子类去
    public static function get_fields($table, $set = array()) { }


    protected static function _get_fields($rows, $set = array())
    {
        $fields = array();
        foreach ($rows as $k => $v)
        {
            // 过滤自增主键
            // if ($v['Key'] != 'PRI')
            if ($v['Extra'] != 'auto_increment')
            {
                $fields[$v['Field']] = $v['Default'] === NULL ? '' : $v['Default'];
            }
        }
        // $set = array_flip($set);
        
        $arr1 = array();
        foreach ($set as $k => $v)
        {
            $arr1[] = $k;
        }
        $arr2 = array();
        foreach ($fields as $k => $v)
        {
            $arr2[] = $k;
        }
        
        $arr = array_intersect($arr1, $arr2);
        $result = array();
        foreach ($arr as $v)
        {
            // form提交过来为空则用表字段默认的
            $result[$v] = !isset($set[$v]) ? $fields[$v] : $set[$v];
        }
        
        return $result;
    }

    //入库数据处理，安全数据
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

    //出库数据整理
    public static function strclear($str)
    {
        if(is_array($str)===true)
        {
            foreach ($str as $key => $val)
            {                        
                if(is_array($val)===true)
                {
                    $str[$key] = self::strclear($val);                
                }
                else 
                {
                    //处理stripslashes没法处理的 _ % 字符
                    //$val = strtr($val, array('\_'=>'_', '\%'=>'%'));
                    $val = stripslashes($val);
                    $str[$key] = $val;
                }
            }
        }
        elseif (is_string($str)) 
        {
            //$str = strtr($str, array('\_'=>'_', '\%'=>'%'));
            $str = stripslashes($str);
        }
        return $str;
    }

    public static function get_last_sql()
    {
        return self::$sql;
    }


    /**
     * 判断表是否存在（表在程序执行完前不会被删除）
     * @var unknown
     */
    protected static $exists_tables = array();
    public static function table_exists($table_name)
    {
        if (isset(static::$exists_tables[$table_name]))
        {
            return static::$exists_tables[$table_name];
        } 
        
        $sql = "SHOW TABLES LIKE '" . $table_name . "'";
        //echo $sql, "\n";
        static::query($sql);
        $table = static::fetch_all();
        if (empty($table))
        {
            static::$exists_tables[$table_name] = FALSE;
        }
        else 
        {
            static::$exists_tables[$table_name] = TRUE;
        }
        return static::$exists_tables[$table_name];
    }

}

// ----------------------------------
// db操作类
// mysqli优先使用
// ----------------------------------
if (function_exists('mysqli_connect'))
{
    require_once dirname(__FILE__) . '/db_mysqli.php';
}
else
{
    require_once dirname(__FILE__) . '/db_mysql.php';
}
