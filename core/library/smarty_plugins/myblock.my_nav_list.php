<?php

function smarty_myblock_my_nav_list($_params, &$compiler)
{
    $sql = "Select * From `dcy_nav` Where `pid`=10 And `is_show`=1 Order By `sort` Asc";
    $list = db::get_all($sql);
    return $list;
}
