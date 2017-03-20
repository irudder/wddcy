<?php

function smarty_myblock_my_keyword($_params, &$compiler)
{
    $sql = "SELECT * FROM `keyword` WHERE `status`=1 ORDER BY `hits` DESC LIMIT 6";
    return db::get_all($sql);
}



?>
