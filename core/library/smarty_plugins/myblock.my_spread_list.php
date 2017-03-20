<?php

function smarty_myblock_my_spread_list($_params, &$compiler)
{
    $sql = "Select * From `dcy_spread`";
    $list = db::get_all($sql);
    return $list;
}
