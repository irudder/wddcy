<?php


function smarty_myblock_my_links($_params, &$compiler)
{
    $wid   = isset($_params['wid']) ? $_params['wid'] : 0;

    if(empty($wid))
    {
        return array();
    }

    $sql = "SELECT * FROM `links` WHERE `wid`='{$wid}' and `status`='1' ORDER BY `oid` ASC";

    return db::get_all($sql);
}



?>
