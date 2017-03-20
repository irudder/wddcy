<?php
/**
 * 状态转提示文字标识
 *
 * @param $status
 * @return void
 */               
function smarty_modifier_status_img( $status, $m='text' )
{
   if( isset($GLOBALS['config']['server_status'][$status]) )
   {
        if( $status != 1 ) {
            return "<span style='color:red'>".$GLOBALS['config']['server_status'][$status]."</span>";
        } else {
            return $GLOBALS['config']['server_status'][$status];
        }
   } else {
        return '未知';
   }
}
