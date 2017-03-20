<?php
/**
 * 状态转提示文字标识
 *
 * @param $status
 * @return void
 */               
function smarty_modifier_err_type_txt( $err_type )
{
   if( isset($GLOBALS['config']['server_err_type'][$err_type]) )
   {
        $restr = $GLOBALS['config']['server_err_type'][$err_type];
        return $restr;
   } else {
        return '未知';
   }
}
