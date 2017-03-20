<?php

function smarty_myblock_my_goods_category_list($_params, &$compiler)
{
    $sql = "Select * From `dcy_goods_category` Where `pid`=0 Order By `sort` Asc";
    $list = db::get_all($sql);
    foreach ($list as $k=>$v) 
    {
        $list[$k]['url'] = URL."/index.php?m=Home&c=Goods&a=lists&class_id=".$v['id'];
        $list[$k]['cate_img'] = URL_PIC."/".$v['cate_img'];
        $sql = "Select * From `dcy_goods_category` Where `pid`='{$v['id']}' Order By `sort` Asc";
        $child_list = db::get_all($sql);
        foreach ($child_list as $kk=>$vv) 
        {
            $vv['url'] = URL."/index.php?m=Home&c=Goods&a=lists&class_id=".$vv['id'];
            
            $child_list[$kk] = $vv;
            $sql = "Select * From `dcy_goods_category` Where `pid`='{$vv['id']}' Order By `sort` Asc";
            $child_list2 = db::get_all($sql);
            foreach ($child_list2 as $kkk=>$vvv) 
            {
                $child_list2[$kkk]['url'] = URL."/index.php?m=Home&c=Goods&a=lists&class_id=".$vvv['id'];
            }
            $child_list[$kk]['child_list'] = $child_list2;
            // 筛选出2个作为显示的
            if ($kk <= 1) 
            {
                $list[$k]['child_show_list'][] = $vv;
            }
        }
        $list[$k]['child_list'] = $child_list;
    }
    //print_r($list);
    return $list;
}
