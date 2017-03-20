<?php
/******************************************
* @处理影视标签函数
* @以字符串方式传入,通过ff_param_lable函数解析为以下变量
* ids:调用指定ID的一个或多个数据,如 1,2,3
* c_id:数据所在分类,可调出一个或多个分类数据,如 1,2,3 默认值为全部,在当前分类为:'.$cid.'
* field:调用影视类的指定字段,如(id,name,actor) 默认全部
* limit:数据条数,默认值为10,可以指定从第几条开始,如3,8(表示共调用8条,从第3条开始)
* order:推荐方式(id/addtime/hits/year/up/down) (desc/asc/rand())
* wd:'关键字' 用于调用自定义关键字(搜索/标签)结果
* serial:调用连载信息(all/数字) 全部连载值为all 其它数字为大于该数字的连载影片
* time: 指定上传时间内,如(1/7/30) 分别表示(当天/本周/本月)------未做好
* stars:推荐星级数,可调出一个或多个星级数据,如 1,2,3 默认值为全部
* hits:大于指定人气值的数据(如:888)或某段之间的(如:888,999)
* up:大于指定支持值的数据(如:888)或某段之间的(如:888,999)
* down:大于指定反对值的数据(如:888)或某段之间的(如:888,999)
* gold:大于指定评分平均值的数据(如:6)或某段之间的(如:1,8)/范围:0-10
* golder:大于指定评分人的数据(如:888)或某段之间的(如:888,999)
*/

function smarty_myblock_my_mysql_vod($_params, &$compiler)
{
    $where = array(); $url = array();

	$field    = !empty($_params['field']) ? $_params['field'] : '*';
	$limit    = !empty($_params['limit']) ? $_params['limit'] : '10';
	$order    = !empty($_params['order']) ? $_params['order'] : 'addtime';
    $ids      = !empty($_params['ids']) ? $_params['ids'] : 0;
    $c_id     = !empty($_params['c_id']) ? $_params['c_id'] : 0;
    $wd       = !empty($_params['wd']) ? $_params['wd'] : '';
	$letter   = !empty($_params['letter']) ? $_params['letter'] : '';
	$stars    = !empty($_params['stars']) ? $_params['stars'] : '';
	$day      = !empty($_params['day']) ? $_params['day'] : '';
	$hits     = !empty($_params['hits']) ? $_params['hits'] : '';
	$up       = !empty($_params['up']) ? $_params['up'] : '';
	$down     = !empty($_params['down']) ? $_params['down'] : '';
	$year     = !empty($_params['year']) ? $_params['year'] : '';
	$area     = !empty($_params['area']) ? $_params['area'] : '';
	$language = !empty($_params['language']) ? $_params['language'] : '';
	$name     = !empty($_params['name']) ? $_params['name'] : '';
	$title    = !empty($_params['title']) ? $_params['title'] : '';
	$actor    = !empty($_params['actor']) ? $_params['actor'] : '';
	$director = !empty($_params['director']) ? $_params['director'] : '';
	$play     = !empty($_params['play']) ? $_params['play'] : '';
	$gold     = !empty($_params['gold']) ? $_params['gold'] : '';
	$golder   = !empty($_params['golder']) ? $_params['golder'] : '';
	$page     = isset($_params['page']) ? $_params['page'] : FALSE;

    $params_cache_name = md5(implode(',',$_params));
    $list = cache::get('vod', $params_cache_name);
    if (!empty($list)) 
    {
        //return $list;
    }

	$where[] = "`status`=1";	

    if(!empty($ids))
    {
		$ids_arr = explode(',',$ids);
        if (count($ids_arr) > 1) 
        {
            $where[] = "`id` IN ({$ids})";
        }
        else 
        {
            $where[] = "`id`={$ids}";
        }
        $url['ids'] = $ids;
    }
    else 
    {
        if(!empty($c_id))
        {
            $c_id_arr = explode(',',$c_id);
            $c_ids = $c_id;
            //如果只传了一个id过来
            if (count($c_id_arr) == 1) 
            {
                $pid = cache::get('vod', 'pid_'.$c_id);
                if (empty($pid)) 
                {
                    $sql = "SELECT `pid` FROM `channel` WHERE `id`={$c_id}";
                    $chan = db::get_one($sql);
                    cache::set('vod', 'pid_'.$c_id, $chan['pid']);
                }
                //如果是一级分类
                if ($pid==0) 
                {
                    $c_id_arr = array();
                    $sql = "SELECT `id` FROM `channel` WHERE `pid`={$c_id}";
                    $child = db::get_all($sql);
                    if (!empty($child)) 
                    {
                        foreach ($child as $v) 
                        {
                            $c_id_arr[] = $v['id'];
                        }
                        $c_ids = implode(",", $c_id_arr);
                    }
                }
            }
            $where[] = "`c_id` IN ({$c_ids})";
            $url['c_id'] = $c_id;
        }
    }

    if (!empty($day)) 
    {
        $day = util::getxtime($day);
		$where[] = "`addtime`={$day}";
        $url['day'] = $day;
	}	

    if (!empty($hits)) 
    {
		$hits = explode(',',$hits);
        if (count($hits)>1) 
        {
			$where[] = "`hits` between {$hits[0]} AND {$hits[1]}";
        }
        else
        {
			$where[] = "`hits`={$hits[0]}";
		}
        $url['hits'] = $hits;
	}

    if(!empty($stars))
    {
        $where[] = "`stars` IN ({$stars})";
        $url['stars'] = $stars;
    }
    if(!empty($letter))
    {
        $where[] = "`letter` IN ({$letter})";
        $url['letter'] = $letter;
    }
    if(!empty($year))
    {
        $where[] = "`year`={$year}";
        $url['year'] = $year;
    }
    if(!empty($area))
    {
        $where[] = "`area`='{$area}'";
        $url['area'] = $area;
    }
    if(!empty($language))
    {
        $where[] = "`language`='{$language}'";
        $url['language'] = $language;
    }
    if(!empty($name))
    {
        $where[] = "`name` LIKE '%{$name}%'";
        $url['name'] = $name;
    }
    if(!empty($title))
    {
        $where[] = "`title` LIKE '%{$title}%'";
        $url['title'] = $title;
    }
    if(!empty($actor))
    {
        $where[] = "`actor` LIKE '%{$actor}%'";
        $url['actor'] = $actor;
    }
    if(!empty($director))
    {
        $where[] = "`director` LIKE '%{$director}%'";
        $url['director'] = $director;
    }
    if(!empty($play))
    {
        $where[] = "`play` LIKE '%{$play}%'";
        $url['play'] = $play;
    }
    if(!empty($wd))
    {
        $where[] = "(`name` LIKE '%{$wd}%' OR `actor` LIKE '%{$wd}%' OR `director` LIKE '%{$wd}%')";
        $url['wd'] = $wd;
    }

    if (!empty($up)) 
    {
		$up = explode(',',$up);
        if (count($up)>1) 
        {
			$where[] = "`up` between {$up[0]} AND {$up[1]}";
        }
        else
        {
			$where[] = "`up`={$up[0]}";
		}
        $url['up'] = $up;
	}
    if (!empty($down)) 
    {
		$down = explode(',',$down);
        if (count($down)>1) 
        {
			$where[] = "`down` between {$down[0]} AND {$down[1]}";
        }
        else
        {
			$where[] = "`down`={$down[0]}";
		}
        $url['down'] = $down;
	}
    if (!empty($gold)) 
    {
		$gold = explode(',',$gold);
        if (count($gold)>1) 
        {
			$where[] = "`gold` between {$gold[0]} AND {$gold[1]}";
        }
        else
        {
			$where[] = "`gold`={$gold[0]}";
		}
        $url['gold'] = $gold;
	}
    if (!empty($golder)) 
    {
		$golder = explode(',',$golder);
        if (count($golder)>1) 
        {
			$where[] = "`golder` between {$golder[0]} AND {$golder[1]}";
        }
        else
        {
			$where[] = "`golder`={$golder[0]}";
		}
        $url['golder'] = $golder;
	}

    $where = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

    $url = '&' . http_build_query($url);
    if ($page) 
    {
        $sql = "SELECT COUNT(*) AS count FROM `video` {$where}";
        $row = db::get_one($sql);
        $config['current_page'] = req::item('page_no', 1);   //当前页数,至少为1
        $config['page_size']    = !empty($limit) ? $limit : 20;      //每页显示多少条
        $config['total_rs']     = $row['count'];      //总记录数
        $config['url_prefix']   = '/?ac=' . req::item('ac') . $url;     //网址前缀
        $config['page_name']    = 'page_no';  //当前分页变量名(默认是page_no,即访问是用 url_prefix&page_no=xxx )
        $config['move_size']    = 2;    //前后偏移量（默认是5）
        $config['input']        = 0;    //是否使用输入跳转框(0|1)
        $pages = util::pagination($config);

        $offset = ( $config['current_page'] - 1 ) * $config['page_size'];
        //$sql = "SELECT * FROM `video` {$where} {$order} LIMIT {$offset}, {$config['page_size']}";
        $sql = "SELECT {$field} FROM `video` {$where} ORDER BY {$order} LIMIT {$offset}, {$config['page_size']}";
        tpl::assign('pages', $pages);
    }
    else 
    {
        $sql = "SELECT {$field} FROM `video` {$where} ORDER BY {$order} LIMIT {$limit}";
    }
    $list = db::get_all($sql);
    if (!empty($list)) 
    {
        foreach ($list as $key=>$val) 
        {
            $list[$key]['pic_s'] = PICURL . "/uploads_s/" . $list[$key]['pic'];
            $list[$key]['pic'] = PICURL . "/uploads/" . $list[$key]['pic'];
            $list[$key]['readurl'] = 'http://' . URL . '/' . $list[$key]['c_dir'] . '/' . $list[$key]['id'] . '/';

            if ($list[$key]['title'] == 'BD') 
                $list[$key]['title'] = '超清';
            elseif ($list[$key]['title'] == 'TV') 
                $list[$key]['title'] = '高清';
            else
                $list[$key]['title'] = '普清';
        }
    }
    else 
    {
        $list = array();
    }
    
    cache::set('vod', $params_cache_name, $list);
    return $list;
}



?>
