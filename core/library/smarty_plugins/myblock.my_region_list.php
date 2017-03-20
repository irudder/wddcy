<?php

function smarty_myblock_my_region_list($_params, &$compiler)
{
    $sql = "Select * From `dcy_region` Where `pid`=1 Order By `sort` Asc";
    $list = db::get_all($sql);
    return $list;
}
